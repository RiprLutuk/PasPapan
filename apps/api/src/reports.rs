use super::*;
async fn ctx(s: &AppState, h: &axum::http::HeaderMap) -> Result<AuthContext, ApiError> {
    let c = authenticated_context(&s.pool, h).await?;
    require_capability(&c, access::REPORTS_VIEW)?;
    Ok(c)
}
fn csv_cell(value: &str) -> String {
    format!("\"{}\"", value.replace('"', "\"\""))
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
