use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
use std::collections::HashSet;

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct EmployeeImportRow {
    full_name: String,
    email: String,
    employee_code: String,
    password: String,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct EmployeeImport {
    rows: Vec<EmployeeImportRow>,
    dry_run: bool,
}

fn validate(row: &EmployeeImportRow) -> Result<String, ApiError> {
    let email = normalise_email(&row.email);
    if row.full_name.trim().is_empty()
        || row.full_name.len() > 200
        || row.employee_code.trim().is_empty()
        || row.employee_code.len() > 80
        || email.len() > 254
        || !email.contains('@')
        || email.chars().any(char::is_whitespace)
        || row.password.len() < 12
        || row.password.len() > 1024
    {
        return Err(ApiError::BadRequest(
            "invalid employee import row; password must have at least 12 characters",
        ));
    }
    Ok(email)
}

pub async fn import(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<EmployeeImport>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::EMPLOYEES_MANAGE)?;
    if body.rows.is_empty() || body.rows.len() > 200 {
        return Err(ApiError::BadRequest(
            "import between 1 and 200 employee rows",
        ));
    }

    let mut emails = HashSet::with_capacity(body.rows.len());
    let mut codes = HashSet::with_capacity(body.rows.len());
    let normalized = body
        .rows
        .iter()
        .map(|row| {
            let email = validate(row)?;
            if !emails.insert(email.clone()) || !codes.insert(row.employee_code.trim().to_owned()) {
                return Err(ApiError::Conflict(
                    "duplicate email or employee code in import",
                ));
            }
            Ok(email)
        })
        .collect::<Result<Vec<_>, _>>()?;

    let login_origin = state
        .app_origin
        .split(',')
        .map(str::trim)
        .find(|origin| !origin.is_empty())
        .unwrap_or("http://127.0.0.1:4173");
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;

    for (row, email) in body.rows.iter().zip(normalized) {
        let email_exists =
            sqlx::query_scalar::<_, bool>("SELECT EXISTS(SELECT 1 FROM users WHERE email=$1)")
                .bind(&email)
                .fetch_one(&mut *tx)
                .await
                .map_err(database_error)?;
        let code_exists = sqlx::query_scalar::<_, bool>(
            "SELECT EXISTS(SELECT 1 FROM employee_profiles WHERE company_id=$1 AND employee_code=$2)",
        )
        .bind(context.company_id)
        .bind(row.employee_code.trim())
        .fetch_one(&mut *tx)
        .await
        .map_err(database_error)?;
        if email_exists || code_exists {
            return Err(ApiError::Conflict("email or employee code already exists"));
        }

        if !body.dry_run {
            let id = Uuid::now_v7();
            sqlx::query(
                "INSERT INTO users (id,email,password_hash,full_name) VALUES ($1,$2,$3,$4)",
            )
            .bind(id)
            .bind(&email)
            .bind(hash_password(&row.password)?)
            .bind(row.full_name.trim())
            .execute(&mut *tx)
            .await
            .map_err(database_error)?;
            sqlx::query("INSERT INTO company_memberships(company_id,user_id,role) VALUES ($1,$2,'employee')")
                .bind(context.company_id)
                .bind(id)
                .execute(&mut *tx)
                .await
                .map_err(database_error)?;
            sqlx::query(
                "INSERT INTO employee_profiles(company_id,user_id,employee_code) VALUES ($1,$2,$3)",
            )
            .bind(context.company_id)
            .bind(id)
            .bind(row.employee_code.trim())
            .execute(&mut *tx)
            .await
            .map_err(database_error)?;
            sqlx::query("INSERT INTO outbox_events (id, company_id, event_type, payload) VALUES ($1,$2,'people.employee_created',jsonb_build_object('user_id',$3,'full_name',$4,'email',$5,'login_url',$6))")
                .bind(Uuid::now_v7())
                .bind(context.company_id)
                .bind(id)
                .bind(row.full_name.trim())
                .bind(&email)
                .bind(format!("{login_origin}/login"))
                .execute(&mut *tx)
                .await
                .map_err(database_error)?;
        }
    }

    if body.dry_run {
        tx.rollback().await.map_err(database_error)?;
    } else {
        people::audit(
            &mut tx,
            &context,
            context.company_id,
            "employee.imported",
            json!({"rows": body.rows.len()}),
        )
        .await?;
        tx.commit().await.map_err(database_error)?;
    }
    Ok(Json(
        json!({"rows": body.rows.len(), "dry_run": body.dry_run}),
    ))
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn employee_import_requires_a_safe_complete_row() {
        let valid = EmployeeImportRow {
            full_name: "Nara".into(),
            email: "NARA@EXAMPLE.COM".into(),
            employee_code: "EMP-1".into(),
            password: "long-enough-password".into(),
        };
        assert!(matches!(validate(&valid), Ok(email) if email == "nara@example.com"));
        let invalid = EmployeeImportRow {
            password: "short".into(),
            ..valid
        };
        assert!(validate(&invalid).is_err());
    }
}
