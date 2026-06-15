use crate::auth::AuthUser;
use crate::error::AppResult;
use crate::state::AppState;
use crate::util::rows_to_json;
use axum::extract::State;
use axum::Json;
use serde_json::{json, Value};

/// GET /api/dashboard
pub async fn index(_user: AuthUser, State(state): State<AppState>) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    let parcelles: (i64, i64) =
        sqlx::query_as("SELECT COUNT(*), COUNT(proprietaire_id) FROM parcelles")
            .fetch_one(&pool)
            .await?;

    let mutations: (i64, i64, i64, i64, i64) = sqlx::query_as(
        "SELECT COUNT(*), \
            COUNT(CASE WHEN statut='validee' THEN 1 END), \
            COUNT(CASE WHEN statut='refusee' THEN 1 END), \
            COUNT(CASE WHEN statut='en_attente' THEN 1 END), \
            COUNT(CASE WHEN statut='annulee' THEN 1 END) \
         FROM mutations",
    )
    .fetch_one(&pool)
    .await?;

    let annulations: (i64,) =
        sqlx::query_as("SELECT COUNT(*) FROM mutation_annulations WHERE statut='en_attente'")
            .fetch_one(&pool)
            .await?;

    let dernieres = sqlx::query(
        "SELECT m.id, m.numero_notification, m.statut, m.date_mutation, pa.numero_lot, pj.nom AS projet_nom \
         FROM mutations m JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         ORDER BY m.created_at DESC LIMIT 8",
    )
    .fetch_all(&pool)
    .await?;

    let top_projets = sqlx::query(
        "SELECT pj.id, pj.nom, pj.code, \
            (SELECT COUNT(*) FROM parcelles pa WHERE pa.projet_id = pj.id) AS parcelles_count, \
            (SELECT COUNT(*) FROM parcelles pa WHERE pa.projet_id = pj.id AND pa.proprietaire_id IS NOT NULL) AS attribuees_count \
         FROM projets pj ORDER BY parcelles_count DESC LIMIT 5",
    )
    .fetch_all(&pool)
    .await?;

    Ok(Json(json!({
        "parcelles": { "total": parcelles.0, "attribuees": parcelles.1, "sans_attributaire": parcelles.0 - parcelles.1 },
        "mutations": { "total": mutations.0, "validees": mutations.1, "refusees": mutations.2, "en_attente": mutations.3, "annulees": mutations.4 },
        "annulations_en_attente": annulations.0,
        "dernieres_mutations": rows_to_json(&dernieres),
        "top_projets": rows_to_json(&top_projets),
    })))
}
