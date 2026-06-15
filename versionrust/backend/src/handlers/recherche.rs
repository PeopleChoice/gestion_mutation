use crate::auth::AuthUser;
use crate::error::AppResult;
use crate::state::AppState;
use crate::util::rows_to_json;
use axum::extract::{Query, State};
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};

#[derive(Debug, Deserialize)]
pub struct SearchQuery {
    #[serde(default)]
    pub q: String,
}

/// GET /api/recherche/rapide?q=... — autocomplétion multi-entités.
pub async fn rapide(
    _user: AuthUser,
    State(state): State<AppState>,
    Query(query): Query<SearchQuery>,
) -> AppResult<Json<Value>> {
    if query.q.trim().len() < 2 {
        return Ok(Json(json!({ "mutations": [], "parcelles": [], "proprietaires": [], "projets": [] })));
    }
    let pool = state.db().await?;
    let like = format!("%{}%", query.q.trim());

    let mutations = sqlx::query(
        "SELECT m.id, m.numero_notification, m.statut, pa.numero_lot FROM mutations m \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         WHERE m.numero_notification LIKE ? ORDER BY m.created_at DESC LIMIT 5",
    )
    .bind(&like)
    .fetch_all(&pool)
    .await?;

    let parcelles = sqlx::query(
        "SELECT pa.id, pa.numero_lot, pj.nom AS projet_nom FROM parcelles pa \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         WHERE pa.numero_lot LIKE ? LIMIT 5",
    )
    .bind(&like)
    .fetch_all(&pool)
    .await?;

    let proprietaires = sqlx::query(
        "SELECT id, prenom, nom, cni_passport FROM proprietaires \
         WHERE nom LIKE ? OR prenom LIKE ? OR cni_passport LIKE ? OR nin LIKE ? OR telephone LIKE ? LIMIT 5",
    )
    .bind(&like).bind(&like).bind(&like).bind(&like).bind(&like)
    .fetch_all(&pool)
    .await?;

    let projets = sqlx::query(
        "SELECT pj.id, pj.nom, pj.code, c.nom AS commune_nom FROM projets pj \
         LEFT JOIN communes c ON c.id = pj.commune_id \
         WHERE pj.nom LIKE ? OR c.nom LIKE ? LIMIT 3",
    )
    .bind(&like).bind(&like)
    .fetch_all(&pool)
    .await?;

    Ok(Json(json!({
        "mutations": rows_to_json(&mutations),
        "parcelles": rows_to_json(&parcelles),
        "proprietaires": rows_to_json(&proprietaires),
        "projets": rows_to_json(&projets),
    })))
}
