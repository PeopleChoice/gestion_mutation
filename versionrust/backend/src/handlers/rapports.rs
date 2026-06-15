use crate::auth::AuthUser;
use crate::error::AppResult;
use crate::state::AppState;
use crate::util::rows_to_json;
use axum::extract::State;
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};

#[derive(Debug, Deserialize)]
pub struct RapportInput {
    #[serde(default)]
    pub projet_id: Option<i64>,
    #[serde(default)]
    pub statut: Option<String>,
    #[serde(default)]
    pub date_from: Option<String>, // YYYY-MM-DD
    #[serde(default)]
    pub date_to: Option<String>,
}

/// POST /api/rapports — génère un rapport filtré (mutations + stats).
pub async fn generer(
    user: AuthUser,
    State(state): State<AppState>,
    Json(input): Json<RapportInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire", "receveur"])?;
    let pool = state.db().await?;

    let projet = input.projet_id.unwrap_or(0);
    let statut = input.statut.clone().unwrap_or_default();
    let from = input.date_from.clone().unwrap_or_default();
    let to = input.date_to.clone().unwrap_or_default();

    // Comparaison de dates en texte ISO (YYYY-MM-DD) -> ordre lexicographique correct.
    let rows = sqlx::query(
        "SELECT m.numero_notification, m.statut, m.date_mutation, pa.numero_lot, \
                pj.nom AS projet_nom, no.prenom AS nouveau_prenom, no.nom AS nouveau_nom \
         FROM mutations m \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         LEFT JOIN proprietaires no ON no.id = m.nouveau_proprietaire_id \
         WHERE (? = 0 OR pa.projet_id = ?) \
           AND (? = '' OR m.statut = ?) \
           AND (? = '' OR m.date_mutation >= ?) \
           AND (? = '' OR m.date_mutation <= ?) \
         ORDER BY m.date_mutation DESC, m.id DESC",
    )
    .bind(projet).bind(projet)
    .bind(&statut).bind(&statut)
    .bind(&from).bind(&from)
    .bind(&to).bind(&to)
    .fetch_all(&pool)
    .await?;

    let data = rows_to_json(&rows);
    let arr = data.as_array().cloned().unwrap_or_default();
    let count = |s: &str| arr.iter().filter(|m| m.get("statut").and_then(|v| v.as_str()) == Some(s)).count();

    Ok(Json(json!({
        "mutations": data,
        "stats": {
            "total": arr.len(),
            "validees": count("validee"),
            "refusees": count("refusee"),
            "en_attente": count("en_attente"),
            "annulees": count("annulee"),
        }
    })))
}
