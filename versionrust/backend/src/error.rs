use axum::http::StatusCode;
use axum::response::{IntoResponse, Response};
use axum::Json;
use serde_json::json;

/// Erreur applicative unifiée -> réponse HTTP JSON.
#[derive(Debug)]
pub enum AppError {
    /// 400 — validation / entrée invalide
    BadRequest(String),
    /// 401 — non authentifié
    Unauthorized(String),
    /// 403 — authentifié mais non autorisé (rôle insuffisant)
    Forbidden(String),
    /// 404 — ressource absente
    NotFound(String),
    /// 409 — conflit (ex: unicité numero_lot/projet)
    Conflict(String),
    /// 503 — application pas encore configurée (assistant /setup requis)
    NotConfigured,
    /// 500 — erreur interne
    Internal(String),
}

impl std::fmt::Display for AppError {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            AppError::BadRequest(m) => write!(f, "{m}"),
            AppError::Unauthorized(m) => write!(f, "{m}"),
            AppError::Forbidden(m) => write!(f, "{m}"),
            AppError::NotFound(m) => write!(f, "{m}"),
            AppError::Conflict(m) => write!(f, "{m}"),
            AppError::NotConfigured => write!(f, "Application non configurée"),
            AppError::Internal(m) => write!(f, "{m}"),
        }
    }
}

impl std::error::Error for AppError {}

impl From<sqlx::Error> for AppError {
    fn from(e: sqlx::Error) -> Self {
        match e {
            sqlx::Error::RowNotFound => AppError::NotFound("Ressource introuvable".into()),
            other => AppError::Internal(format!("Erreur base de données : {other}")),
        }
    }
}

impl From<anyhow::Error> for AppError {
    fn from(e: anyhow::Error) -> Self {
        AppError::Internal(e.to_string())
    }
}

impl IntoResponse for AppError {
    fn into_response(self) -> Response {
        let (status, code) = match &self {
            AppError::BadRequest(_) => (StatusCode::BAD_REQUEST, "bad_request"),
            AppError::Unauthorized(_) => (StatusCode::UNAUTHORIZED, "unauthorized"),
            AppError::Forbidden(_) => (StatusCode::FORBIDDEN, "forbidden"),
            AppError::NotFound(_) => (StatusCode::NOT_FOUND, "not_found"),
            AppError::Conflict(_) => (StatusCode::CONFLICT, "conflict"),
            AppError::NotConfigured => (StatusCode::SERVICE_UNAVAILABLE, "not_configured"),
            AppError::Internal(_) => (StatusCode::INTERNAL_SERVER_ERROR, "internal"),
        };
        let body = Json(json!({ "error": code, "message": self.to_string() }));
        (status, body).into_response()
    }
}

pub type AppResult<T> = Result<T, AppError>;
