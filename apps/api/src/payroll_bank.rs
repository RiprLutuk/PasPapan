use super::*;
use serde::{Deserialize, Serialize};
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Account {
    user_id: Uuid,
    bank_code: String,
    account_number: String,
    account_name: String,
    version: Option<i64>,
}
#[derive(Deserialize, Serialize)]
#[serde(deny_unknown_fields)]
pub struct Column {
    field: String,
    label: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Settings {
    columns: Vec<Column>,
    delimiter: String,
    version: Option<i64>,
    confirmed: bool,
}
const FIELDS: [&str; 9] = [
    "employee_code",
    "employee_name",
    "bank_code",
    "account_number",
    "account_name",
    "net_amount",
    "currency",
    "period_start",
    "period_end",
];
pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    let accounts=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('user_id',a.user_id,'employee_name',u.full_name,'bank_code',a.bank_code,'account_number',a.account_number,'account_name',a.account_name,'version',a.version) FROM employee_payroll_accounts a JOIN users u ON u.id=a.user_id WHERE a.company_id=$1 ORDER BY u.full_name").bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    let settings=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('columns',columns,'delimiter',delimiter,'version',version) FROM payroll_bank_export_settings WHERE company_id=$1").bind(context.company_id).fetch_optional(&state.pool).await.map_err(database_error)?;
    Ok(Json(json!({"accounts":accounts,"settings":settings})))
}
pub async fn save_account(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<Account>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    if !(2..=20).contains(&body.bank_code.len())
        || !body
            .bank_code
            .bytes()
            .all(|v| v.is_ascii_alphanumeric() || v == b'-')
        || !(6..=40).contains(&body.account_number.len())
        || !body.account_number.bytes().all(|v| v.is_ascii_digit())
        || body.account_name.trim().is_empty()
        || body.account_name.len() > 200
    {
        return Err(ApiError::BadRequest("invalid employee bank account"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let member: bool = sqlx::query_scalar(
        "SELECT EXISTS(SELECT 1 FROM company_memberships WHERE company_id=$1 AND user_id=$2)",
    )
    .bind(context.company_id)
    .bind(body.user_id)
    .fetch_one(&mut *tx)
    .await
    .map_err(database_error)?;
    if !member {
        return Err(ApiError::Forbidden);
    }
    let version = sqlx::query_scalar::<_, i64>(
        "SELECT version FROM employee_payroll_accounts WHERE company_id=$1 AND user_id=$2",
    )
    .bind(context.company_id)
    .bind(body.user_id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?;
    if version != body.version {
        return Err(ApiError::Conflict("bank account changed; reload"));
    }
    sqlx::query("INSERT INTO employee_payroll_accounts(company_id,user_id,bank_code,account_number,account_name) VALUES($1,$2,$3,$4,$5) ON CONFLICT(company_id,user_id) DO UPDATE SET bank_code=EXCLUDED.bank_code,account_number=EXCLUDED.account_number,account_name=EXCLUDED.account_name,version=employee_payroll_accounts.version+1")
        .bind(context.company_id).bind(body.user_id).bind(body.bank_code.trim()).bind(body.account_number.trim()).bind(body.account_name.trim()).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(
        &mut tx,
        &context,
        body.user_id,
        "payroll.bank_account_saved",
        json!({}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
pub async fn save_settings(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<Settings>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    integrations::require_owner(&context)?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    if !body.confirmed
        || !matches!(body.delimiter.as_str(), "," | ";")
        || body.columns.is_empty()
        || body.columns.len() > 20
        || body.columns.iter().any(|c| {
            !FIELDS.contains(&c.field.as_str()) || c.label.trim().is_empty() || c.label.len() > 100
        })
        || !["net_amount", "account_number"]
            .iter()
            .all(|field| body.columns.iter().any(|c| c.field == *field))
    {
        return Err(ApiError::BadRequest(
            "confirm a valid bank export column mapping",
        ));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let version = sqlx::query_scalar::<_, i64>(
        "SELECT version FROM payroll_bank_export_settings WHERE company_id=$1",
    )
    .bind(context.company_id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?;
    if version != body.version {
        return Err(ApiError::Conflict("bank export settings changed; reload"));
    }
    sqlx::query("INSERT INTO payroll_bank_export_settings(company_id,columns,delimiter) VALUES($1,$2,$3) ON CONFLICT(company_id) DO UPDATE SET columns=EXCLUDED.columns,delimiter=EXCLUDED.delimiter,version=payroll_bank_export_settings.version+1")
        .bind(context.company_id).bind(json!(body.columns)).bind(body.delimiter).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(
        &mut tx,
        &context,
        context.company_id,
        "payroll.bank_export_settings_saved",
        json!({}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
fn cell(value: &str) -> String {
    let safe = if value.starts_with(['=', '+', '-', '@', '\t', '\r', '\n'])
        || value.trim_start().starts_with(['=', '+', '-', '@'])
    {
        format!("'{value}")
    } else {
        value.to_owned()
    };
    format!("\"{}\"", safe.replace('"', "\"\""))
}
pub async fn export(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<Response, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let (columns, delimiter) = sqlx::query_as::<_, (Value, String)>(
        "SELECT columns,delimiter FROM payroll_bank_export_settings WHERE company_id=$1",
    )
    .bind(context.company_id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::Conflict(
        "configure and confirm the bank export format first",
    ))?;
    let columns: Vec<Column> = serde_json::from_value(columns).map_err(|_| ApiError::Internal)?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('employee_code',ep.employee_code,'employee_name',u.full_name,'bank_code',r.bank_account_snapshot->>'bank_code','account_number',r.bank_account_snapshot->>'account_number','account_name',r.bank_account_snapshot->>'account_name','net_amount',r.net_amount::bigint::text,'currency','IDR','period_start',p.period_start::text,'period_end',p.period_end::text) FROM payroll_records r JOIN payroll_periods p ON p.company_id=r.company_id AND p.id=r.period_id JOIN users u ON u.id=r.user_id LEFT JOIN employee_profiles ep ON ep.company_id=r.company_id AND ep.user_id=r.user_id WHERE r.company_id=$1 AND r.period_id=$2 AND p.status IN ('published','closed') ORDER BY r.user_id")
        .bind(context.company_id).bind(id).fetch_all(&mut *tx).await.map_err(database_error)?;
    if rows.is_empty()
        || rows
            .iter()
            .any(|r| r["account_number"].as_str().is_none_or(str::is_empty))
    {
        return Err(ApiError::Conflict(
            "published payroll with complete bank account snapshots is required",
        ));
    }
    let mut csv = columns
        .iter()
        .map(|c| cell(&c.label))
        .collect::<Vec<_>>()
        .join(&delimiter);
    for row in rows {
        csv.push_str("\r\n");
        csv.push_str(
            &columns
                .iter()
                .map(|c| cell(row[&c.field].as_str().unwrap_or("")))
                .collect::<Vec<_>>()
                .join(&delimiter),
        );
    }
    people::audit(&mut tx, &context, id, "payroll.bank_exported", json!({})).await?;
    tx.commit().await.map_err(database_error)?;
    let mut response = csv.into_response();
    response.headers_mut().insert(
        header::CONTENT_TYPE,
        HeaderValue::from_static("text/csv; charset=utf-8"),
    );
    response
        .headers_mut()
        .insert(header::CACHE_CONTROL, HeaderValue::from_static("no-store"));
    response.headers_mut().insert(
        header::CONTENT_DISPOSITION,
        HeaderValue::from_str(&format!("attachment; filename=payroll-{id}.csv"))
            .map_err(|_| ApiError::Internal)?,
    );
    Ok(response)
}

#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn csv_quotes_delimiters_and_neutralizes_formula_prefixes() {
        assert_eq!(cell("00123456"), "\"00123456\"");
        assert_eq!(cell("=1+1"), "\"'=1+1\"");
        assert_eq!(cell("\n=1+1"), "\"'\n=1+1\"");
        assert_eq!(cell("  @SUM(1)"), "\"'  @SUM(1)\"");
        assert_eq!(cell("a,\"b\""), "\"a,\"\"b\"\"\"");
    }
}
