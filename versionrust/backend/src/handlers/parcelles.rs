use crate::auth::AuthUser;
use crate::domain;
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
    pub projet_id: Option<i64>,
    #[serde(default)]
    pub usage: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct ParcelleInput {
    pub numero_lot: String,
    pub projet_id: i64,
    #[serde(default)]
    pub superficie: Option<f64>,
    #[serde(default)]
    pub usage: Option<String>,
    #[serde(default)]
    pub ref_lettre: Option<String>,
    // Propriétaire initial (optionnel)
    #[serde(default)]
    pub civilite: Option<String>,
    #[serde(default)]
    pub prenom: Option<String>,
    #[serde(default)]
    pub nom: Option<String>,
    #[serde(default)]
    pub cni_passport: Option<String>,
    #[serde(default)]
    pub telephone: Option<String>,
}

/// GET /api/parcelles
pub async fn list(
    _user: AuthUser,
    State(state): State<AppState>,
    Query(q): Query<ListQuery>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let like = format!("%{}%", q.q.clone().unwrap_or_default());
    let projet = q.projet_id.unwrap_or(0);
    let usage = q.usage.clone().unwrap_or_default();

    let rows = sqlx::query(
        "SELECT pa.*, pj.nom AS projet_nom, pr.prenom AS proprietaire_prenom, pr.nom AS proprietaire_nom, \
                pr.cni_passport AS proprietaire_cni \
         FROM parcelles pa \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         LEFT JOIN proprietaires pr ON pr.id = pa.proprietaire_id \
         WHERE (? = '%%' OR pa.numero_lot LIKE ? OR pa.ref_lettre LIKE ? OR pr.nom LIKE ? OR pr.prenom LIKE ? OR pr.cni_passport LIKE ?) \
           AND (? = 0 OR pa.projet_id = ?) \
           AND (? = '' OR pa.`usage` = ?) \
         ORDER BY pa.created_at DESC LIMIT 200",
    )
    .bind(&like).bind(&like).bind(&like).bind(&like).bind(&like).bind(&like)
    .bind(projet).bind(projet)
    .bind(&usage).bind(&usage)
    .fetch_all(&pool)
    .await?;

    Ok(Json(rows_to_json(&rows)))
}

/// GET /api/parcelles/:id
pub async fn show(
    _user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let row = sqlx::query(
        "SELECT pa.*, pj.nom AS projet_nom, pr.prenom AS proprietaire_prenom, pr.nom AS proprietaire_nom \
         FROM parcelles pa \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         LEFT JOIN proprietaires pr ON pr.id = pa.proprietaire_id WHERE pa.id = ?",
    )
    .bind(id)
    .fetch_optional(&pool)
    .await?
    .ok_or_else(|| AppError::NotFound("Parcelle introuvable.".into()))?;

    let mutations = sqlx::query("SELECT * FROM mutations WHERE parcelle_id = ? ORDER BY created_at DESC")
        .bind(id)
        .fetch_all(&pool)
        .await?;

    Ok(Json(json!({ "parcelle": row_to_json(&row), "mutations": rows_to_json(&mutations) })))
}

