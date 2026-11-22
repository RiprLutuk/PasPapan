use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Schedule {
    report_key: String,
    format: String,
    frequency: String,
    recipient_email: String,
}
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
fn pdf_escape(value: &str) -> String {
    value
        .replace('\\', "\\\\")
        .replace('(', "\\(")
        .replace(')', "\\)")
}
fn pdf_response(filename: &str, title: &str, lines: Vec<String>) -> Response {
    let mut text = format!(
        "BT /F1 18 Tf 50 790 Td ({}) Tj /F1 10 Tf 0 -28 Td ",
        pdf_escape(title)
    );
    for line in lines.into_iter().take(45) {
        text.push_str(&format!("({}) Tj 0 -15 Td ", pdf_escape(&line)));
    }
    text.push_str("ET");
    let objects=["<< /Type /Catalog /Pages 2 0 R >>".to_owned(),"<< /Type /Pages /Kids [3 0 R] /Count 1 >>".to_owned(),"<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>".to_owned(),"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>".to_owned(),format!("<< /Length {} >>\nstream\n{}\nendstream",text.len(),text)];
    let mut out = b"%PDF-1.4\n".to_vec();
    let mut offsets = Vec::new();
    for (index, obj) in objects.iter().enumerate() {
        offsets.push(out.len());
        out.extend_from_slice(format!("{} 0 obj\n{}\nendobj\n", index + 1, obj).as_bytes());
    }
    let xref = out.len();
    out.extend_from_slice(
        format!("xref\n0 {}\n0000000000 65535 f \n", objects.len() + 1).as_bytes(),
    );
    for offset in offsets {
        out.extend_from_slice(format!("{offset:010} 00000 n \n").as_bytes());
    }
    out.extend_from_slice(
        format!(
            "trailer << /Size {} /Root 1 0 R >>\nstartxref\n{xref}\n%%EOF",
            objects.len() + 1
        )
        .as_bytes(),
    );
    (
        [
            (header::CONTENT_TYPE, "application/pdf"),
            (
                header::CONTENT_DISPOSITION,
                &format!("attachment; filename={filename}"),
            ),
        ],
        out,
    )
        .into_response()
}
async fn pdf_lines(s: &AppState, company_id: Uuid, key: &str) -> Result<Vec<String>, ApiError> {
    let rows=match key {"attendance"=>sqlx::query_scalar::<_,String>("SELECT concat(u.full_name,' — ',a.attendance_date::text,' — ',CASE WHEN a.checked_in_at IS NULL THEN 'Belum check-in' ELSE 'Hadir' END) FROM attendances a JOIN users u ON u.id=a.user_id WHERE a.company_id=$1 ORDER BY a.attendance_date DESC LIMIT 40").bind(company_id).fetch_all(&s.pool).await.map_err(database_error)?,"payroll"=>sqlx::query_scalar::<_,String>("SELECT concat(u.full_name,' — ',p.period_end::text,' — Rp ',r.net_amount::text) FROM payroll_records r JOIN payroll_periods p ON p.id=r.period_id JOIN users u ON u.id=r.user_id WHERE r.company_id=$1 ORDER BY p.period_end DESC LIMIT 40").bind(company_id).fetch_all(&s.pool).await.map_err(database_error)?,"leave"=>sqlx::query_scalar::<_,String>("SELECT concat(u.full_name,' — ',r.starts_on::text,' s/d ',r.ends_on::text,' — ',r.status) FROM workforce_requests r JOIN users u ON u.id=r.user_id WHERE r.company_id=$1 AND r.kind='leave' ORDER BY r.created_at DESC LIMIT 40").bind(company_id).fetch_all(&s.pool).await.map_err(database_error)?,_=>sqlx::query_scalar::<_,String>("SELECT concat(j.number,' — ',j.entry_date::text,' — ',a.code,' — D ',l.debit_cents::text,' / K ',l.credit_cents::text) FROM accounting_journals j JOIN accounting_journal_lines l ON l.journal_id=j.id JOIN accounting_accounts a ON a.id=l.account_id WHERE j.company_id=$1 AND j.status='posted' ORDER BY j.entry_date DESC LIMIT 40").bind(company_id).fetch_all(&s.pool).await.map_err(database_error)?};
    Ok(rows)
}
pub async fn report_pdf(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Path(key): Path<String>,
) -> Result<Response, ApiError> {
    let c = ctx(&s, &h).await?;
    if !matches!(
        key.as_str(),
        "attendance" | "payroll" | "leave" | "accounting"
    ) {
        return Err(ApiError::BadRequest("unknown report"));
    }
    Ok(pdf_response(
        &format!("{key}-report.pdf"),
        &format!("PasPapan — {} report", key),
        pdf_lines(&s, c.company_id, &key).await?,
    ))
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
pub async fn schedules(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let c = ctx(&s, &h).await?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'report_key',report_key,'format',format,'frequency',frequency,'recipient_email',recipient_email,'is_active',is_active,'next_run_at',next_run_at) FROM report_schedules WHERE company_id=$1 ORDER BY created_at DESC")
        .bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn create_schedule(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Schedule>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&h, &s.app_origin)?;
    let c = ctx(&s, &h).await?;
    if !matches!(
        b.report_key.as_str(),
        "attendance" | "payroll" | "leave" | "accounting"
    ) || !matches!(b.format.as_str(), "csv" | "pdf")
        || !matches!(b.frequency.as_str(), "daily" | "weekly" | "monthly")
        || !b.recipient_email.contains('@')
        || b.recipient_email.len() > 320
    {
        return Err(ApiError::BadRequest("invalid report schedule"));
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO report_schedules(id,company_id,report_key,format,frequency,recipient_email,next_run_at,created_by) VALUES($1,$2,$3,$4,$5,$6,NOW(),$7)").bind(id).bind(c.company_id).bind(b.report_key).bind(b.format).bind(b.frequency).bind(b.recipient_email.trim()).bind(c.user_id).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
