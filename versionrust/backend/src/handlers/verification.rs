use crate::error::AppResult;
use crate::state::AppState;
use axum::extract::{Path, State};
use axum::Json;
use serde_json::{json, Value};
use sqlx::Row;

/// GET /api/verification/:hash — vérification PUBLIQUE d'un document par son code.
///
/// N'expose que des données non sensibles (CNI tronquée), comme la page
/// publique du projet d'origine pour un visiteur non authentifié.
pub async fn verifier(
    State(state): State<AppState>,
    Path(hash): Path<String>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    let row = sqlx::query(
        "SELECT m.numero_notification, m.date_mutation, m.statut, \
                pa.numero_lot, pj.nom AS projet_nom, c.nom AS commune_nom, \
                no.prenom AS nouveau_prenom, no.nom AS nouveau_nom, no.cni_passport AS nouveau_cni \
         FROM mutations m \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         LEFT JOIN communes c ON c.id = pj.commune_id \
         LEFT JOIN proprietaires no ON no.id = m.nouveau_proprietaire_id \
         WHERE m.code_verification = ?",
    )
    .bind(&hash)
    .fetch_optional(&pool)
    .await?;

    let Some(row) = row else {
        return Ok(Json(json!({ "valide": false, "message": "Document introuvable ou code invalide." })));
    };

    // Masque la CNI : ne garde que les 4 derniers caractères.
    let cni: String = row.try_get::<Option<String>, _>("nouveau_cni").unwrap_or(None).unwrap_or_default();
    let cni_masquee = if cni.len() > 4 {
        format!("****{}", &cni[cni.len() - 4..])
    } else if cni.is_empty() {
        "N/A".into()
    } else {
        "****".into()
    };

    let get = |k: &str| row.try_get::<Option<String>, _>(k).unwrap_or(None).unwrap_or_default();

    Ok(Json(json!({
        "valide": true,
        "statut": get("statut"),
        "numero_notification": get("numero_notification"),
        "date_mutation": get("date_mutation"),
        "numero_lot": get("numero_lot"),
        "projet": get("projet_nom"),
        "commune": get("commune_nom"),
        "beneficiaire": format!("{} {}", get("nouveau_prenom"), get("nouveau_nom")).trim().to_string(),
        "cni": cni_masquee,
    })))
}
