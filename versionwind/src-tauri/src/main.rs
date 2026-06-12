// Empêche l'ouverture d'une console Windows en build de production.
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use std::fs;
use std::net::{TcpListener, TcpStream};
use std::path::{Path, PathBuf};
use std::process::{Child, Command};
use std::sync::Mutex;
use std::time::{Duration, Instant};

use tauri::path::BaseDirectory;
use tauri::{Manager, RunEvent, WebviewWindow};

/// Conserve le process PHP pour pouvoir l'arrêter à la fermeture de l'app.
struct PhpServer(Mutex<Option<Child>>);

const HOST: &str = "127.0.0.1";
const PORT_START: u16 = 8777;
const PORT_END: u16 = 8800;

fn main() {
    tauri::Builder::default()
        .manage(PhpServer(Mutex::new(None)))
        .setup(|app| {
            let handle = app.handle().clone();

            // 1. Dossier de données inscriptible de l'utilisateur (%LOCALAPPDATA%\<id>).
            let data_dir = app
                .path()
                .app_local_data_dir()
                .expect("dossier de données introuvable");
            prepare_data_dir(&data_dir, &handle).expect("préparation du dossier de données");

            // 2. Localiser le runtime PHP et l'application Laravel embarqués.
            let php_exe = resolve_php(&handle).expect("php.exe introuvable");
            let php_ini = php_exe
                .parent()
                .map(|p| p.join("php.ini"))
                .filter(|p| p.exists());
            let app_dir = resolve_app(&handle).expect("application Laravel introuvable");

            // 3. Choisir un port libre.
            let port = pick_free_port().expect("aucun port libre disponible");

            // 4. Démarrer le serveur PHP intégré.
            let child = spawn_php(&php_exe, php_ini.as_deref(), &app_dir, &data_dir, port)
                .expect("échec du démarrage de PHP");
            *app.state::<PhpServer>().0.lock().unwrap() = Some(child);

            // 5. Quand le serveur répond, basculer la fenêtre vers l'app Laravel.
            let window: WebviewWindow = app.get_webview_window("main").unwrap();
            std::thread::spawn(move || {
                if wait_for_server(port, Duration::from_secs(30)) {
                    let url = format!("http://{HOST}:{port}");
                    let _ = window.navigate(url.parse().unwrap());
                } else {
                    let _ = window.eval(
                        "document.querySelector('.msg').textContent = \
                         'Impossible de démarrer le serveur local. Vérifiez l\\'installation.';",
                    );
                }
            });

            Ok(())
        })
        .build(tauri::generate_context!())
        .expect("erreur au lancement de l'application")
        .run(|app_handle, event| {
            // Arrêter PHP proprement à la sortie.
            if let RunEvent::ExitRequested { .. } | RunEvent::Exit = event {
                if let Some(state) = app_handle.try_state::<PhpServer>() {
                    if let Some(mut child) = state.0.lock().unwrap().take() {
                        let _ = child.kill();
                    }
                }
            }
        });
}

/// Crée le dossier de données, l'arborescence storage/ et le .env initial.
fn prepare_data_dir(data_dir: &Path, handle: &tauri::AppHandle) -> std::io::Result<()> {
    fs::create_dir_all(data_dir)?;

    for sub in [
        "storage/app/public",
        "storage/framework/cache/data",
        "storage/framework/sessions",
        "storage/framework/views",
        "storage/framework/testing",
        "storage/logs",
    ] {
        fs::create_dir_all(data_dir.join(sub))?;
    }

    // .env initial copié depuis le modèle embarqué, seulement s'il n'existe pas.
    let env_path = data_dir.join(".env");
    if !env_path.exists() {
        if let Ok(template) = handle
            .path()
            .resolve("laravel/.env.tauri", BaseDirectory::Resource)
        {
            if template.exists() {
                fs::copy(&template, &env_path)?;
            }
        }
    }

    Ok(())
}

/// Localise php.exe (embarqué sous resources/php, ou via GM_PHP_DIR en dev).
fn resolve_php(handle: &tauri::AppHandle) -> Option<PathBuf> {
    if let Ok(dir) = handle.path().resolve("php", BaseDirectory::Resource) {
        let exe = dir.join(php_binary_name());
        if exe.exists() {
            return Some(exe);
        }
    }
    if let Ok(dir) = std::env::var("GM_PHP_DIR") {
        let exe = Path::new(&dir).join(php_binary_name());
        if exe.exists() {
            return Some(exe);
        }
    }
    None
}

/// Localise le dossier de l'application Laravel embarquée (resources/app),
/// ou via GM_APP_DIR en dev.
fn resolve_app(handle: &tauri::AppHandle) -> Option<PathBuf> {
    if let Ok(dir) = handle.path().resolve("laravel", BaseDirectory::Resource) {
        if dir.join("public/index.php").exists() {
            return Some(dir);
        }
    }
    if let Ok(dir) = std::env::var("GM_APP_DIR") {
        let p = PathBuf::from(dir);
        if p.join("public/index.php").exists() {
            return Some(p);
        }
    }
    None
}

#[cfg(windows)]
fn php_binary_name() -> &'static str {
    "php.exe"
}

#[cfg(not(windows))]
fn php_binary_name() -> &'static str {
    "php"
}

/// Démarre `php -S` avec le routeur server.php, dans le dossier de l'app.
fn spawn_php(
    php_exe: &Path,
    php_ini: Option<&Path>,
    app_dir: &Path,
    data_dir: &Path,
    port: u16,
) -> std::io::Result<Child> {
    let mut cmd = Command::new(php_exe);

    if let Some(ini) = php_ini {
        cmd.arg("-c").arg(ini);
    }

    cmd.arg("-S")
        .arg(format!("{HOST}:{port}"))
        .arg("server.php")
        .current_dir(app_dir)
        // Transmet le dossier de données à Laravel (bootstrap/app.php).
        .env("GM_DATA_DIR", data_dir)
        .env("APP_URL", format!("http://{HOST}:{port}"));

    cmd.spawn()
}

/// Cherche un port TCP libre dans la plage configurée.
fn pick_free_port() -> Option<u16> {
    (PORT_START..=PORT_END).find(|&port| TcpListener::bind((HOST, port)).is_ok())
}

/// Attend que le serveur HTTP accepte les connexions (avec timeout).
fn wait_for_server(port: u16, timeout: Duration) -> bool {
    let deadline = Instant::now() + timeout;
    while Instant::now() < deadline {
        if TcpStream::connect((HOST, port)).is_ok() {
            // Petit délai pour laisser Laravel finir de booter.
            std::thread::sleep(Duration::from_millis(400));
            return true;
        }
        std::thread::sleep(Duration::from_millis(200));
    }
    false
}
