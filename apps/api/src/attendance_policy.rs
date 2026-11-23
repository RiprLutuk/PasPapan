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
    let existing=sqlx::query_scalar::<_,String>("SELECT a.work_mode FROM attendances a JOIN companies c ON c.id=a.company_id WHERE a.company_id=$1 AND a.user_id=$2 AND a.attendance_date=(now() AT TIME ZONE c.timezone)::date AND a.checked_in_at IS NOT NULL AND a.checked_out_at IS NULL")
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

/// Check-out normally uses the live QR policy.  If an administrator disabled the
/// original location while a shift was running, use the immutable check-in
/// snapshot as a narrowly-scoped recovery path and make the caller audit it.
pub async fn validate_checkout(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    mode: Option<&str>,
    qr: &str,
    latitude: f64,
    longitude: f64,
    snapshot: &Value,
) -> Result<(Option<Uuid>, bool), ApiError> {
    let expected_mode = snapshot["mode"].as_str().unwrap_or("onsite");
    let requested_mode = mode.unwrap_or("onsite");
    if requested_mode != expected_mode {
        return Err(ApiError::Conflict(
            "use the same attendance mode as check-in",
        ));
    }
    if expected_mode == "wfh" && snapshot["wfh_approved"].as_bool() == Some(true) {
        return Ok((None, false));
    }
    match attendance_locations::validate_scan(tx, context.company_id, qr, latitude, longitude).await
    {
        Ok(id) => Ok((Some(id), false)),
        Err(live_error) => {
            let location = &snapshot["location"];
            let Some(id) = location["id"]
                .as_str()
                .and_then(|raw| Uuid::parse_str(raw).ok())
            else {
                return Err(live_error);
            };
            let (Some(saved_latitude), Some(saved_longitude), Some(radius)) = (
                location["latitude"].as_f64(),
                location["longitude"].as_f64(),
                location["radius_meters"].as_i64(),
            ) else {
                return Err(live_error);
            };
            let was_disabled: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM attendance_locations WHERE company_id=$1 AND id=$2 AND active=false)")
                .bind(context.company_id).bind(id).fetch_one(&mut **tx).await.map_err(database_error)?;
            if !was_disabled
                || attendance_locations::distance_meters(
                    latitude,
                    longitude,
                    saved_latitude,
                    saved_longitude,
                ) > radius as f64
            {
                return Err(live_error);
            }
            Ok((Some(id), true))
        }
    }
}
