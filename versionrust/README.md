# Gestion Mutations — Édition native (Rust + Vue.js)

Réécriture **native** de l'application Laravel d'origine, sans PHP :

- **Backend** : Rust + [Axum](https://github.com/tokio-rs/axum) + [SQLx](https://github.com/launchbadge/sqlx)
  (pool `Any` → **MySQL ou SQLite au choix**, MySQL par défaut), API REST JSON.
- **Frontend** : Vue 3 + Vite + Vue Router + Pinia + Tailwind CSS, SPA.

> La spécification fonctionnelle complète extraite du projet Laravel est dans
> [SPEC.md](SPEC.md). C'est la référence pour compléter les modules restants.

---

## Architecture

```
versionrust/
├── SPEC.md                  spécification du domaine (référence)
├── backend/                 API Rust (Axum + SQLx)
│   ├── Cargo.toml
│   └── src/
│       ├── main.rs          routeur + démarrage
│       ├── config.rs        config persistée (clés, DB, état)
│       ├── state.rs         état partagé + init du pool DB
│       ├── migrations.rs    schéma (dialect-aware MySQL/SQLite)
│       ├── seed.rs          rôles + compte admin
│       ├── auth.rs          JWT + Argon2 + RBAC
│       ├── domain.rs        logique métier (code vérif, propriétaires)
│       ├── util.rs          helpers (n° notif, HMAC, JSON, logs)
│       └── handlers/        endpoints REST par module
└── frontend/                SPA Vue 3
    └── src/
        ├── router/ stores/ components/ views/
        └── api.js           client axios (JWT + 401/503)
```

---

## Démarrer en développement

Prérequis : **Rust** (rustup), **Node.js 18+**.

### 1. Backend

```bash
cd versionrust/backend
cargo run
# → http://127.0.0.1:8788   (API sous /api)
```

Au premier lancement, le backend crée `data/config.json` (clés générées) et
attend la configuration via l'assistant `/setup`.

### 2. Frontend

```bash
cd versionrust/frontend
npm install
npm run dev
# → http://127.0.0.1:5173   (proxy /api vers le backend)
```

Ouvrez **http://127.0.0.1:5173** :
1. **Assistant de configuration** : choisissez **MySQL** (hôte/port/base/login)
   ou **SQLite** (zéro config). Migrations + seed automatiques.
2. **Connexion** : compte admin par défaut `admin@domaines.sn` / `admin1234`
   (modifiable via les variables `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD`).

### Variables d'environnement (backend)

| Variable | Rôle | Défaut |
|----------|------|--------|
| `GM_DATA_DIR` | dossier de données (config + SQLite) | `./data` |
| `GM_PORT` | port d'écoute du backend | `8788` |
| `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD` | compte admin initial | `admin@domaines.sn` / `admin1234` |
| `GM_ATTRIBUTION_FORCE_PASSWORD` | mot de passe de forçage d'attribution | (désactivé) |

---

## Build de production

```bash
# Frontend -> versionrust/frontend/dist
cd versionrust/frontend && npm run build

# Backend (sert automatiquement frontend/dist s'il existe)
cd ../backend && cargo build --release
./target/release/gestion-mutations-backend
```

Le backend sert alors la SPA et l'API sur le même port (8788).

> **Empaquetage Windows** : ce backend natif peut remplacer le sidecar PHP de
> `versionwind/`. On peut soit lancer `gestion-mutations-backend.exe` comme
> sidecar Tauri, soit cibler une fenêtre Tauri sur `http://127.0.0.1:8788`.

---

## Correspondance avec le code de vérification d'origine

Le code de vérification HMAC est **compatible** avec celui de Laravel
(`Mutation::genererCodeVerification`) : mêmes champs signés, `HMAC-SHA256`, clé
applicative décodée (format `base64:`). Voir `util::code_verification` et
`domain::finalize_validated_mutation`.

---

## État d'avancement

Implémenté (backend + frontend) : **setup, auth/RBAC, dashboard, communes,
projets, parcelles (+ attribution), mutations (+ validation/refus + code de
vérification), recherche, vérification publique**.

À compléter (cf. [SPEC.md](SPEC.md) §État d'avancement) :
- **Imports Excel** (parsing + matching) → crate `calamine`.
- **PDF de notification + QR code** → crate `qrcode` + moteur HTML→PDF.
- **Document templates** (placeholders), **annulations**, **rapports**,
  **export/import BDD**, gestion des **utilisateurs**.

Ces modules suivent exactement les règles décrites dans `SPEC.md`.
