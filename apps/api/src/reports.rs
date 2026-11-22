use super::*;
async fn ctx(s: &AppState, h: &axum::http::HeaderMap) -> Result<AuthContext, ApiError> {
    let c = authenticated_context(&s.pool, h).await?;
    require_capability(&c, access::REPORTS_VIEW)?;
    Ok(c)
}
fn csv_cell(value: &str) -> String {
    format!("\"{}\"", value.replace('"', "\"\""))
}
fn csv_response(filename: &str, header_line: &str, rows: Vec<Vec<String>>) -> Response {
    let mut out = format!("{header_line}\n");
    for row in rows {
        out.push_str(
            &row.into_iter()
                .map(|value| csv_cell(&value))
                .collect::<Vec<_>>()
                .join(","),
        );
        out.push('\n');
    }
    (
        ([
            (header::CONTENT_TYPE, "text/csv; charset=utf-8"),
            (
                header::CONTENT_DISPOSITION,
                &format!("attachment; filename={filename}"),
            ),
        ]),
        out,
    )
        .into_response()
}
pub async fn attendance_csv(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Response, ApiError> {
    let c = ctx(&s, &h).await?;
    let rows=sqlx::query_as::<_,(String,String,String,String,String)>("SELECT u.name,u.email,coalesce(a.check_in_at::text,''),coalesce(a.check_out_at::text,''),a.status FROM attendances a JOIN users u ON u.id=a.user_id WHERE a.company_id=$1 ORDER BY a.check_in_at DESC LIMIT 10000").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    let mut out = "name,email,check_in_at,check_out_at,status\n".to_string();
    for (name, email, check_in, check_out, status) in rows {
        out.push_str(&format!(
            "{},{},{},{},{}\n",
            csv_cell(&name),
            csv_cell(&email),
            csv_cell(&check_in),
            csv_cell(&check_out),
            csv_cell(&status)
        ));
    }
    Ok((
        [
            (header::CONTENT_TYPE, "text/csv; charset=utf-8"),
            (
                header::CONTENT_DISPOSITION,
                "attachment; filename=attendance-report.csv",
            ),
        ],
        out,
    )
        .into_response())
}
pub async fn payroll_csv(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Response, ApiError> {
    let c = ctx(&s, &h).await?;
    let rows=sqlx::query_as::<_,(String,String,String,String,String)>("SELECT u.full_name,u.email,p.period_start::text,p.period_end::text,r.net_amount::text FROM payroll_records r JOIN payroll_periods p ON p.id=r.period_id JOIN users u ON u.id=r.user_id WHERE r.company_id=$1 ORDER BY p.period_end DESC,u.full_name LIMIT 10000").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(csv_response(
        "payroll-report.csv",
        "employee,email,period_start,period_end,net_amount",
        rows.into_iter()
            .map(|row| vec![row.0, row.1, row.2, row.3, row.4])
            .collect(),
    ))
}
pub async fn leave_csv(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Response, ApiError> {
    let c = ctx(&s, &h).await?;
    let rows=sqlx::query_as::<_,(String,String,String,String,String,String)>("SELECT u.full_name,u.email,r.starts_on::text,r.ends_on::text,r.status,r.reason FROM workforce_requests r JOIN users u ON u.id=r.user_id WHERE r.company_id=$1 AND r.kind='leave' ORDER BY r.created_at DESC LIMIT 10000").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(csv_response(
        "leave-report.csv",
        "employee,email,starts_on,ends_on,status,reason",
        rows.into_iter()
            .map(|row| vec![row.0, row.1, row.2, row.3, row.4, row.5])
            .collect(),
    ))
}
pub async fn accounting_csv(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Response, ApiError> {
    let c = ctx(&s, &h).await?;
    let rows=sqlx::query_as::<_,(String,String,String,String,i64,i64,String)>("SELECT j.number,j.entry_date::text,j.reference,a.code,l.debit_cents,l.credit_cents,j.description FROM accounting_journals j JOIN accounting_journal_lines l ON l.journal_id=j.id JOIN accounting_accounts a ON a.id=l.account_id WHERE j.company_id=$1 AND j.status='posted' ORDER BY j.entry_date DESC,j.number LIMIT 10000").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(csv_response(
        "accounting-ledger.csv",
        "journal,entry_date,reference,account,debit_cents,credit_cents,description",
        rows.into_iter()
            .map(|row| {
                vec![
                    row.0,
                    row.1,
                    row.2,
                    row.3,
                    row.4.to_string(),
                    row.5.to_string(),
                    row.6,
                ]
            })
            .collect(),
    ))
}
