use crate::error::{AppError, AppResult};
use crate::util::{self, now};
use sqlx::any::AnyPool;
use sqlx::Row;

/// Finalise une mutation validée : calcule et enregistre son code de
/// vérification HMAC, puis met à jour le propriétaire actuel de la parcelle.
///
/// Reproduit `Mutation::genererCodeVerification` + mise à jour de la parcelle.
pub async fn finalize_validated_mutation(
    pool: &AnyPool,
    app_key: &[u8],
    mutation_id: i64,
) -> AppResult<String> {
    let row = sqlx::query(
        "SELECT m.id, m.numero_notification, m.date_mutation, m.nouveau_proprietaire_id, \
                m.parcelle_id, pa.numero_lot, pa.projet_id, pr.cni_passport \
         FROM mutations m \
         JOIN parcelles pa ON pa.id = m.parcelle_id \
         LEFT JOIN proprietaires pr ON pr.id = m.nouveau_proprietaire_id \
         WHERE m.id = ?",
    )
    .bind(mutation_id)
    .fetch_optional(pool)
    .await?
    .ok_or_else(|| AppError::NotFound("Mutation introuvable.".into()))?;

    let numero: String = row.try_get::<Option<String>, _>("numero_notification").unwrap_or(None).unwrap_or_default();
    let date_mut: String = row.try_get::<Option<String>, _>("date_mutation").unwrap_or(None).unwrap_or_default();
    let numero_lot: String = row.try_get::<Option<String>, _>("numero_lot").unwrap_or(None).unwrap_or_default();
    let projet_id: i64 = row.try_get::<Option<i64>, _>("projet_id").unwrap_or(None).unwrap_or(0);
    let nouveau_id: i64 = row.try_get::<Option<i64>, _>("nouveau_proprietaire_id").unwrap_or(None).unwrap_or(0);
    let cni: String = row.try_get::<Option<String>, _>("cni_passport").unwrap_or(None).unwrap_or_default();
    let parcelle_id: i64 = row.try_get::<Option<i64>, _>("parcelle_id").unwrap_or(None).unwrap_or(0);

    let data = vec![
        mutation_id.to_string(),
        numero,
        numero_lot,
        projet_id.to_string(),
        nouveau_id.to_string(),
        cni,
        date_mut,
    ];
    let code = util::code_verification(app_key, &data);

    sqlx::query("UPDATE mutations SET code_verification = ?, updated_at = ? WHERE id = ?")
        .bind(&code)
        .bind(now())
        .bind(mutation_id)
        .execute(pool)
        .await?;

    // Met à jour le propriétaire actuel de la parcelle.
    sqlx::query("UPDATE parcelles SET proprietaire_id = ?, updated_at = ? WHERE id = ?")
        .bind(nouveau_id)
        .bind(now())
        .bind(parcelle_id)
        .execute(pool)
        .await?;

    Ok(code)
}

/// Crée un propriétaire et renvoie son id.
#[allow(clippy::too_many_arguments)]
pub async fn create_proprietaire(
    pool: &AnyPool,
    civilite: Option<&str>,
    prenom: &str,
    nom: &str,
    cni_passport: Option<&str>,
    nin: Option<&str>,
    ninea: Option<&str>,
    type_piece: Option<&str>,
    telephone: Option<&str>,
    adresse: Option<&str>,
) -> AppResult<i64> {
    sqlx::query(
        "INSERT INTO proprietaires (civilite, prenom, nom, cni_passport, nin, ninea, type_piece, telephone, adresse, created_at, updated_at) \
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(civilite)
    .bind(prenom)
    .bind(nom)
    .bind(cni_passport)
    .bind(nin)
    .bind(ninea)
    .bind(type_piece)
    .bind(telephone)
    .bind(adresse)
    .bind(now())
    .bind(now())
    .execute(pool)
    .await?;

    util::last_id(pool, "proprietaires").await
}
