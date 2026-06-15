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

    Ok(())
}
