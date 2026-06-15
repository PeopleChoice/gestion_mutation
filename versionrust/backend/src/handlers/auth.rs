use crate::auth::{self, AuthUser};
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util;
use axum::extract::State;
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};
use sqlx::Row;

#[derive(Debug, Deserialize)]
pub struct LoginInput {
    pub email: String,
    pub password: String,
}

/// POST /api/login
pub async fn login(
    State(state): State<AppState>,
    Json(input): Json<LoginInput>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    let row = sqlx::query("SELECT id, name, email, password FROM users WHERE email = ?")
        .bind(&input.email)
        .fetch_optional(&pool)
        .await?;

    let row = match row {
        Some(r) => r,
        None => {
            util::log_activity(&pool, None, "connexion_echouee", "auth", &input.email).await;
            return Err(AppError::Unauthorized("Identifiants invalides.".into()));
        }
    };

    let id: i64 = row.try_get("id")?;
    let name: String = row.try_get("name")?;
    let email: String = row.try_get("email")?;
    let hash: String = row.try_get("password")?;

    if !auth::verify_password(&input.password, &hash) {
        util::log_activity(&pool, Some(id), "connexion_echouee", "auth", &email).await;
        return Err(AppError::Unauthorized("Identifiants invalides.".into()));
    }

    let roles = auth::load_roles(&pool, id).await?;
    let secret = state.config.read().await.jwt_secret.clone();
    let token = auth::issue_token(&secret, id, &name, &email, roles.clone())?;

    util::log_activity(&pool, Some(id), "connexion", "auth", &email).await;

    Ok(Json(json!({
        "token": token,
        "user": { "id": id, "name": name, "email": email, "roles": roles }
    })))
}

/// GET /api/me — profil de l'utilisateur courant.
pub async fn me(user: AuthUser) -> Json<Value> {
    Json(json!({
        "id": user.id,
        "name": user.name,
        "email": user.email,
        "roles": user.roles,
    }))
}
