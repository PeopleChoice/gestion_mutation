use crate::auth::hash_password;
use crate::config::AppConfig;
use crate::util::now;
use sqlx::any::AnyPool;
use sqlx::Row;

/// Les 4 rôles métier (cf. SPEC.md).
pub const ROLES: [&str; 4] = ["admin", "receveur", "gestionnaire", "operateur"];

/// Crée les rôles et le compte administrateur initial s'ils n'existent pas.
pub async fn run(pool: &AnyPool, _cfg: &AppConfig) -> anyhow::Result<()> {
    // Rôles
    for role in ROLES {
        let exists: Option<(i64,)> = sqlx::query_as("SELECT id FROM roles WHERE name = ?")
            .bind(role)
            .fetch_optional(pool)
            .await?;
        if exists.is_none() {
            sqlx::query("INSERT INTO roles (name, created_at) VALUES (?, ?)")
                .bind(role)
                .bind(now())
                .execute(pool)
                .await?;
        }
    }

    // Compte admin
    let email = std::env::var("ADMIN_SEED_EMAIL").unwrap_or_else(|_| "admin@domaines.sn".into());
    let admin_exists: Option<(i64,)> = sqlx::query_as("SELECT id FROM users WHERE email = ?")
        .bind(&email)
        .fetch_optional(pool)
        .await?;

    if admin_exists.is_none() {
        let password = std::env::var("ADMIN_SEED_PASSWORD").unwrap_or_else(|_| "admin1234".into());
        let hash = hash_password(&password)
            .map_err(|e| anyhow::anyhow!("hash admin: {e}"))?;

        sqlx::query("INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)")
            .bind("Administrateur")
            .bind(&email)
            .bind(&hash)
            .bind(now())
            .bind(now())
            .execute(pool)
            .await?;

        // Récupère l'id (compatible MySQL/SQLite)
        let user_id: i64 = sqlx::query("SELECT id FROM users WHERE email = ?")
            .bind(&email)
            .fetch_one(pool)
            .await?
            .try_get("id")?;

        let role_id: i64 = sqlx::query("SELECT id FROM roles WHERE name = 'admin'")
            .fetch_one(pool)
            .await?
            .try_get("id")?;

        sqlx::query("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")
            .bind(user_id)
            .bind(role_id)
            .execute(pool)
            .await?;

        tracing::info!("Compte admin créé : {email} (mot de passe : {password})");
    }

    // Template de notification par défaut
    let tpl_exists: Option<(i64,)> =
        sqlx::query_as("SELECT id FROM document_templates WHERE type = 'notification_attribution'")
            .fetch_optional(pool)
            .await?;
    if tpl_exists.is_none() {
        sqlx::query(
            "INSERT INTO document_templates (nom, type, entete_html, corps_html, pied_html, centre_fiscal, bureau, actif, created_at, updated_at) \
             VALUES (?, 'notification_attribution', ?, ?, ?, ?, ?, 1, ?, ?)",
        )
        .bind("Notification d'attribution (par défaut)")
        .bind(DEFAULT_ENTETE)
        .bind(DEFAULT_CORPS)
        .bind(DEFAULT_PIED)
        .bind("Centre des Services Fiscaux")
        .bind("Bureau des Domaines")
        .bind(now())
        .bind(now())
        .execute(pool)
        .await?;
    }

    Ok(())
}

const DEFAULT_ENTETE: &str = r#"
<div style="text-align:center; border-bottom:2px solid #1e3a8a; padding-bottom:8px; margin-bottom:16px;">
  <div style="font-weight:bold;">RÉPUBLIQUE DU SÉNÉGAL</div>
  <div style="font-size:12px;">Direction Générale des Impôts et des Domaines</div>
  <div style="font-size:12px;">{{centre_fiscal}} — {{bureau}}</div>
</div>"#;

const DEFAULT_CORPS: &str = r#"
<h2 style="text-align:center;">NOTIFICATION D'ATTRIBUTION</h2>
<p style="text-align:right;">N° {{numero_notification}} &nbsp;&nbsp; Le {{date_mutation}}</p>
<p>Il est porté à la connaissance de <strong>{{nom_complet_nouveau}}</strong>
(pièce : {{cni_nouveau}}) que le lot <strong>{{numero_lot}}</strong> du projet
<strong>{{nom_projet}}</strong>, commune de <strong>{{commune}}</strong>, lui est attribué.</p>
<p>La présente notification fait foi de l'attribution enregistrée sous le numéro
{{numero_notification}}.</p>"#;

const DEFAULT_PIED: &str = r#"
<div style="margin-top:32px; display:flex; justify-content:space-between; align-items:flex-end;">
  <div style="text-align:center; font-size:11px;">
    <img src="{{qr_code}}" alt="QR" style="width:120px; height:120px;" /><br/>
    Vérifier l'authenticité<br/>
    <span style="font-family:monospace; font-size:9px;">{{code_verification}}</span>
  </div>
  <div style="text-align:center;">
    Le Chef de Bureau<br/><br/><br/>_______________________
  </div>
</div>"#;
