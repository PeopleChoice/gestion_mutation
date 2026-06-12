# Gestion Mutations — Application de bureau Windows (Tauri)

Ce document explique comment transformer l'application Laravel en un **`.exe`
Windows installable** grâce à [Tauri](https://tauri.app), avec un **assistant de
configuration** au premier lancement permettant de choisir **MySQL (par défaut)**
ou **SQLite**.

---

## 1. Comment ça marche (architecture)

Tauri n'exécute **pas** PHP nativement. L'application empaquetée fonctionne
selon le schéma suivant :

```
┌─────────────────────── Fenêtre Tauri (WebView2) ───────────────────────┐
│                                                                         │
│   Au lancement : page de chargement → bascule vers http://127.0.0.1:8777│
│                                                                         │
└───────────────▲─────────────────────────────────────────────────────────┘
                │ HTTP local
┌───────────────┴─────────────────────────────────────────────────────────┐
│  Launcher Rust (main.rs)                                                  │
│   1. crée %LOCALAPPDATA%\sn.gestion-mutations.app  (données modifiables)  │
│   2. copie .env initial, prépare storage/                                 │
│   3. lance :  php.exe -S 127.0.0.1:<port> server.php  (Laravel embarqué)  │
│   4. attend que le serveur réponde, puis navigue la fenêtre dessus        │
│   5. tue le process PHP à la fermeture                                     │
└───────────────────────────────────────────────────────────────────────────┘
```

- Le **code Laravel** et un **PHP portable** sont embarqués dans les *resources*
  de l'installeur (lecture seule, dans `Program Files` / dossier utilisateur).
- Les éléments **modifiables** (`.env`, `storage/`, base SQLite, sessions) sont
  relocalisés dans `%LOCALAPPDATA%\sn.gestion-mutations.app\` — voir
  [../bootstrap/app.php](../bootstrap/app.php) (`GM_DATA_DIR`).
- Au **premier lancement**, le middleware
  [EnsureAppIsConfigured](../app/Http/Middleware/EnsureAppIsConfigured.php) redirige
  vers **`/setup`** ([SetupController](../app/Http/Controllers/SetupController.php))
  tant que la base n'est pas configurée.

> 📂 **Tout le packaging Tauri vit dans le dossier `versionwind/`** (ce dossier).
> Les fichiers Laravel modifiés (middleware, contrôleur, vue, `bootstrap/app.php`,
> `routes/web.php`) restent eux dans le projet : ils font partie de l'application.

> ⚠️ **Le `.exe` se compile sur Windows.** Le code a été préparé sur macOS, mais
> la commande finale `tauri build` doit être exécutée sur une machine Windows
> (ou une VM Windows), car elle produit un binaire natif Windows.

---

## 2. Prérequis sur la machine Windows

| Outil | Pourquoi | Installation |
|-------|----------|--------------|
| **Rust** (rustup) | compile le launcher Tauri | <https://rustup.rs> |
| **Visual Studio Build Tools** (charge de travail « Développement Desktop C++ ») | toolchain MSVC requise par Rust/Tauri | <https://visualstudio.microsoft.com/visual-cpp-build-tools/> |
| **Node.js 18+** + npm | CLI Tauri + assets Vite | <https://nodejs.org> |
| **PHP 8.2+ CLI** + **Composer** | dépendances Laravel au build | <https://windows.php.net> / <https://getcomposer.org> |
| **WebView2 Runtime** | moteur de rendu | déjà présent sur Windows 10/11 à jour |

> Le **runtime PHP embarqué** dans l'app est téléchargé automatiquement par le
> script de build (build *Non Thread Safe* x64) — distinct du PHP CLI utilisé
> pour le build.

---

## 3. Build en une commande

Depuis la **racine du projet Laravel**, dans **PowerShell** :

```powershell
powershell -ExecutionPolicy Bypass -File versionwind\scripts\build-windows.ps1
```

Le script [scripts/build-windows.ps1](scripts/build-windows.ps1) enchaîne :

1. `composer install --no-dev --optimize-autoloader`
2. `npm install && npm run build` (assets Vite)
3. copie du projet dans `versionwind\src-tauri\laravel\` (sans `.git`, `node_modules`, `.env`, etc.)
4. téléchargement du PHP portable dans `versionwind\src-tauri\php\` (si absent)
5. `tauri build` → **installeur NSIS**

Résultat :

```
versionwind\src-tauri\target\release\bundle\nsis\Gestion Mutations_1.0.0_x64-setup.exe
```

C'est ce fichier que vous distribuez aux utilisateurs.

---

## 4. Build manuel (étape par étape)

Si vous préférez contrôler chaque étape :

```powershell
# 1. Dépendances PHP de production
composer install --no-dev --optimize-autoloader

# 2. Assets
npm install
npm run build
php artisan config:clear; php artisan route:clear; php artisan view:clear

# 3. Copie Laravel embarquée  (ou via le script build-windows.ps1)
#    -> versionwind\src-tauri\laravel  (+ y copier server.php et .env.tauri)

# 4. PHP portable
powershell -ExecutionPolicy Bypass -File versionwind\scripts\download-php.ps1
#    Version différente ? :
#    ... download-php.ps1 -PhpVersion 8.3.14

# 5. (1re fois) icônes — à partir d'un logo carré PNG  (depuis versionwind\)
cd versionwind
npx @tauri-apps/cli icon ..\logo.png

# 6. Build  (depuis versionwind\, là où se trouve src-tauri)
npx @tauri-apps/cli build
```

### Tester en développement (sans empaqueter)

Sur Windows, après avoir récupéré le PHP portable et préparé
`versionwind\src-tauri\laravel`, depuis le dossier `versionwind\` :

```powershell
cd versionwind
$env:GM_PHP_DIR = "$PWD\src-tauri\php"
$env:GM_APP_DIR = "$PWD\src-tauri\laravel"
npx @tauri-apps/cli dev
```

Les variables `GM_PHP_DIR` / `GM_APP_DIR` permettent au launcher de trouver PHP
et l'app hors empaquetage (voir [src-tauri/src/main.rs](src-tauri/src/main.rs)).

---

## 5. Premier lancement côté utilisateur

1. L'utilisateur installe via le `setup.exe`, puis lance **Gestion Mutations**.
2. Une page de chargement s'affiche le temps que le serveur local démarre.
3. **Écran `/setup`** :
   - **MySQL** (par défaut) : hôte, port, base, utilisateur, mot de passe →
     bouton *Tester la connexion* puis *Installer et démarrer*.
   - **SQLite** : aucun paramètre, un fichier `database.sqlite` est créé
     automatiquement dans le dossier de données.
4. Les migrations (et seeders éventuels) s'exécutent, puis redirection vers la
   page de connexion. La config est mémorisée — l'assistant ne réapparaît plus.

> **MySQL par défaut** suppose qu'un serveur MySQL est **accessible** depuis le
> poste (local, XAMPP, ou serveur réseau de l'établissement). Pour un poste
> totalement autonome **sans serveur**, choisir **SQLite** sur l'écran de setup.

### Où sont stockées les données ?

```
%LOCALAPPDATA%\sn.gestion-mutations.app\
├── .env                 (configuration, écrite par l'assistant)
├── installed.flag       (marqueur « configuré »)
├── database.sqlite      (si SQLite choisi)
└── storage\             (logs, sessions, cache, vues compilées, fichiers générés)
```

Pour **réinitialiser** la configuration : supprimer ce dossier, ou seulement
`installed.flag` pour repasser par l'assistant.

---

## 6. Personnalisation

| Élément | Où |
|--------|-----|
| Nom / version de l'app, icônes, installeur | [src-tauri/tauri.conf.json](src-tauri/tauri.conf.json) |
| Identifiant (et donc dossier de données) | `identifier` dans `tauri.conf.json` |
| Plage de ports du serveur local | `PORT_START` / `PORT_END` dans [main.rs](src-tauri/src/main.rs) |
| Extensions PHP activées | [src-tauri/php/php.ini](src-tauri/php/php.ini) |
| Valeurs `.env` par défaut | [.env.tauri](.env.tauri) |
| Version du PHP portable | paramètre `-PhpVersion` de [download-php.ps1](scripts/download-php.ps1) |

---

## 7. Dépannage

- **Fenêtre bloquée sur « Démarrage du serveur local… »** : PHP n'a pas démarré.
  Vérifier que `versionwind\src-tauri\php\php.exe` et `php.ini` existent, et que
  les extensions (`pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`) se chargent :
  `versionwind\src-tauri\php\php.exe -c versionwind\src-tauri\php\php.ini -m`.
- **« Connexion impossible » (MySQL)** : serveur injoignable / identifiants /
  la base doit déjà exister et l'utilisateur pouvoir créer des tables.
- **Erreur d'écriture / permissions** : les écritures doivent aller dans
  `%LOCALAPPDATA%`. Si une erreur pointe vers `Program Files`, vérifier que
  `GM_DATA_DIR` est bien pris en compte dans `bootstrap/app.php`.
- **Build Rust échoue** : installer les *Build Tools Visual Studio (C++)* et
  redémarrer le terminal.
