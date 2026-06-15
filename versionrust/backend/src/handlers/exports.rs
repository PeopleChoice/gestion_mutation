use crate::auth::AuthUser;
use crate::error::{AppError, AppResult};
use crate::state::AppState;
use crate::util::rows_to_json;
use axum::body::Body;
use axum::extract::{Path, State};
use axum::http::header;
use axum::response::Response;
use axum::Json;
use serde_json::{json, Value};

/// Tables exportables (users sans le mot de passe).
const TABLES: [&str; 10] = [
    "communes", "projets", "proprietaires", "parcelles", "mutations",
    "imports", "import_lignes", "document_templates", "mutation_annulations", "activity_logs",
];

fn select_for(table: &str) -> AppResult<String> {
    if table == "users" {
        return Ok("SELECT id, name, email, created_at FROM users".into());
    }
    if TABLES.contains(&table) {
        Ok(format!("SELECT * FROM {table}"))
    } else {
        Err(AppError::BadRequest(format!("Table non exportable : {table}")))
    }
}

/// GET /api/admin/export.json — sauvegarde complète JSON (admin).
pub async fn export_json(user: AuthUser, State(state): State<AppState>) -> AppResult<Json<Value>> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;

    let mut dump = serde_json::Map::new();
    for table in TABLES.iter().chain(["users"].iter()) {
        let sql = select_for(table)?;
        let rows = sqlx::query(&sql).fetch_all(&pool).await?;
        dump.insert((*table).to_string(), rows_to_json(&rows));
    }
    Ok(Json(json!({ "version": 1, "tables": dump })))
}

/// GET /api/admin/export/:table — export CSV d'une table (admin).
pub async fn export_csv(
    user: AuthUser,
    State(state): State<AppState>,
    Path(table): Path<String>,
) -> AppResult<Response> {
    user.require_any(&["admin"])?;
    let pool = state.db().await?;
    let sql = select_for(&table)?;
    let rows = sqlx::query(&sql).fetch_all(&pool).await?;
    let data = rows_to_json(&rows);

    let csv = json_array_to_csv(&data);
    Response::builder()
        .header(header::CONTENT_TYPE, "text/csv; charset=utf-8")
        .header(header::CONTENT_DISPOSITION, format!("attachment; filename=\"{table}.csv\""))
        .body(Body::from(csv))
        .map_err(|e| AppError::Internal(e.to_string()))
}

/// Convertit un tableau JSON d'objets en CSV (point-virgule, échappement RFC4180).
fn json_array_to_csv(data: &Value) -> String {
    let arr = match data.as_array() {
        Some(a) if !a.is_empty() => a,
        _ => return String::new(),
    };
    // Colonnes = clés du premier objet.
    let keys: Vec<String> = arr[0].as_object().map(|o| o.keys().cloned().collect()).unwrap_or_default();

    let mut out = String::new();
    out.push_str(&keys.iter().map(|k| esc(k)).collect::<Vec<_>>().join(";"));
    out.push('\n');

    for row in arr {
        let line: Vec<String> = keys
            .iter()
            .map(|k| match row.get(k) {
                Some(Value::Null) | None => String::new(),
                Some(Value::String(s)) => esc(s),
                Some(v) => esc(&v.to_string()),
            })
            .collect();
        out.push_str(&line.join(";"));
        out.push('\n');
    }
    out
}

fn esc(s: &str) -> String {
    if s.contains(';') || s.contains('"') || s.contains('\n') {
        format!("\"{}\"", s.replace('"', "\"\""))
    } else {
        s.to_string()
    }
}
