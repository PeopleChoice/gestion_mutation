use crate::config::DbDriver;
use sqlx::any::AnyPool;

/// Clause de clé primaire auto-incrémentée selon le dialecte.
fn pk(driver: &DbDriver) -> &'static str {
    match driver {
        DbDriver::Sqlite => "INTEGER PRIMARY KEY AUTOINCREMENT",
        DbDriver::Mysql => "BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY",
    }
}

/// Type d'un identifiant de clé étrangère.
fn fk(driver: &DbDriver) -> &'static str {
    match driver {
        DbDriver::Sqlite => "INTEGER",
        DbDriver::Mysql => "BIGINT",
    }
}

/// Crée tout le schéma (idempotent). Les dates/timestamps sont stockés en TEXT
/// (ISO-8601) dans les deux dialectes pour un décodage uniforme ; les booléens
/// en INTEGER (0/1) ; les décimaux en TEXT.
pub async fn run(pool: &AnyPool, driver: &DbDriver) -> anyhow::Result<()> {
    if *driver == DbDriver::Sqlite {
        sqlx::query("PRAGMA foreign_keys = ON").execute(pool).await.ok();
    }

    let id = pk(driver);
    let r = fk(driver);

    let stmts: Vec<String> = vec![
        format!(
            "CREATE TABLE IF NOT EXISTS users (
                id {id},
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS roles (
                id {id},
                name TEXT NOT NULL UNIQUE,
                created_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS user_roles (
                user_id {r} NOT NULL,
                role_id {r} NOT NULL,
                PRIMARY KEY (user_id, role_id)
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS communes (
                id {id},
                nom TEXT NOT NULL,
                departement TEXT,
                region TEXT,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS projets (
                id {id},
                nom TEXT NOT NULL,
                code TEXT,
                type_lotissement TEXT,
                commune_id {r} NOT NULL,
                description TEXT,
                latitude TEXT,
                longitude TEXT,
                fichier_dxf TEXT,
                geojson TEXT,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS proprietaires (
                id {id},
                civilite TEXT,
                prenom TEXT NOT NULL,
                nom TEXT NOT NULL,
                nin TEXT,
                ninea TEXT,
                cni_passport TEXT,
                type_piece TEXT,
                telephone TEXT,
                adresse TEXT,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS parcelles (
                id {id},
                numero_lot TEXT NOT NULL,
                projet_id {r} NOT NULL,
                proprietaire_id {r},
                ref_lettre TEXT,
                date_attribution TEXT,
                superficie TEXT,
                `usage` TEXT,
                observation TEXT,
                geometrie TEXT,
                centroid_lat TEXT,
                centroid_lng TEXT,
                created_at TEXT,
                updated_at TEXT,
                UNIQUE (numero_lot, projet_id)
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS mutations (
                id {id},
                parcelle_id {r} NOT NULL,
                ancien_proprietaire_id {r},
                nouveau_proprietaire_id {r},
                import_id {r},
                statut TEXT NOT NULL DEFAULT 'en_attente',
                ref_lettre TEXT,
                numero_notification TEXT,
                date_mutation TEXT,
                motif_refus TEXT,
                observation TEXT,
                validated_by {r},
                validated_at TEXT,
                code_verification TEXT,
                code_paye TEXT,
                type_piece TEXT,
                demandeur_prenom TEXT,
                demandeur_nom TEXT,
                demandeur_telephone TEXT,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS imports (
                id {id},
                nom_fichier TEXT NOT NULL,
                fichier_path TEXT NOT NULL,
                projet_id {r} NOT NULL,
                imported_by {r} NOT NULL,
                statut TEXT NOT NULL DEFAULT 'en_cours',
                type TEXT NOT NULL DEFAULT 'mutation',
                total_lignes INTEGER NOT NULL DEFAULT 0,
                lignes_traitees INTEGER NOT NULL DEFAULT 0,
                lignes_matchees INTEGER NOT NULL DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS import_lignes (
                id {id},
                import_id {r} NOT NULL,
                numero_ordre INTEGER NOT NULL,
                civilite TEXT, prenom TEXT, nom TEXT,
                type_piece TEXT, code_paye TEXT, cni_passport TEXT,
                demandeur_prenom TEXT, demandeur_nom TEXT, demandeur_telephone TEXT,
                nin TEXT, ninea TEXT, telephone TEXT,
                numero_lot TEXT NOT NULL,
                ref_lettre TEXT, date_excel TEXT, observation TEXT,
                precedent_attributaire TEXT,
                parcelle_id {r},
                matched INTEGER NOT NULL DEFAULT 0,
                statut TEXT NOT NULL DEFAULT 'en_attente',
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS document_templates (
                id {id},
                nom TEXT NOT NULL,
                type TEXT NOT NULL DEFAULT 'notification_attribution',
                entete_html TEXT NOT NULL,
                corps_html TEXT NOT NULL,
                pied_html TEXT,
                centre_fiscal TEXT,
                bureau TEXT,
                actif INTEGER NOT NULL DEFAULT 1,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS mutation_annulations (
                id {id},
                mutation_id {r} NOT NULL,
                demande_par {r} NOT NULL,
                approuve_par {r},
                statut TEXT NOT NULL DEFAULT 'en_attente',
                motif TEXT NOT NULL,
                motif_rejet TEXT,
                approuve_at TEXT,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
        format!(
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id {id},
                user_id {r},
                action TEXT NOT NULL,
                module TEXT NOT NULL,
                description TEXT NOT NULL,
                ip_address TEXT,
                details TEXT,
                created_at TEXT,
                updated_at TEXT
            )"
        ),
    ];

    for stmt in stmts {
        sqlx::query(&stmt).execute(pool).await?;
    }

    // Index de performance — best-effort (la syntaxe IF NOT EXISTS sur index
    // n'existe pas en MySQL ; on ignore donc les erreurs « existe déjà »).
    let indexes = [
        "CREATE INDEX idx_mutations_statut ON mutations (statut)",
        "CREATE INDEX idx_mutations_code ON mutations (code_verification)",
        "CREATE INDEX idx_parcelles_lot ON parcelles (numero_lot)",
    ];
    for idx in indexes {
        let _ = sqlx::query(idx).execute(pool).await;
    }

    Ok(())
}
