use serde::{Deserialize, Serialize};
use std::path::{Path, PathBuf};

/// Driver de base de données choisi via l'assistant /setup.
#[derive(Debug, Clone, Serialize, Deserialize, PartialEq, Eq)]
#[serde(rename_all = "lowercase")]
pub enum DbDriver {
    Mysql,
    Sqlite,
}

/// Paramètres de connexion à la base.
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct DbConfig {
    pub driver: DbDriver,
    #[serde(default)]
    pub host: String,
    #[serde(default)]
    pub port: u16,
    #[serde(default)]
    pub database: String,
    #[serde(default)]
    pub username: String,
    #[serde(default)]
    pub password: String,
    /// Chemin du fichier SQLite (driver = sqlite)
    #[serde(default)]
    pub sqlite_path: String,
}

impl DbConfig {
    /// URL de connexion SQLx selon le driver.
    pub fn connect_url(&self) -> String {
        match self.driver {
            DbDriver::Sqlite => format!("sqlite://{}?mode=rwc", self.sqlite_path),
            DbDriver::Mysql => format!(
                "mysql://{}:{}@{}:{}/{}",
                self.username,
                self.password,
                if self.host.is_empty() { "127.0.0.1" } else { &self.host },
                if self.port == 0 { 3306 } else { self.port },
                self.database
            ),
        }
    }
}

/// Configuration persistée de l'application (dans le dossier de données).
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AppConfig {
    /// Clé applicative (base64) — sert au HMAC du code de vérification.
    pub app_key: String,
    /// Secret de signature des JWT.
    pub jwt_secret: String,
    /// Configuration DB (None tant que l'assistant /setup n'est pas passé).
    #[serde(default)]
    pub db: Option<DbConfig>,
    /// L'assistant a-t-il été complété (migrations + seed faits) ?
    #[serde(default)]
    pub installed: bool,
}

impl AppConfig {
    /// Dossier de données : $GM_DATA_DIR sinon ./data
    pub fn data_dir() -> PathBuf {
        match std::env::var("GM_DATA_DIR") {
            Ok(d) if !d.is_empty() => PathBuf::from(d),
            _ => PathBuf::from("./data"),
        }
    }

    pub fn config_path() -> PathBuf {
        Self::data_dir().join("config.json")
    }

    /// Charge la config, ou en crée une neuve (clés générées) si absente.
    pub fn load_or_init() -> anyhow::Result<Self> {
        let dir = Self::data_dir();
        std::fs::create_dir_all(&dir)?;
        let path = Self::config_path();

        if path.exists() {
            let raw = std::fs::read_to_string(&path)?;
            let cfg: AppConfig = serde_json::from_str(&raw)?;
            Ok(cfg)
        } else {
            let cfg = AppConfig {
                app_key: gen_key_b64(),
                jwt_secret: gen_key_b64(),
                db: None,
                installed: false,
            };
            cfg.save()?;
            Ok(cfg)
        }
    }

    pub fn save(&self) -> anyhow::Result<()> {
        let path = Self::config_path();
        if let Some(parent) = Path::new(&path).parent() {
            std::fs::create_dir_all(parent)?;
        }
        std::fs::write(&path, serde_json::to_string_pretty(self)?)?;
        Ok(())
    }

    /// Clé applicative décodée en octets bruts (pour le HMAC).
    /// Compatible avec le format Laravel `base64:...`.
    pub fn app_key_bytes(&self) -> Vec<u8> {
        use base64::Engine;
        let raw = self.app_key.strip_prefix("base64:").unwrap_or(&self.app_key);
        base64::engine::general_purpose::STANDARD
            .decode(raw)
            .unwrap_or_else(|_| self.app_key.as_bytes().to_vec())
    }
}

/// Génère une clé aléatoire encodée en base64 (préfixe Laravel-compatible).
pub fn gen_key_b64() -> String {
    use base64::Engine;
    use rand::RngCore;
    let mut bytes = [0u8; 32];
    rand::thread_rng().fill_bytes(&mut bytes);
    format!("base64:{}", base64::engine::general_purpose::STANDARD.encode(bytes))
}
