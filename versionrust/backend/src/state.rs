use crate::config::{AppConfig, DbConfig, DbDriver};
use crate::error::{AppError, AppResult};
use crate::{migrations, seed};
use sqlx::any::{AnyPool, AnyPoolOptions};
use std::sync::Arc;
use tokio::sync::RwLock;

/// État partagé de l'application (config + pool DB initialisé après /setup).
#[derive(Clone)]
pub struct AppState {
    pub config: Arc<RwLock<AppConfig>>,
    db: Arc<RwLock<Option<AnyPool>>>,
    driver: Arc<RwLock<Option<DbDriver>>>,
}

impl AppState {
    pub fn new(config: AppConfig) -> Self {
        Self {
            config: Arc::new(RwLock::new(config)),
            db: Arc::new(RwLock::new(None)),
            driver: Arc::new(RwLock::new(None)),
        }
    }

    /// Pool DB courant ou 503 si l'app n'est pas encore configurée.
    pub async fn db(&self) -> AppResult<AnyPool> {
        self.db
            .read()
            .await
            .clone()
            .ok_or(AppError::NotConfigured)
    }

    pub async fn driver(&self) -> AppResult<DbDriver> {
        self.driver
            .read()
            .await
            .clone()
            .ok_or(AppError::NotConfigured)
    }

    /// Connecte le pool pour une config DB donnée (sans migrer/seeder).
    pub async fn connect(dbcfg: &DbConfig) -> anyhow::Result<AnyPool> {
        // Indispensable pour activer les drivers du pool "Any".
        sqlx::any::install_default_drivers();
        let pool = AnyPoolOptions::new()
            .max_connections(5)
            .connect(&dbcfg.connect_url())
            .await?;
        Ok(pool)
    }

    /// Initialise complètement la base : connexion + migrations + seed,
    /// puis stocke le pool dans l'état. Appelé par l'assistant /setup et au
    /// démarrage si la config existe déjà.
    pub async fn initialize(&self, dbcfg: &DbConfig) -> anyhow::Result<()> {
        let pool = Self::connect(dbcfg).await?;
        migrations::run(&pool, &dbcfg.driver).await?;
        {
            let cfg = self.config.read().await;
            seed::run(&pool, &cfg).await?;
        }
        *self.db.write().await = Some(pool);
        *self.driver.write().await = Some(dbcfg.driver.clone());
        Ok(())
    }
}
