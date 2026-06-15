use crate::auth::AuthUser;
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::{self, now, row_to_json, rows_to_json};
use axum::extract::{Path, Query, State};
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};

#[derive(Debug, Deserialize)]
pub struct ListQuery {
    #[serde(default)]
    pub q: Option<String>,
    #[serde(default)]
    pub commune_id: Option<i64>,
}

#[derive(Debug, Deserialize)]
pub struct ProjetInput {
    pub nom: String,
    pub commune_id: i64,
    #[serde(default)]
    pub type_lotissement: Option<String>,
    #[serde(default)]
    pub description: Option<String>,
    #[serde(default)]
    pub latitude: Option<f64>,
    #[serde(default)]
    pub longitude: Option<f64>,
}

/// GET /api/projets
pub async fn list(
    _user: AuthUser,
    State(state): State<AppState>,
    Query(q): Query<ListQuery>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let like = format!("%{}%", q.q.clone().unwrap_or_default());
    let commune = q.commune_id.unwrap_or(0);

    let rows = sqlx::query(
        "SELECT p.*, c.nom AS commune_nom, \
            (SELECT COUNT(*) FROM parcelles pa WHERE pa.projet_id = p.id) AS parcelles_count \
         FROM projets p \
         LEFT JOIN communes c ON c.id = p.commune_id \
         WHERE (? = '%%' OR p.nom LIKE ? OR p.type_lotissement LIKE ? OR c.nom LIKE ?) \
           AND (? = 0 OR p.commune_id = ?) \
         ORDER BY p.created_at DESC",
    )
    .bind(&like)
    .bind(&like)
    .bind(&like)
    .bind(&like)
    .bind(commune)
    .bind(commune)
    .fetch_all(&pool)
    .await?;

    Ok(Json(rows_to_json(&rows)))
}

/// GET /api/projets/:id
pub async fn show(
    _user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    let row = sqlx::query(
        "SELECT p.*, c.nom AS commune_nom FROM projets p \
         LEFT JOIN communes c ON c.id = p.commune_id WHERE p.id = ?",
    )
    .bind(id)
    .fetch_optional(&pool)
    .await?
    .ok_or_else(|| AppError::NotFound("Projet introuvable.".into()))?;

    let parcelles = sqlx::query(
        "SELECT pa.*, pr.prenom AS proprietaire_prenom, pr.nom AS proprietaire_nom \
         FROM parcelles pa LEFT JOIN proprietaires pr ON pr.id = pa.proprietaire_id \
         WHERE pa.projet_id = ? ORDER BY CAST(pa.numero_lot AS INTEGER), pa.numero_lot",
    )
    .bind(id)
    .fetch_all(&pool)
    .await?;

    let stats: (i64, i64) = sqlx::query_as(
        "SELECT COUNT(*), COUNT(proprietaire_id) FROM parcelles WHERE projet_id = ?",
    )
    .bind(id)
    .fetch_one(&pool)
    .await?;

    Ok(Json(json!({
        "projet": row_to_json(&row),
        "parcelles": rows_to_json(&parcelles),
        "stats": { "total": stats.0, "attribuees": stats.1, "non_attribuees": stats.0 - stats.1 }
    })))
}

/// POST /api/projets
pub async fn store(
    _user: AuthUser,
    State(state): State<AppState>,
    Json(input): Json<ProjetInput>,
) -> AppResult<Json<Value>> {
    if input.nom.trim().is_empty() {
        return Err(AppError::BadRequest("Le nom est requis.".into()));
    }
    let pool = state.db().await?;

    // Vérifie la commune
    let commune: Option<(i64,)> = sqlx::query_as("SELECT id FROM communes WHERE id = ?")
        .bind(input.commune_id)
        .fetch_optional(&pool)
        .await?;
    if commune.is_none() {
        return Err(AppError::BadRequest("Commune inexistante.".into()));
    }

    let code = util::next_code_projet(&pool, &input.nom).await?;

    sqlx::query(
        "INSERT INTO projets (nom, code, type_lotissement, commune_id, description, latitude, longitude, created_at, updated_at) \
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(input.nom.trim())
    .bind(&code)
    .bind(&input.type_lotissement)
    .bind(input.commune_id)
    .bind(&input.description)
    .bind(input.latitude.map(|v| v.to_string()))
    .bind(input.longitude.map(|v| v.to_string()))
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;

    let id = util::last_id(&pool, "projets").await?;
    Ok(Json(json!({ "id": id, "code": code })))
}

/// PUT /api/projets/:id
pub async fn update(
    _user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<ProjetInput>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    let res = sqlx::query(
        "UPDATE projets SET nom = ?, type_lotissement = ?, commune_id = ?, description = ?, \
            latitude = ?, longitude = ?, updated_at = ? WHERE id = ?",
    )
    .bind(input.nom.trim())
    .bind(&input.type_lotissement)
    .bind(input.commune_id)
    .bind(&input.description)
    .bind(input.latitude.map(|v| v.to_string()))
    .bind(input.longitude.map(|v| v.to_string()))
    .bind(now())
    .bind(id)
    .execute(&pool)
    .await?;

    if res.rows_affected() == 0 {
        return Err(AppError::NotFound("Projet introuvable.".into()));
    }
    Ok(Json(json!({ "ok": true })))
}
