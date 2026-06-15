use crate::auth::AuthUser;
use crate::domain;
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::{self, now, row_to_json, rows_to_json};
use axum::extract::{Path, Query, State};
use axum::Json;
use serde::Deserialize;
use serde_json::{json, Value};
use sqlx::Row;

#[derive(Debug, Deserialize)]
pub struct ListQuery {
    #[serde(default)]
    pub q: Option<String>,
    #[serde(default)]
    pub statut: Option<String>,
    #[serde(default)]
    pub projet_id: Option<i64>,
    #[serde(default)]
    pub show_refusees: Option<bool>,
}

/// GET /api/mutations
pub async fn list(
    _user: AuthUser,
    State(state): State<AppState>,
    Query(q): Query<ListQuery>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let like = format!("%{}%", q.q.clone().unwrap_or_default());
    let statut = q.statut.clone().unwrap_or_default();
    let projet = q.projet_id.unwrap_or(0);
    let show_refusees = q.show_refusees.unwrap_or(false);

    let rows = sqlx::query(
        "SELECT m.*, pa.numero_lot, pj.nom AS projet_nom, \
                an.prenom AS ancien_prenom, an.nom AS ancien_nom, \
                no.prenom AS nouveau_prenom, no.nom AS nouveau_nom \
         FROM mutations m \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         LEFT JOIN proprietaires an ON an.id = m.ancien_proprietaire_id \
         LEFT JOIN proprietaires no ON no.id = m.nouveau_proprietaire_id \
         WHERE (? = '%%' OR m.numero_notification LIKE ? OR pa.numero_lot LIKE ? OR no.nom LIKE ? OR no.prenom LIKE ?) \
           AND (? = '' OR m.statut = ?) \
           AND (? = 0 OR pa.projet_id = ?) \
           AND (? = 1 OR m.statut <> 'refusee') \
         ORDER BY m.created_at DESC LIMIT 200",
    )
    .bind(&like).bind(&like).bind(&like).bind(&like).bind(&like)
    .bind(&statut).bind(&statut)
    .bind(projet).bind(projet)
    .bind(if show_refusees { 1 } else { 0 })
    .fetch_all(&pool)
    .await?;

    let stats: (i64, i64, i64, i64) = sqlx::query_as(
        "SELECT COUNT(*), \
            COUNT(CASE WHEN statut='en_attente' THEN 1 END), \
            COUNT(CASE WHEN statut='validee' THEN 1 END), \
            COUNT(CASE WHEN statut='refusee' THEN 1 END) \
         FROM mutations",
    )
    .fetch_one(&pool)
    .await?;

    Ok(Json(json!({
        "data": rows_to_json(&rows),
        "stats": { "total": stats.0, "en_attente": stats.1, "validees": stats.2, "refusees": stats.3 }
    })))
}

/// GET /api/mutations/:id
pub async fn show(
    _user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let row = sqlx::query(
        "SELECT m.*, pa.numero_lot, pj.nom AS projet_nom, c.nom AS commune_nom, \
                an.prenom AS ancien_prenom, an.nom AS ancien_nom, \
                no.prenom AS nouveau_prenom, no.nom AS nouveau_nom, no.cni_passport AS nouveau_cni \
         FROM mutations m \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         LEFT JOIN communes c ON c.id = pj.commune_id \
         LEFT JOIN proprietaires an ON an.id = m.ancien_proprietaire_id \
         LEFT JOIN proprietaires no ON no.id = m.nouveau_proprietaire_id \
         WHERE m.id = ?",
    )
    .bind(id)
    .fetch_optional(&pool)
    .await?
    .ok_or_else(|| AppError::NotFound("Mutation introuvable.".into()))?;

    Ok(Json(row_to_json(&row)))
}

#[derive(Debug, Deserialize)]
pub struct MutationInput {
    pub parcelle_id: i64,
    pub prenom: String,
    pub nom: String,
    #[serde(default)]
    pub civilite: Option<String>,
    #[serde(default)]
    pub cni_passport: Option<String>,
    #[serde(default)]
    pub telephone: Option<String>,
    #[serde(default)]
    pub numero_notification: Option<String>,
    #[serde(default)]
    pub observation: Option<String>,
}

