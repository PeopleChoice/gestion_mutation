use crate::auth::AuthUser;
use crate::domain;
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::{self, now, row_to_json, rows_to_json};
use axum::extract::{Multipart, Path, State};
use axum::Json;
use calamine::{Data, Reader, Xlsx};
use serde::Deserialize;
use serde_json::{json, Value};
use sqlx::Row;
use std::io::Cursor;

/// Normalise un libellé d'en-tête (minuscules, sans accents).
fn norm(s: &str) -> String {
    s.trim()
        .to_lowercase()
        .chars()
        .map(|c| match c {
            'é' | 'è' | 'ê' | 'ë' => 'e',
            'à' | 'â' | 'ä' => 'a',
            'î' | 'ï' => 'i',
            'ô' | 'ö' => 'o',
            'ù' | 'û' | 'ü' => 'u',
            'ç' => 'c',
            x => x,
        })
        .collect()
}

/// Valeur d'une cellule en chaîne (les flottants entiers perdent le `.0`).
fn cell(d: &Data) -> String {
    match d {
        Data::Empty => String::new(),
        Data::String(s) => s.trim().to_string(),
        Data::Int(i) => i.to_string(),
        Data::Float(f) => {
            if f.fract() == 0.0 {
                format!("{}", *f as i64)
            } else {
                f.to_string()
            }
        }
        Data::Bool(b) => b.to_string(),
        other => other.to_string(),
    }
}

/// Index de la première colonne dont l'en-tête contient un des mots-clés,
/// en excluant ceux de `not`.
fn find_col(headers: &[String], any: &[&str], not: &[&str]) -> Option<usize> {
    headers.iter().position(|h| {
        let n = norm(h);
        any.iter().any(|k| n.contains(k)) && !not.iter().any(|k| n.contains(k))
    })
}

/// POST /api/imports — upload d'un fichier Excel + parsing + matching.
pub async fn upload(
    user: AuthUser,
    State(state): State<AppState>,
    mut multipart: Multipart,
) -> AppResult<Json<Value>> {
    let pool = state.db().await?;

    let mut projet_id: i64 = 0;
    let mut filename = String::from("import.xlsx");
    let mut bytes: Vec<u8> = Vec::new();

    while let Some(field) = multipart
        .next_field()
        .await
        .map_err(|e| AppError::BadRequest(format!("Upload invalide : {e}")))?
    {
        match field.name() {
            Some("projet_id") => {
                projet_id = field.text().await.unwrap_or_default().parse().unwrap_or(0);
            }
            Some("file") => {
                if let Some(fname) = field.file_name() {
                    filename = fname.to_string();
                }
                bytes = field
                    .bytes()
                    .await
                    .map_err(|e| AppError::BadRequest(format!("Fichier illisible : {e}")))?
                    .to_vec();
            }
            _ => {}
        }
    }

    if projet_id == 0 {
        return Err(AppError::BadRequest("Projet requis.".into()));
    }
    if bytes.is_empty() {
        return Err(AppError::BadRequest("Fichier vide ou absent.".into()));
    }

    // Parsing du classeur (première feuille).
    let mut wb: Xlsx<_> = Xlsx::new(Cursor::new(bytes))
        .map_err(|e| AppError::BadRequest(format!("Excel illisible : {e}")))?;
    let range = wb
        .worksheet_range_at(0)
        .ok_or_else(|| AppError::BadRequest("Aucune feuille dans le fichier.".into()))?
        .map_err(|e| AppError::BadRequest(format!("Feuille illisible : {e}")))?;

    let rows: Vec<Vec<String>> = range
        .rows()
        .map(|r| r.iter().map(cell).collect())
        .collect();
    if rows.is_empty() {
        return Err(AppError::BadRequest("Fichier sans données.".into()));
    }

    // Détection de la ligne d'en-tête : la plus remplie parmi les 3 premières.
    let header_idx = (0..rows.len().min(3))
        .max_by_key(|&i| rows[i].iter().filter(|c| !c.is_empty()).count())
        .unwrap_or(0);
    let headers = &rows[header_idx];

    let col_lot = find_col(headers, &["lot"], &[])
        .ok_or_else(|| AppError::BadRequest("Colonne « lot » introuvable.".into()))?;
    let col_prenom = find_col(headers, &["prenom"], &["demandeur"]);
    let col_nom = find_col(headers, &["nom"], &["prenom", "demandeur"]);
    let col_cni = find_col(headers, &["cni", "piece", "passeport"], &[]);
    let col_tel = find_col(headers, &["tel"], &["demandeur"]);
    let col_civ = find_col(headers, &["civilit"], &[]);

    let get = |row: &[String], col: Option<usize>| -> Option<String> {
        col.and_then(|c| row.get(c)).map(|s| s.trim().to_string()).filter(|s| !s.is_empty())
    };

    // Création de l'import.
    sqlx::query(
        "INSERT INTO imports (nom_fichier, fichier_path, projet_id, imported_by, statut, type, created_at, updated_at) \
         VALUES (?, ?, ?, ?, 'en_cours', 'mutation', ?, ?)",
    )
    .bind(&filename)
    .bind(&filename)
    .bind(projet_id)
    .bind(user.id)
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;
    let import_id = util::last_id(&pool, "imports").await?;

    let mut total = 0i64;
    let mut matched_count = 0i64;
    let mut ordre = 0i64;

    for row in &rows[header_idx + 1..] {
        let lot = match get(row, Some(col_lot)) {
            Some(l) => l,
            None => continue, // ligne vide
        };
        ordre += 1;
        total += 1;

        // Matching parcelle.
        let parcelle: Option<(i64,)> =
            sqlx::query_as("SELECT id FROM parcelles WHERE numero_lot = ? AND projet_id = ?")
                .bind(&lot)
                .bind(projet_id)
                .fetch_optional(&pool)
                .await?;
        let parcelle_id = parcelle.map(|p| p.0);
        let matched = parcelle_id.is_some();
        if matched {
            matched_count += 1;
        }

        sqlx::query(
            "INSERT INTO import_lignes (import_id, numero_ordre, civilite, prenom, nom, cni_passport, telephone, numero_lot, parcelle_id, matched, statut, created_at, updated_at) \
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente', ?, ?)",
        )
        .bind(import_id)
        .bind(ordre)
        .bind(get(row, col_civ))
        .bind(get(row, col_prenom))
        .bind(get(row, col_nom))
        .bind(get(row, col_cni))
        .bind(get(row, col_tel))
        .bind(&lot)
        .bind(parcelle_id)
        .bind(if matched { 1 } else { 0 })
        .bind(now())
        .bind(now())
        .execute(&pool)
        .await?;
    }

    sqlx::query("UPDATE imports SET statut='termine', total_lignes=?, lignes_traitees=?, lignes_matchees=?, updated_at=? WHERE id=?")
        .bind(total)
        .bind(total)
        .bind(matched_count)
        .bind(now())
        .bind(import_id)
        .execute(&pool)
        .await?;

    util::log_activity(&pool, Some(user.id), "import", "import", &filename).await;

    Ok(Json(json!({
        "import_id": import_id, "total": total, "matchees": matched_count,
        "non_matchees": total - matched_count
    })))
}

