use crate::auth::AuthUser;
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::{self, now, rows_to_json};
use axum::extract::{Path, Query, State};
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};

#[derive(Debug, Deserialize)]
pub struct ListQuery {
    #[serde(default)]
    pub q: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct CommuneInput {
    pub nom: String,
    #[serde(default)]
    pub departement: Option<String>,
    #[serde(default)]
    pub region: Option<String>,
}

/// GET /api/communes
pub async fn list(
    _user: AuthUser,
    State(state): State<AppState>,
    Query(q): Query<ListQuery>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let like = format!("%{}%", q.q.clone().unwrap_or_default());

    let rows = sqlx::query(
        "SELECT c.*, \
            (SELECT COUNT(*) FROM projets p WHERE p.commune_id = c.id) AS projets_count \
         FROM communes c \
         WHERE (? = '%%' OR c.nom LIKE ? OR c.departement LIKE ? OR c.region LIKE ?) \
         ORDER BY c.nom",
    )
    .bind(&like)
    .bind(&like)
    .bind(&like)
    .bind(&like)
    .fetch_all(&pool)
    .await?;

    Ok(Json(rows_to_json(&rows)))
}

/// POST /api/communes (admin)
pub async fn store(
    user: AuthUser,
    State(state): State<AppState>,
    Json(input): Json<CommuneInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    if input.nom.trim().is_empty() {
        return Err(AppError::BadRequest("Le nom est requis.".into()));
    }
    let pool = state.db().await?;

    sqlx::query("INSERT INTO communes (nom, departement, region, created_at, updated_at) VALUES (?, ?, ?, ?, ?)")
        .bind(input.nom.trim())
        .bind(&input.departement)
        .bind(&input.region)
        .bind(now())
        .bind(now())
        .execute(&pool)
        .await?;

    let id = util::last_id(&pool, "communes").await?;
    util::log_activity(&pool, Some(user.id), "commune_creee", "commune", &input.nom).await;
    Ok(Json(json!({ "id": id })))
}

/// PUT /api/communes/:id (admin)
pub async fn update(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<CommuneInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;

    let res = sqlx::query("UPDATE communes SET nom = ?, departement = ?, region = ?, updated_at = ? WHERE id = ?")
        .bind(input.nom.trim())
        .bind(&input.departement)
        .bind(&input.region)
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;

    if res.rows_affected() == 0 {
        return Err(AppError::NotFound("Commune introuvable.".into()));
    }
    Ok(Json(json!({ "ok": true })))
}

/// DELETE /api/communes/:id (admin) — refuse si la commune a des projets.
pub async fn destroy(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;

    let count: (i64,) = sqlx::query_as("SELECT COUNT(*) FROM projets WHERE commune_id = ?")
        .bind(id)
        .fetch_one(&pool)
        .await?;
    if count.0 > 0 {
        return Err(AppError::Conflict(
            "Impossible de supprimer : des projets sont rattachés à cette commune.".into(),
        ));
    }

    sqlx::query("DELETE FROM communes WHERE id = ?")
        .bind(id)
        .execute(&pool)
        .await?;
    util::log_activity(&pool, Some(user.id), "commune_supprimee", "commune", &id.to_string()).await;
    Ok(Json(json!({ "ok": true })))
}