/// POST /api/mutations — création manuelle d'une mutation (transfert).
pub async fn store(
    user: AuthUser,
    State(state): State<AppState>,
    Json(input): Json<MutationInput>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    // La parcelle doit avoir un propriétaire actuel (on ne mute pas une parcelle vierge).
    let parcelle = sqlx::query("SELECT id, proprietaire_id FROM parcelles WHERE id = ?")
        .bind(input.parcelle_id)
        .fetch_optional(&pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Parcelle introuvable.".into()))?;

    let ancien_id: Option<i64> = parcelle.try_get::<Option<i64>, _>("proprietaire_id").unwrap_or(None);
    if ancien_id.is_none() {
        return Err(AppError::BadRequest(
            "Cette parcelle n'a pas de propriétaire : utilisez « Attribuer » d'abord.".into(),
        ));
    }

    if input.prenom.trim().is_empty() {
        return Err(AppError::BadRequest("Le prénom du nouveau propriétaire est requis.".into()));
    }

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

    // Les non-admin créent en attente ; l'admin valide directement.
    let is_admin = user.has_role("admin");
    let statut = if is_admin { "validee" } else { "en_attente" };

    let numero = match &input.numero_notification {
        Some(n) if !n.trim().is_empty() => n.trim().to_string(),
        _ => util::next_numero_notification(&pool).await?,
    };

    sqlx::query(
        "INSERT INTO mutations (parcelle_id, ancien_proprietaire_id, nouveau_proprietaire_id, statut, numero_notification, date_mutation, observation, validated_by, validated_at, created_at, updated_at) \
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(input.parcelle_id)
    .bind(ancien_id)
    .bind(nouveau_id)
    .bind(statut)
    .bind(&numero)
    .bind(util::today())
    .bind(&input.observation)
    .bind(if is_admin { Some(user.id) } else { None })
    .bind(if is_admin { Some(now()) } else { None })
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;

    let mutation_id = util::last_id(&pool, "mutations").await?;

    let mut code = None;
    if is_admin {
        let app_key = state.config.read().await.app_key_bytes();
        code = Some(domain::finalize_validated_mutation(&pool, &app_key, mutation_id).await?);
    }

    util::log_activity(&pool, Some(user.id), "mutation_creee_manuellement", "mutation", &numero).await;
    Ok(Json(json!({ "id": mutation_id, "statut": statut, "numero_notification": numero, "code_verification": code })))
}

/// POST /api/mutations/:id/valider (admin)
pub async fn valider(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;

    let statut: Option<(String,)> = sqlx::query_as("SELECT statut FROM mutations WHERE id = ?")
        .bind(id)
        .fetch_optional(&pool)
        .await?;
    let statut = statut.ok_or_else(|| AppError::NotFound("Mutation introuvable.".into()))?.0;
    if statut != "en_attente" {
        return Err(AppError::BadRequest("Seules les mutations en attente peuvent être validées.".into()));
    }

    sqlx::query("UPDATE mutations SET statut='validee', validated_by=?, validated_at=?, updated_at=? WHERE id=?")
        .bind(user.id)
        .bind(now())
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;

    let app_key = state.config.read().await.app_key_bytes();
    let code = domain::finalize_validated_mutation(&pool, &app_key, id).await?;
    util::log_activity(&pool, Some(user.id), "mutation_validee", "mutation", &id.to_string()).await;
    Ok(Json(json!({ "ok": true, "code_verification": code })))
}

/// GET /api/mutations/:id/notification — HTML imprimable (avec QR) de la notification.
pub async fn notification(
    _user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    let row = sqlx::query(
        "SELECT m.numero_notification, m.date_mutation, m.code_verification, \
                pa.numero_lot, pj.nom AS projet_nom, c.nom AS commune_nom, \
                no.civilite, no.prenom, no.nom, no.cni_passport \
         FROM mutations m \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN projets pj ON pj.id = pa.projet_id \
         LEFT JOIN communes c ON c.id = pj.commune_id \
         LEFT JOIN proprietaires no ON no.id = m.nouveau_proprietaire_id \
         WHERE m.id = ?",
    )
    .bind(id)
    .fetch_optional(&pool)
    .await?
    .ok_or_else(|| AppError::NotFound("Mutation introuvable.".into()))?;

    let g = |k: &str| row.try_get::<Option<String>, _>(k).unwrap_or(None).unwrap_or_default();
    let code = g("code_verification");
    if code.is_empty() {
        return Err(AppError::BadRequest("Cette mutation n'est pas validée (pas de code).".into()));
    }

    // Template actif
    let tpl = sqlx::query(
        "SELECT entete_html, corps_html, pied_html, centre_fiscal, bureau \
         FROM document_templates WHERE type='notification_attribution' AND actif=1 \
         ORDER BY id LIMIT 1",
    )
    .fetch_optional(&pool)
    .await
    .ok()
    .flatten();

    let (entete, corps, pied, centre, bureau) = match &tpl {
        Some(t) => (
            t.try_get::<Option<String>, _>("entete_html").unwrap_or(None).unwrap_or_default(),
            t.try_get::<Option<String>, _>("corps_html").unwrap_or(None).unwrap_or_default(),
            t.try_get::<Option<String>, _>("pied_html").unwrap_or(None).unwrap_or_default(),
            t.try_get::<Option<String>, _>("centre_fiscal").unwrap_or(None).unwrap_or_default(),
            t.try_get::<Option<String>, _>("bureau").unwrap_or(None).unwrap_or_default(),
        ),
        None => (String::new(), "<p>{{nom_complet_nouveau}} — lot {{numero_lot}}</p>".into(), String::new(), String::new(), String::new()),
    };

    // nom complet (Société -> prénom seul)
    let civilite = g("civilite");
    let nom_complet = if civilite == "Société" {
        g("prenom")
    } else {
        format!("{} {} {}", civilite, g("prenom"), g("nom")).trim().to_string()
    };

    let qr = util::qr_data_uri(&util::verification_url(&code))?;

    let mut html = format!("{entete}{corps}{pied}");
    let repl: [(&str, String); 10] = [
        ("{{numero_notification}}", g("numero_notification")),
        ("{{date_mutation}}", g("date_mutation")),
        ("{{numero_lot}}", g("numero_lot")),
        ("{{nom_projet}}", g("projet_nom")),
        ("{{commune}}", g("commune_nom")),
        ("{{nom_complet_nouveau}}", nom_complet),
        ("{{cni_nouveau}}", g("cni_passport")),
        ("{{centre_fiscal}}", centre),
        ("{{bureau}}", bureau),
        ("{{code_verification}}", code.clone()),
    ];
    for (k, v) in repl {
        html = html.replace(k, &v);
    }
    html = html.replace("{{qr_code}}", &qr);

    Ok(Json(json!({ "html": html, "code_verification": code })))
}

#[derive(Debug, Deserialize)]
pub struct RefusInput {
    pub motif_refus: String,
}

/// POST /api/mutations/:id/refuser (admin)
pub async fn refuser(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<RefusInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    if input.motif_refus.trim().len() < 3 {
        return Err(AppError::BadRequest("Le motif de refus est trop court.".into()));
    }
    let pool = state.db().await?;

    let res = sqlx::query("UPDATE mutations SET statut='refusee', motif_refus=?, updated_at=? WHERE id=? AND statut='en_attente'")
        .bind(input.motif_refus.trim())
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;

    if res.rows_affected() == 0 {
        return Err(AppError::BadRequest("Mutation introuvable ou déjà traitée.".into()));
    }
    util::log_activity(&pool, Some(user.id), "mutation_refusee", "mutation", &id.to_string()).await;
    Ok(Json(json!({ "ok": true })))
}
