use super::*;
use serde_json::{Value, json};

pub async fn policy(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let allowed:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM workforce_requests q JOIN companies c ON c.id=q.company_id WHERE q.company_id=$1 AND q.user_id=$2 AND q.kind='wfh' AND q.status='approved' AND (now() AT TIME ZONE c.timezone)::date BETWEEN q.starts_on AND q.ends_on)")
        .bind(context.company_id).bind(context.user_id).fetch_one(&state.pool).await.map_err(database_error)?;
    Ok(Json(json!({"wfh_allowed":allowed})))
}
pub async fn validate(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    mode: Option<&str>,
    qr: &str,
    latitude: f64,
    longitude: f64,
) -> Result<Option<Uuid>, ApiError> {
    sqlx::query(
        "SELECT user_id FROM company_memberships WHERE company_id=$1 AND user_id=$2 FOR NO KEY UPDATE",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .execute(&mut **tx)
    .await
    .map_err(database_error)?;
    let existing=sqlx::query_scalar::<_,String>("SELECT a.work_mode FROM attendances a JOIN companies c ON c.id=a.company_id WHERE a.company_id=$1 AND a.user_id=$2 AND a.attendance_date=(now() AT TIME ZONE c.timezone)::date AND a.checked_in_at IS NOT NULL")
        .bind(context.company_id).bind(context.user_id).fetch_optional(&mut **tx).await.map_err(database_error)?;
    if existing.is_some_and(|value| value != mode.unwrap_or("onsite")) {
        return Err(ApiError::Conflict(
            "use the same attendance mode as check-in",
        ));
    }
    match mode.unwrap_or("onsite") {
        "onsite" => Ok(Some(
            attendance_locations::validate_scan(tx, context.company_id, qr, latitude, longitude)
                .await?,
        )),
        "wfh" => {
            let approved=sqlx::query_scalar::<_,Uuid>("SELECT q.id FROM workforce_requests q JOIN companies c ON c.id=q.company_id WHERE q.company_id=$1 AND q.user_id=$2 AND q.kind='wfh' AND q.status='approved' AND (now() AT TIME ZONE c.timezone)::date BETWEEN q.starts_on AND q.ends_on ORDER BY q.id LIMIT 1 FOR SHARE OF q")
                .bind(context.company_id).bind(context.user_id).fetch_optional(&mut **tx).await.map_err(database_error)?;
            if approved.is_none() {
                return Err(ApiError::Forbidden);
            }
            Ok(None)
        }
        _ => Err(ApiError::BadRequest("invalid attendance mode")),
    }
}
