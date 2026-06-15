mod auth;
mod config;
mod domain;
mod error;
mod handlers;
mod migrations;
mod seed;
mod state;
mod util;

use axum::routing::{delete, get, post, put};
use axum::Router;
use config::AppConfig;
use state::AppState;
use std::net::SocketAddr;
use tower_http::cors::{Any, CorsLayer};

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    dotenvy::dotenv().ok();
    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "info,sqlx=warn".into()),
        )
        .init();

    // Configuration (génère les clés au 1er lancement).
    let cfg = AppConfig::load_or_init()?;
    let state = AppState::new(cfg.clone());

    // Si déjà configuré, on (re)connecte la base au démarrage.
    if cfg.installed {
        if let Some(dbcfg) = &cfg.db {
            match state.initialize(dbcfg).await {
                Ok(_) => tracing::info!("Base de données connectée ({:?}).", dbcfg.driver),
                Err(e) => tracing::error!("Connexion DB impossible au démarrage : {e}"),
            }
        }
    } else {
        tracing::info!("Application non configurée — assistant /setup requis.");
    }

    let cors = CorsLayer::new()
        .allow_origin(Any)
        .allow_methods(Any)
        .allow_headers(Any);

    let api = Router::new()
        // Setup (public)
        .route("/setup/status", get(handlers::setup::status))
        .route("/setup/test", post(handlers::setup::test))
        .route("/setup", post(handlers::setup::store))
        // Auth
        .route("/login", post(handlers::auth::login))
        .route("/me", get(handlers::auth::me))
        // Dashboard
        .route("/dashboard", get(handlers::dashboard::index))
        // Communes
        .route("/communes", get(handlers::communes::list).post(handlers::communes::store))
        .route("/communes/:id", put(handlers::communes::update).delete(handlers::communes::destroy))
        // Projets
        .route("/projets", get(handlers::projets::list).post(handlers::projets::store))
        .route("/projets/:id", get(handlers::projets::show).put(handlers::projets::update))
        // Parcelles
        .route("/parcelles", get(handlers::parcelles::list).post(handlers::parcelles::store))
        .route("/parcelles/:id", get(handlers::parcelles::show).put(handlers::parcelles::update))
        .route("/parcelles/:id/attribuer", post(handlers::parcelles::attribuer))
        // Mutations
        .route("/mutations", get(handlers::mutations::list).post(handlers::mutations::store))
        .route("/mutations/:id", get(handlers::mutations::show))
        .route("/mutations/:id/valider", post(handlers::mutations::valider))
        .route("/mutations/:id/refuser", post(handlers::mutations::refuser))
        .route("/mutations/:id/notification", get(handlers::mutations::notification))
        .route("/mutations/:id/annulation", post(handlers::annulations::demander))
        // Annulations
        .route("/annulations", get(handlers::annulations::list))
        .route("/annulations/:id/traiter", post(handlers::annulations::traiter))
        // Templates de documents (admin)
        .route("/templates", get(handlers::templates::list).post(handlers::templates::store))
        .route("/templates/:id", get(handlers::templates::show).put(handlers::templates::update).delete(handlers::templates::destroy))
        .route("/templates/:id/toggle", post(handlers::templates::toggle))
        // Recherche
        .route("/recherche/rapide", get(handlers::recherche::rapide))
        // Vérification publique
        .route("/verification/:hash", get(handlers::verification::verifier));

    let mut app = Router::new().nest("/api", api);

    // Sert le frontend compilé s'il est présent (mode empaqueté).
    let dist = std::path::Path::new("../frontend/dist");
    if dist.exists() {
        app = app.fallback_service(
            tower_http::services::ServeDir::new(dist)
                .fallback(tower_http::services::ServeFile::new(dist.join("index.html"))),
        );
    }

    let app = app.layer(cors).with_state(state);

    let port: u16 = std::env::var("GM_PORT").ok().and_then(|p| p.parse().ok()).unwrap_or(8788);
    let addr = SocketAddr::from(([127, 0, 0, 1], port));
    tracing::info!("Backend Gestion Mutations en écoute sur http://{addr}");

    let listener = tokio::net::TcpListener::bind(addr).await?;
    axum::serve(listener, app).await?;
    Ok(())
}