/// POST /api/parcelles (admin|gestionnaire) — unicité (numero_lot, projet_id).
pub async fn store(
    user: AuthUser,
    State(state): State<AppState>,
    Json(input): Json<ParcelleInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire"])?;
    let pool = state.db().await?;

    if input.numero_lot.trim().is_empty() {
        return Err(AppError::BadRequest("Le numéro de lot est requis.".into()));
    }

    // Unicité (numero_lot, projet_id)
    let dup: Option<(i64,)> =
        sqlx::query_as("SELECT id FROM parcelles WHERE numero_lot = ? AND projet_id = ?")
            .bind(input.numero_lot.trim())
            .bind(input.projet_id)
            .fetch_optional(&pool)
            .await?;
    if dup.is_some() {
        return Err(AppError::Conflict(
            "Ce lot existe déjà dans ce projet.".into(),
        ));
    }

    // Propriétaire initial (si fourni)
    let proprietaire_id = if input.prenom.as_deref().unwrap_or("").trim().is_empty() {
        None
    } else {
        Some(
            domain::create_proprietaire(
                &pool,
                input.civilite.as_deref(),
                input.prenom.as_deref().unwrap_or("N/A"),
                input.nom.as_deref().unwrap_or("N/A"),
                input.cni_passport.as_deref(),
                None,
                None,
                None,
                input.telephone.as_deref(),
                None,
            )
            .await?,
        )
    };

    sqlx::query(
        "INSERT INTO parcelles (numero_lot, projet_id, proprietaire_id, ref_lettre, superficie, `usage`, date_attribution, created_at, updated_at) \
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(input.numero_lot.trim())
    .bind(input.projet_id)
    .bind(proprietaire_id)
    .bind(&input.ref_lettre)
    .bind(input.superficie.map(|v| v.to_string()))
    .bind(&input.usage)
    .bind(if proprietaire_id.is_some() { Some(util::today()) } else { None })
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;

    let id = util::last_id(&pool, "parcelles").await?;
    util::log_activity(&pool, Some(user.id), "parcelle_creee", "parcelle", input.numero_lot.trim()).await;
    Ok(Json(json!({ "id": id })))
}

/// PUT /api/parcelles/:id (admin|gestionnaire)
pub async fn update(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<ParcelleInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire"])?;
    let pool = state.db().await?;

    let res = sqlx::query(
        "UPDATE parcelles SET numero_lot = ?, projet_id = ?, superficie = ?, `usage` = ?, updated_at = ? WHERE id = ?",
    )
    .bind(input.numero_lot.trim())
    .bind(input.projet_id)
    .bind(input.superficie.map(|v| v.to_string()))
    .bind(&input.usage)
    .bind(now())
    .bind(id)
    .execute(&pool)
    .await?;

    if res.rows_affected() == 0 {
        return Err(AppError::NotFound("Parcelle introuvable.".into()));
    }
    Ok(Json(json!({ "ok": true })))
}

#[derive(Debug, Deserialize)]
pub struct AttribuerInput {
    pub civilite: Option<String>,
    pub prenom: String,
    pub nom: String,
    pub cni_passport: Option<String>,
    pub telephone: Option<String>,
    pub numero_notification: Option<String>,
    /// Mot de passe de forçage si la parcelle est déjà attribuée.
    #[serde(default)]
    pub force_password: Option<String>,
}

/// POST /api/parcelles/:id/attribuer (admin|gestionnaire)
/// Crée le nouveau propriétaire + une mutation validée (attribution) avec code.
pub async fn attribuer(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<AttribuerInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire"])?;
    if input.prenom.trim().is_empty() {
        return Err(AppError::BadRequest("Le prénom est requis.".into()));
    }
    let pool = state.db().await?;

    let parcelle = sqlx::query("SELECT id, proprietaire_id FROM parcelles WHERE id = ?")
        .bind(id)
        .fetch_optional(&pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Parcelle introuvable.".into()))?;

    use sqlx::Row;
    let ancien_id: Option<i64> = parcelle.try_get::<Option<i64>, _>("proprietaire_id").unwrap_or(None);

    // Forçage si déjà attribuée : exige le mot de passe configuré.
    if ancien_id.is_some() {
        let force_pwd = std::env::var("GM_ATTRIBUTION_FORCE_PASSWORD").unwrap_or_default();
        let provided = input.force_password.clone().unwrap_or_default();
        if force_pwd.is_empty() || provided != force_pwd {
            util::log_activity(&pool, Some(user.id), "attribution_force_refusee", "parcelle", &id.to_string()).await;
            return Err(AppError::Conflict(
                "Parcelle déjà attribuée : passez par une mutation, ou fournissez le mot de passe de forçage.".into(),
            ));
        }
    }

    // Nouveau propriétaire
    let nouveau_id = domain::create_proprietaire(
        &pool,
        input.civilite.as_deref(),
        input.prenom.trim(),
        input.nom.trim(),
        input.cni_passport.as_deref(),
        None, None, None,
        input.telephone.as_deref(),
        None,
    )
    .await?;

    // Numéro de notification
    let numero = match &input.numero_notification {
        Some(n) if !n.trim().is_empty() => n.trim().to_string(),
        _ => util::next_numero_notification(&pool).await?,
    };

    // Mutation validée
    sqlx::query(
        "INSERT INTO mutations (parcelle_id, ancien_proprietaire_id, nouveau_proprietaire_id, statut, numero_notification, date_mutation, validated_by, validated_at, created_at, updated_at) \
         VALUES (?, ?, ?, 'validee', ?, ?, ?, ?, ?, ?)",
    )
    .bind(id)
    .bind(ancien_id)
    .bind(nouveau_id)
    .bind(&numero)
    .bind(util::today())
    .bind(user.id)
    .bind(now())
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;

    let mutation_id = util::last_id(&pool, "mutations").await?;

    // Code de vérification + mise à jour du propriétaire de la parcelle.
    let app_key = state.config.read().await.app_key_bytes();
    let code = domain::finalize_validated_mutation(&pool, &app_key, mutation_id).await?;

    let action = if ancien_id.is_some() { "attribution_forcee" } else { "attribution" };
    util::log_activity(&pool, Some(user.id), action, "parcelle", &numero).await;

    Ok(Json(json!({ "mutation_id": mutation_id, "numero_notification": numero, "code_verification": code })))
}