/// GET /api/imports
pub async fn list(_user: AuthUser, State(state): State<AppState>) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let rows = sqlx::query(
        "SELECT i.*, pj.nom AS projet_nom, \
            (SELECT COUNT(*) FROM import_lignes l WHERE l.import_id = i.id AND l.statut='en_attente' AND l.matched=1) AS en_attente \
         FROM imports i LEFT JOIN projets pj ON pj.id = i.projet_id \
         ORDER BY i.created_at DESC",
    )
    .fetch_all(&pool)
    .await?;
    Ok(Json(rows_to_json(&rows)))
}

/// GET /api/imports/:id
pub async fn show(_user: AuthUser, State(state): State<AppState>, Path(id): Path<i64>) -> AppResult<Json<Value>> {
    let pool = state.db().await?;
    let import = sqlx::query("SELECT i.*, pj.nom AS projet_nom FROM imports i LEFT JOIN projets pj ON pj.id = i.projet_id WHERE i.id = ?")
        .bind(id)
        .fetch_optional(&pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Import introuvable.".into()))?;
    let lignes = sqlx::query("SELECT * FROM import_lignes WHERE import_id = ? ORDER BY numero_ordre")
        .bind(id)
        .fetch_all(&pool)
        .await?;
    Ok(Json(json!({ "import": row_to_json(&import), "lignes": rows_to_json(&lignes) })))
}

#[derive(Debug, Deserialize)]
pub struct NumeroInput {
    #[serde(default)]
    pub numero_notification: Option<String>,
}

/// POST /api/imports/lignes/:id/valider (admin|gestionnaire)
/// Crée le nouveau propriétaire + une mutation validée (transfert).
pub async fn valider_ligne(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<NumeroInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire"])?;
    let pool = state.db().await?;

    let l = sqlx::query(
        "SELECT l.civilite, l.prenom, l.nom, l.cni_passport, l.telephone, l.parcelle_id, l.statut, \
                pa.proprietaire_id AS ancien_id \
         FROM import_lignes l LEFT JOIN parcelles pa ON pa.id = l.parcelle_id WHERE l.id = ?",
    )
    .bind(id)
    .fetch_optional(&pool)
    .await?
    .ok_or_else(|| AppError::NotFound("Ligne introuvable.".into()))?;

    let statut: String = l.try_get("statut")?;
    if statut != "en_attente" {
        return Err(AppError::BadRequest("Ligne déjà traitée.".into()));
    }
    let parcelle_id: Option<i64> = l.try_get::<Option<i64>, _>("parcelle_id").unwrap_or(None);
    let parcelle_id = parcelle_id.ok_or_else(|| AppError::BadRequest("Lot non rattaché (non matché).".into()))?;
    let ancien_id: Option<i64> = l.try_get::<Option<i64>, _>("ancien_id").unwrap_or(None);
    if ancien_id.is_none() {
        return Err(AppError::BadRequest("Lot vierge (sans propriétaire) : non mutable.".into()));
    }

    let g = |k: &str| l.try_get::<Option<String>, _>(k).unwrap_or(None);
    let prenom = g("prenom").unwrap_or_else(|| "N/A".into());
    let nom = g("nom").unwrap_or_else(|| "N/A".into());

    let nouveau_id = domain::create_proprietaire(
        &pool, g("civilite").as_deref(), &prenom, &nom,
        g("cni_passport").as_deref(), None, None, None, g("telephone").as_deref(), None,
    )
    .await?;

    let numero = match &input.numero_notification {
        Some(n) if !n.trim().is_empty() => n.trim().to_string(),
        _ => util::next_numero_notification(&pool).await?,
    };

    sqlx::query(
        "INSERT INTO mutations (parcelle_id, ancien_proprietaire_id, nouveau_proprietaire_id, import_id, statut, numero_notification, date_mutation, validated_by, validated_at, created_at, updated_at) \
         VALUES (?, ?, ?, (SELECT import_id FROM import_lignes WHERE id = ?), 'validee', ?, ?, ?, ?, ?, ?)",
    )
    .bind(parcelle_id)
    .bind(ancien_id)
    .bind(nouveau_id)
    .bind(id)
    .bind(&numero)
    .bind(util::today())
    .bind(user.id)
    .bind(now())
    .bind(now())
    .bind(now())
    .execute(&pool)
    .await?;

    let mutation_id = util::last_id(&pool, "mutations").await?;
    let app_key = state.config.read().await.app_key_bytes();
    let code = domain::finalize_validated_mutation(&pool, &app_key, mutation_id).await?;

    sqlx::query("UPDATE import_lignes SET statut='validee', updated_at=? WHERE id=?")
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;

    util::log_activity(&pool, Some(user.id), "ligne_validee", "import", &numero).await;
    Ok(Json(json!({ "mutation_id": mutation_id, "numero_notification": numero, "code_verification": code })))
}

#[derive(Debug, Deserialize)]
pub struct RefusInput {
    #[serde(default)]
    pub motif: Option<String>,
}

/// GET /api/imports/template — télécharge un modèle Excel vierge.
pub async fn template(_user: AuthUser, State(_state): State<AppState>) -> AppResult<axum::response::Response> {
    use axum::body::Body;
    use axum::http::header;
    use rust_xlsxwriter::Workbook;

    let mut wb = Workbook::new();
    let sheet = wb.add_worksheet();
    let cols = ["Lot", "Civilité", "Prénom", "Nom", "CNI / Passeport", "Téléphone"];
    for (i, c) in cols.iter().enumerate() {
        sheet
            .write_string(0, i as u16, *c)
            .map_err(|e| AppError::Internal(e.to_string()))?;
    }
    let buf = wb.save_to_buffer().map_err(|e| AppError::Internal(e.to_string()))?;

    axum::response::Response::builder()
        .header(
            header::CONTENT_TYPE,
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        )
        .header(header::CONTENT_DISPOSITION, "attachment; filename=\"modele_import.xlsx\"")
        .body(Body::from(buf))
        .map_err(|e| AppError::Internal(e.to_string()))
}

/// POST /api/imports/lignes/:id/refuser (admin|gestionnaire)
pub async fn refuser_ligne(
    user: AuthUser,
    State(state): State<AppState>,
    Path(id): Path<i64>,
    Json(input): Json<RefusInput>,
) -> AppResult<Json<Value>> {
    user.require_any(&["admin", "gestionnaire"])?;
    let pool = state.db().await?;
    let res = sqlx::query("UPDATE import_lignes SET statut='refusee', observation=?, updated_at=? WHERE id=? AND statut='en_attente'")
        .bind(input.motif.as_deref().unwrap_or("Refusée"))
        .bind(now())
        .bind(id)
        .execute(&pool)
        .await?;
    if res.rows_affected() == 0 {
        return Err(AppError::BadRequest("Ligne introuvable ou déjà traitée.".into()));
    }
    Ok(Json(json!({ "ok": true })))
}
