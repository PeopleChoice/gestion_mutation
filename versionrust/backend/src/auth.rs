use crate::error::{AppError, AppResult};
use crate::state::AppState;
use argon2::password_hash::{
    rand_core::OsRng, PasswordHash, PasswordHasher, PasswordVerifier, SaltString,
};
use argon2::Argon2;
use axum::async_trait;
use axum::extract::FromRequestParts;
use axum::http::request::Parts;
use jsonwebtoken::{decode, encode, DecodingKey, EncodingKey, Header, Validation};
use serde::{Deserialize, Serialize};
use sqlx::Row;

/// Contenu du JWT.
#[derive(Debug, Serialize, Deserialize)]
pub struct Claims {
    pub sub: i64,      // user id
    pub name: String,
    pub email: String,
    pub roles: Vec<String>,
    pub exp: usize,    // expiration (timestamp)
}

/// Hash un mot de passe en Argon2.
pub fn hash_password(plain: &str) -> AppResult<String> {
    let salt = SaltString::generate(&mut OsRng);
    Argon2::default()
        .hash_password(plain.as_bytes(), &salt)
        .map(|h| h.to_string())
        .map_err(|e| AppError::Internal(format!("hash: {e}")))
}

/// Vérifie un mot de passe contre son hash Argon2.
pub fn verify_password(plain: &str, hash: &str) -> bool {
    match PasswordHash::new(hash) {
        Ok(parsed) => Argon2::default()
            .verify_password(plain.as_bytes(), &parsed)
            .is_ok(),
        Err(_) => false,
    }
}

/// Émet un JWT valable 12 h.
pub fn issue_token(secret: &str, sub: i64, name: &str, email: &str, roles: Vec<String>) -> AppResult<String> {
    let exp = (chrono::Utc::now() + chrono::Duration::hours(12)).timestamp() as usize;
    let claims = Claims {
        sub,
        name: name.to_string(),
        email: email.to_string(),
        roles,
        exp,
    };
    encode(&Header::default(), &claims, &EncodingKey::from_secret(secret.as_bytes()))
        .map_err(|e| AppError::Internal(format!("jwt: {e}")))
}

/// Utilisateur authentifié, extrait du header `Authorization: Bearer <jwt>`.
#[derive(Debug, Clone)]
pub struct AuthUser {
    pub id: i64,
    pub name: String,
    pub email: String,
    pub roles: Vec<String>,
}

impl AuthUser {
    pub fn has_role(&self, role: &str) -> bool {
        self.roles.iter().any(|r| r == role)
    }

    /// Vrai si l'utilisateur possède au moins un des rôles demandés.
    pub fn has_any(&self, roles: &[&str]) -> bool {
        roles.iter().any(|r| self.has_role(r))
    }

    /// Garde : exige l'un des rôles, sinon 403.
    pub fn require_any(&self, roles: &[&str]) -> AppResult<()> {
        if self.has_any(roles) {
            Ok(())
        } else {
            Err(AppError::Forbidden(format!(
                "Rôle requis : {}",
                roles.join(" ou ")
            )))
        }
    }
}

#[async_trait]
impl FromRequestParts<AppState> for AuthUser {
    type Rejection = AppError;

    async fn from_request_parts(parts: &mut Parts, state: &AppState) -> Result<Self, Self::Rejection> {
        let header = parts
            .headers
            .get(axum::http::header::AUTHORIZATION)
            .and_then(|h| h.to_str().ok())
            .ok_or_else(|| AppError::Unauthorized("Token manquant".into()))?;

        let token = header
            .strip_prefix("Bearer ")
            .ok_or_else(|| AppError::Unauthorized("Format de token invalide".into()))?;

        let secret = state.config.read().await.jwt_secret.clone();
        let data = decode::<Claims>(
            token,
            &DecodingKey::from_secret(secret.as_bytes()),
            &Validation::default(),
        )
        .map_err(|_| AppError::Unauthorized("Token invalide ou expiré".into()))?;

        Ok(AuthUser {
            id: data.claims.sub,
            name: data.claims.name,
            email: data.claims.email,
            roles: data.claims.roles,
        })
    }
}

/// Charge les rôles d'un utilisateur depuis la base.
pub async fn load_roles(pool: &sqlx::any::AnyPool, user_id: i64) -> AppResult<Vec<String>> {
    let rows = sqlx::query(
        "SELECT r.name FROM roles r \
         JOIN user_roles ur ON ur.role_id = r.id \
         WHERE ur.user_id = ?",
    )
    .bind(user_id)
    .fetch_all(pool)
    .await?;

    Ok(rows
        .into_iter()
        .filter_map(|row| row.try_get::<String, _>("name").ok())
        .collect())
}
