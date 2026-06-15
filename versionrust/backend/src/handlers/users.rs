use crate::auth::{hash_password, AuthUser};
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::{self, now};
use axum::extract::{Path, State};
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};
use sqlx::Row;

#[derive(Debug, Deserialize)]
pub struct UserInput {
    pub name: String,
    pub email: String,
    #[serde(default)]
    pub password: Option<String>,
    #[serde(default)]
    pub roles: Vec<String>,
}

#[derive(Debug, Deserialize)]
pub struct PasswordInput {
    pub password: String,
}

/// GET /api/roles — liste des rôles (admin).
pub async fn roles(user: AuthUser, State(state): State<AppState>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    let rows = sqlx::query("SELECT name FROM roles ORDER BY name")
        .fetch_all(&pool)
        .await?;
    let names: Vec<String> = rows
        .iter()
        .filter_map(|r| r.try_get::<String, _>("name").ok())
        .collect();
    Ok(Json(json!(names)))
}

/// GET /api/users — liste des utilisateurs avec leurs rôles (admin).
pub async fn list(user: AuthUser, State(state): State<AppState>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    let rows = sqlx::query("SELECT id, name, email FROM users ORDER BY name")
        .fetch_all(&pool)
        .await?;

    let mut out = Vec::new();
    for r in &rows {
        let id: i64 = r.try_get("id")?;
        let roles = crate::auth::load_roles(&pool, id).await?;
        out.push(json!({
            "id": id,
            "name": r.try_get::<String, _>("name").unwrap_or_default(),
            "email": r.try_get::<String, _>("email").unwrap_or_default(),
            "roles": roles,
        }));
    }
    Ok(Json(json!(out)))
}

/// Remplace les rôles d'un utilisateur.
async fn set_roles(pool: &sqlx::any::AnyPool, user_id: i64, roles: &[String]) -> AppResult<()> {
    sqlx::query("DELETE FROM user_roles WHERE user_id = ?")
        .bind(user_id)
        .execute(pool)
        .await?;
    for role in roles {
        let rid: Option<(i64,)> = sqlx::query_as("SELECT id FROM roles WHERE name = ?")
            .bind(role)
            .fetch_optional(pool)
            .await?;
        if let Some((rid,)) = rid {
            sqlx::query("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")
                .bind(user_id)
                .bind(rid)
                .execute(pool)
                .await?;
        }
    }
    Ok(())
}

/// POST /api/users (admin)
pub async fn store(user: AuthUser, State(state): State<AppState>, Json(input): Json<UserInput>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;

    if input.email.trim().is_empty() || input.name.trim().is_empty() {
        return Err(AppError::BadRequest("Nom et email requis.".into()));
    }
    let dup: Option<(i64,)> = sqlx::query_as("SELECT id FROM users WHERE email = ?")
        .bind(input.email.trim())
        .fetch_optional(&pool)
        .await?;
    if dup.is_some() {
        return Err(AppError::Conflict("Cet email est déjà utilisé.".into()));
    }

    let password = input.password.clone().unwrap_or_else(|| "motdepasse".into());
    let hash = hash_password(&password)?;

    sqlx::query("INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)")
        .bind(input.name.trim())
        .bind(input.email.trim())
        .bind(&hash)
        .bind(now())
        .bind(now())
        .execute(&pool)
        .await?;
    let id = util::last_id(&pool, "users").await?;
    set_roles(&pool, id, &input.roles).await?;

    util::log_activity(&pool, Some(user.id), "utilisateur_cree", "auth", input.email.trim()).await;
    Ok(Json(json!({ "id": id })))
}

/// PUT /api/users/:id (admin)
pub async fn update(user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>, Json(input): Json<UserInput>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;

    let res = sqlx::query("UPDATE users SET name = ?, email = ?, updated_at = ? WHERE id = ?")
        .bind(input.name.trim())
        .bind(input.email.trim())
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;
    if res.rows_affected() == 0 {
        return Err(AppError::NotFound("Utilisateur introuvable.".into()));
    }
    set_roles(&pool, id, &input.roles).await?;
    Ok(Json(json!({ "ok": true })))
}

/// POST /api/users/:id/password (admin)
pub async fn reset_password(user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>, Json(input): Json<PasswordInput>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    if input.password.len() < 6 {
        return Err(AppError::BadRequest("Mot de passe trop court (min. 6).".into()));
    }
    let pool = state.db().await?;
    let hash = hash_password(&input.password)?;
    sqlx::query("UPDATE users SET password = ?, updated_at = ? WHERE id = ?")
        .bind(&hash)
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;
    Ok(Json(json!({ "ok": true })))
}

/// DELETE /api/users/:id (admin) — interdit de se supprimer soi-même.
pub async fn destroy(user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    if id == user.id {
        return Err(AppError::BadRequest("Vous ne pouvez pas supprimer votre propre compte.".into()));
    }
    let pool = state.db().await?;
    sqlx::query("DELETE FROM user_roles WHERE user_id = ?").bind(id).execute(&pool).await?;
    sqlx::query("DELETE FROM users WHERE id = ?").bind(id).execute(&pool).await?;
    Ok(Json(json!({ "ok": true })))
}
