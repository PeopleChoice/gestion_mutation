use crate::error::{AppError, AppResult};
use serde_json::{json, Value};
use sqlx::any::{AnyPool, AnyRow};
use sqlx::{Column, Row};

/// Convertit une ligne SQL générique en objet JSON (clé = nom de colonne).
/// Tente i64, puis f64, puis String ; NULL -> null.
pub fn row_to_json(row: &AnyRow) -> Value {
    let mut map = serde_json::Map::new();
    for col in row.columns() {
        map.insert(col.name().to_string(), column_value(row, col.ordinal()));
    }
    Value::Object(map)
}

fn column_value(row: &AnyRow, i: usize) -> Value {
    if let Ok(v) = row.try_get::<Option<i64>, _>(i) {
        return v.map(|x| json!(x)).unwrap_or(Value::Null);
    }
    // MySQL renvoie les colonnes INTEGER en i32 (SQLite : i64).
    if let Ok(v) = row.try_get::<Option<i32>, _>(i) {
        return v.map(|x| json!(x)).unwrap_or(Value::Null);
    }
    if let Ok(v) = row.try_get::<Option<f64>, _>(i) {
        return v.map(|x| json!(x)).unwrap_or(Value::Null);
    }
    if let Ok(v) = row.try_get::<Option<String>, _>(i) {
        return v.map(|x| json!(x)).unwrap_or(Value::Null);
    }
    Value::Null
}

/// Convertit une liste de lignes en tableau JSON.
pub fn rows_to_json(rows: &[AnyRow]) -> Value {
    Value::Array(rows.iter().map(row_to_json).collect())
}

/// Dernier id inséré dans une table (approche MAX(id), adaptée au mono-poste).
pub async fn last_id(pool: &AnyPool, table: &str) -> AppResult<i64> {
    let row = sqlx::query(&format!("SELECT MAX(id) AS id FROM {table}"))
        .fetch_one(pool)
        .await?;
    Ok(row.try_get::<Option<i64>, _>("id").unwrap_or(None).unwrap_or(0))
}

/// Horodatage ISO-8601 courant (UTC), stocké tel quel dans la base.
pub fn now() -> String {
    chrono::Utc::now().format("%Y-%m-%d %H:%M:%S").to_string()
}

/// Date du jour (Y-m-d).
pub fn today() -> String {
    chrono::Utc::now().format("%Y-%m-%d").to_string()
}

/// Calcule le code de vérification HMAC-SHA256 d'une mutation.
///
/// Reproduit `Mutation::genererCodeVerification` du projet Laravel :
/// données signées = id | numero_notification | numero_lot | projet_id |
/// nouveau_proprietaire_id | cni_passport | date_mutation, jointes par `|`,
/// signées avec la clé applicative décodée. Résultat : 64 caractères hex.
pub fn code_verification(app_key: &[u8], data_fields: &[String]) -> String {
    use hmac::{Hmac, Mac};
    use sha2::Sha256;
    type HmacSha256 = Hmac<Sha256>;

    let data = data_fields.join("|");
    let mut mac = HmacSha256::new_from_slice(app_key).expect("clé HMAC");
    mac.update(data.as_bytes());
    hex::encode(mac.finalize().into_bytes())
}

/// Génère le prochain numéro de notification (7 chiffres, zero-paddé), unique.
///
/// Reproduit `Mutation::genererNumeroNotification` : prend le plus grand
/// numéro existant, +1, padding à 7 chiffres ; boucle jusqu'à l'unicité.
pub async fn next_numero_notification(pool: &AnyPool) -> AppResult<String> {
    let rows = sqlx::query("SELECT numero_notification FROM mutations WHERE numero_notification IS NOT NULL")
        .fetch_all(pool)
        .await?;

    let mut max: i64 = 0;
    for row in rows {
        let raw: Option<String> = row.try_get("numero_notification").ok();
        if let Some(s) = raw {
            let digits: String = s.chars().filter(|c| c.is_ascii_digit()).collect();
            if let Ok(n) = digits.parse::<i64>() {
                if n > max {
                    max = n;
                }
            }
        }
    }

    // Garantit l'unicité (en cas de trous / collisions).
    let mut candidate = max + 1;
    loop {
        let num = format!("{:0>7}", candidate);
        let exists: Option<(i64,)> =
            sqlx::query_as("SELECT id FROM mutations WHERE numero_notification = ?")
                .bind(&num)
                .fetch_optional(pool)
                .await
                .map_err(|e| AppError::Internal(e.to_string()))?;
        if exists.is_none() {
            return Ok(num);
        }
        candidate += 1;
    }
}

/// Génère un code projet `AB1234` unique à partir du nom.
pub async fn next_code_projet(pool: &AnyPool, nom: &str) -> AppResult<String> {
    let mots: Vec<&str> = nom.split_whitespace().collect();
    let prefixe: String = if mots.len() >= 2 {
        format!(
            "{}{}",
            mots[0].chars().next().unwrap_or('X'),
            mots[1].chars().next().unwrap_or('X')
        )
    } else if let Some(m) = mots.first() {
        m.chars().take(2).collect()
    } else {
        "PR".to_string()
    };
    let prefixe = format!("{:X<2}", prefixe.to_uppercase());

    let mut num = 1;
    loop {
        let code = format!("{}{:0>4}", prefixe, num);
        let exists: Option<(i64,)> =
            sqlx::query_as("SELECT id FROM projets WHERE code = ?")
                .bind(&code)
                .fetch_optional(pool)
                .await
                .map_err(|e| AppError::Internal(e.to_string()))?;
        if exists.is_none() {
            return Ok(code);
        }
        num += 1;
    }
}

/// Journalise une action (équivalent `ActivityLog::log`).
pub async fn log_activity(
    pool: &AnyPool,
    user_id: Option<i64>,
    action: &str,
    module: &str,
    description: &str,
) {
    let _ = sqlx::query(
        "INSERT INTO activity_logs (user_id, action, module, description, created_at, updated_at) \
         VALUES (?, ?, ?, ?, ?, ?)",
    )
    .bind(user_id)
    .bind(action)
    .bind(module)
    .bind(description)
    .bind(now())
    .bind(now())
    .execute(pool)
    .await;
}
