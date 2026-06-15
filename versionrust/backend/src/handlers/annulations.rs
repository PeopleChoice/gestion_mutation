use crate::auth::AuthUser;
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::{self, now, rows_to_json};
use axum::extract::{Path, State};
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};
use sqlx::Row;

#[derive(Debug, Deserialize)]
pub struct DemandeInput {
    pub motif: String,
}

/// POST /api/mutations/:id/annulation — demande d'annulation (tout utilisateur).
pub async fn demander(
    user: AuthUser,
    State(state): State<AppState>,
    Path(mutation_id): Path<i64>,
    Json(input): Json<DemandeInput>,
) -> AppResult<Json<Value>> {
    if input.motif.trim().len() < 3 {
        return Err(AppError::BadRequest("Motif requis.".into()));
    }
    let pool = state.db().await?;

    let exists: Option<(i64,)> = sqlx::query_as("SELECT id FROM mutations WHERE id = ?")
        .bind(mutation_id)
        .fetch_optional(&pool)
        .await?;
    if exists.is_none() {
        return Err(AppError::NotFound("Mutation introuvable.".into()));
    }

    sqlx::query(
        "INSERT INTO mutation_annulations (mutation_id, demande_par, statut, motif, created_at, updated_at) \
         VALUES (?, ?, 'en_attente', ?, ?, ?)",
    )
    .bind(mutation_id)
    .bind(user.id)
    .bind(input.motif.trim())
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;

    util::log_activity(&pool, Some(user.id), "demande_annulation", "mutation", &mutation_id.to_string()).await;
    Ok(Json(json!({ "ok": true })))
}

/// GET /api/annulations — annulations en attente (admin|gestionnaire).
pub async fn list(
    user: AuthUser,
    State(state): State<AppState>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire"])?;
    let pool = state.db().await?;

    let rows = sqlx::query(
        "SELECT a.*, m.numero_notification, pa.numero_lot, u.name AS demandeur_nom \
         FROM mutation_annulations a \
         JOIN mutations m ON m.id = a.mutation_id \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN users u ON u.id = a.demande_par \
         WHERE a.statut = 'en_attente' ORDER BY a.created_at DESC",
    )
    .fetch_all(&pool)
    .await?;

    Ok(Json(rows_to_json(&rows)))
}

#[derive(Debug, Deserialize)]
pub struct TraiterInput {
    pub action: String, // "approuver" | "rejeter"
    #[serde(default)]
    pub motif_rejet: Option<String>,
}

/// POST /api/annulations/:id/traiter (admin|gestionnaire)
pub async fn traiter(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<TraiterInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire"])?;
    let pool = state.db().await?;

    let row = sqlx::query("SELECT mutation_id, statut FROM mutation_annulations WHERE id = ?")
        .bind(id)
        .fetch_optional(&pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Annulation introuvable.".into()))?;

    let statut: String = row.try_get("statut")?;
    if statut != "en_attente" {
        return Err(AppError::BadRequest("Demande déjà traitée.".into()));
    }
    let mutation_id: i64 = row.try_get::<Option<i64>, _>("mutation_id").unwrap_or(None).unwrap_or(0);

    match input.action.as_str() {
        "approuver" => {
            // Remet l'ancien propriétaire sur la parcelle.
            let m = sqlx::query("SELECT parcelle_id, ancien_proprietaire_id FROM mutations WHERE id = ?")
                .bind(mutation_id)
                .fetch_one(&pool)
                .await?;
            let parcelle_id: i64 = m.try_get::<Option<i64>, _>("parcelle_id").unwrap_or(None).unwrap_or(0);
            let ancien: Option<i64> = m.try_get::<Option<i64>, _>("ancien_proprietaire_id").unwrap_or(None);

            sqlx::query("UPDATE parcelles SET proprietaire_id = ?, updated_at = ? WHERE id = ?")
                .bind(ancien)
                .bind(now())
                .bind(parcelle_id)
                .execute(&pool)
                .await?;

            sqlx::query("UPDATE mutations SET statut='annulee', updated_at=? WHERE id=?")
                .bind(now())
                .bind(mutation_id)
                .execute(&pool)
                .await?;

            sqlx::query("UPDATE mutation_annulations SET statut='approuvee', approuve_par=?, approuve_at=?, updated_at=? WHERE id=?")
                .bind(user.id)
                .bind(now())
                .bind(now())
                .bind(id)
                .execute(&pool)
                .await?;

            util::log_activity(&pool, Some(user.id), "annulation_approuvee", "mutation", &mutation_id.to_string()).await;
        }
        "rejeter" => {
            sqlx::query("UPDATE mutation_annulations SET statut='rejetee', motif_rejet=?, approuve_par=?, approuve_at=?, updated_at=? WHERE id=?")
                .bind(input.motif_rejet.as_deref().unwrap_or(""))
                .bind(user.id)
                .bind(now())
                .bind(now())
                .bind(id)
                .execute(&pool)
                .await?;
            util::log_activity(&pool, Some(user.id), "annulation_rejetee", "mutation", &mutation_id.to_string()).await;
        }
        _ => return Err(AppError::BadRequest("Action invalide.".into())),
    }

    Ok(Json(json!({ "ok": true })))
}
