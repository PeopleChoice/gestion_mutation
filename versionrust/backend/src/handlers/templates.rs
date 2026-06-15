use crate::auth::AuthUser;
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::{self, now, row_to_json, rows_to_json};
use axum::extract::{Path, State};
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};

#[derive(Debug, Deserialize)]
pub struct TemplateInput {
    pub nom: String,
    #[serde(default = "default_type")]
    pub r#type: String,
    pub entete_html: String,
    pub corps_html: String,
    #[serde(default)]
    pub pied_html: Option<String>,
    #[serde(default)]
    pub centre_fiscal: Option<String>,
    #[serde(default)]
    pub bureau: Option<String>,
    #[serde(default = "default_true")]
    pub actif: bool,
}
fn default_type() -> String { "notification_attribution".into() }
fn default_true() -> bool { true }

/// GET /api/templates (admin)
pub async fn list(user: AuthUser, State(state): State<AppState>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    let rows = sqlx::query("SELECT * FROM document_templates ORDER BY actif DESC, id DESC")
        .fetch_all(&pool)
        .await?;
    Ok(Json(rows_to_json(&rows)))
}

/// GET /api/templates/:id (admin)
pub async fn show(user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    let row = sqlx::query("SELECT * FROM document_templates WHERE id = ?")
        .bind(id)
        .fetch_optional(&pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Template introuvable.".into()))?;
    Ok(Json(row_to_json(&row)))
}

/// POST /api/templates (admin)
pub async fn store(user: AuthUser, State(state): State<AppState>, Json(input): Json<TemplateInput>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    sqlx::query(
        "INSERT INTO document_templates (nom, type, entete_html, corps_html, pied_html, centre_fiscal, bureau, actif, created_at, updated_at) \
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(&input.nom)
    .bind(&input.r#type)
    .bind(&input.entete_html)
    .bind(&input.corps_html)
    .bind(&input.pied_html)
    .bind(&input.centre_fiscal)
    .bind(&input.bureau)
    .bind(if input.actif { 1 } else { 0 })
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;
    let id = util::last_id(&pool, "document_templates").await?;
    Ok(Json(json!({ "id": id })))
}

/// PUT /api/templates/:id (admin)
pub async fn update(user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>, Json(input): Json<TemplateInput>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    let res = sqlx::query(
        "UPDATE document_templates SET nom=?, type=?, entete_html=?, corps_html=?, pied_html=?, centre_fiscal=?, bureau=?, actif=?, updated_at=? WHERE id=?",
    )
    .bind(&input.nom)
    .bind(&input.r#type)
    .bind(&input.entete_html)
    .bind(&input.corps_html)
    .bind(&input.pied_html)
    .bind(&input.centre_fiscal)
    .bind(&input.bureau)
    .bind(if input.actif { 1 } else { 0 })
    .bind(now())
    .bind(id)
    .execute(&pool)
    .await?;
    if res.rows_affected() == 0 {
        return Err(AppError::NotFound("Template introuvable.".into()));
    }
    Ok(Json(json!({ "ok": true })))
}

/// DELETE /api/templates/:id (admin)
pub async fn destroy(user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    sqlx::query("DELETE FROM document_templates WHERE id = ?")
        .bind(id)
        .execute(&pool)
        .await?;
    Ok(Json(json!({ "ok": true })))
}

/// POST /api/templates/:id/toggle (admin)
pub async fn toggle(user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    sqlx::query("UPDATE document_templates SET actif = CASE WHEN actif = 1 THEN 0 ELSE 1 END, updated_at = ? WHERE id = ?")
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;
    Ok(Json(json!({ "ok": true })))
}
