use crate::config::{DbConfig, DbDriver};
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use axum::extract::State;
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};

#[derive(Debug, Deserialize)]
pub struct SetupInput {
    pub driver: String, // "mysql" | "sqlite"
    #[serde(default)]
    pub host: String,
    #[serde(default)]
    pub port: Option<u16>,
    #[serde(default)]
    pub database: String,
    #[serde(default)]
    pub username: String,
    #[serde(default)]
    pub password: String,
}

impl SetupInput {
    fn to_dbconfig(&self) -> AppResult<DbConfig> {
        let driver = match self.driver.as_str() {
            "mysql" => DbDriver::Mysql,
            "sqlite" => DbDriver::Sqlite,
            other => return Err(AppError::BadRequest(format!("Driver inconnu : {other}"))),
        };

        let sqlite_path = crate::config::AppConfig::data_dir()
            .join("database.sqlite")
            .to_string_lossy()
            .to_string();

        if driver == DbDriver::Mysql {
            if self.database.is_empty() {
                return Err(AppError::BadRequest("Nom de base requis".into()));
            }
            if self.username.is_empty() {
                return Err(AppError::BadRequest("Utilisateur requis".into()));
            }
        }

        Ok(DbConfig {
            driver,
            host: self.host.clone(),
            port: self.port.unwrap_or(3306),
            database: self.database.clone(),
            username: self.username.clone(),
            password: self.password.clone(),
            sqlite_path,
        })
    }
}

/// GET /api/setup/status — l'application est-elle configurée ?
pub async fn status(State(state): State<AppState>) -> Json<Value> {
    let cfg = state.config.read().await;
    Json(json!({ "installed": cfg.installed }))
}

/// POST /api/setup/test — teste la connexion sans rien écrire.
pub async fn test(
    State(_state): State<AppState>,
    Json(input): Json<SetupInput>,
) -> AppResult<Json<Value>> {
    let dbcfg = input.to_dbconfig()?;
    match AppState::connect(&dbcfg).await {
        Ok(pool) => {
            // Vérifie qu'une requête simple passe.
            sqlx::query("SELECT 1").execute(&pool).await
                .map_err(|e| AppError::BadRequest(format!("Connexion établie mais requête échouée : {e}")))?;
            Ok(Json(json!({ "ok": true, "message": "Connexion réussie." })))
        }
        Err(e) => Err(AppError::BadRequest(format!("Échec : {e}"))),
    }
}

/// POST /api/setup — enregistre la config, migre, seed, marque installé.
pub async fn store(
    State(state): State<AppState>,
    Json(input): Json<SetupInput>,
) -> AppResult<Json<Value>> {
    {
        // Refus si déjà installé.
        if state.config.read().await.installed {
            return Err(AppError::BadRequest("Application déjà configurée.".into()));
        }
    }

    let dbcfg = input.to_dbconfig()?;

    // Connexion + migrations + seed + stockage du pool.
    state
        .initialize(&dbcfg)
        .await
        .map_err(|e| AppError::BadRequest(format!("Initialisation impossible : {e}")))?;

    // Persiste la config.
    {
        let mut cfg = state.config.write().await;
        cfg.db = Some(dbcfg);
        cfg.installed = true;
        cfg.save().map_err(|e| AppError::Internal(e.to_string()))?;
    }

    Ok(Json(json!({ "ok": true, "message": "Configuration terminée." })))
}
