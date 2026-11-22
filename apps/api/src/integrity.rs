use super::*;
use axum::extract::Path;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct ReviewEvent {
    status: String,
    #[serde(default)]
    note: String,
}

async fn context(
    state: &AppState,
    headers: &axum::http::HeaderMap,
    write: bool,
) -> Result<AuthContext, ApiError> {
    if write {
        require_trusted_origin(headers, &state.app_origin)?;
    }
    let context = authenticated_context(&state.pool, headers).await?;
    require_capability(&context, access::ATTENDANCE_INTEGRITY_VIEW)?;
    Ok(context)
}

pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = context(&state, &headers, false).await?;
    let rows = sqlx::query_scalar::<_, Value>("SELECT jsonb_build_object('id',e.id,'event_type',e.event_type,'severity',e.severity,'review_status',e.review_status,'metadata',e.metadata,'created_at',e.created_at,'reviewed_at',e.reviewed_at,'resolution_note',e.resolution_note,'employee',u.full_name) FROM attendance_integrity_events e JOIN users u ON u.id=e.user_id WHERE e.company_id=$1 ORDER BY CASE e.review_status WHEN 'open' THEN 0 ELSE 1 END, CASE e.severity WHEN 'high' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END, e.created_at DESC LIMIT 200")
        .bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}

pub async fn review(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<ReviewEvent>,
) -> Result<Json<Value>, ApiError> {
    let context = context(&state, &headers, true).await?;
    require_capability(&context, access::ATTENDANCES_MANAGE)?;
    if !matches!(body.status.as_str(), "resolved" | "dismissed") || body.note.chars().count() > 1000
    {
        return Err(ApiError::BadRequest("invalid integrity review"));
    }
    let updated = sqlx::query("UPDATE attendance_integrity_events SET review_status=$1,reviewed_by=$2,reviewed_at=NOW(),resolution_note=$3 WHERE id=$4 AND company_id=$5 AND review_status='open'")
        .bind(&body.status).bind(context.user_id).bind(body.note.trim()).bind(id).bind(context.company_id).execute(&state.pool).await.map_err(database_error)?;
    if updated.rows_affected() == 0 {
        return Err(ApiError::BadRequest(
            "integrity event not found or already reviewed",
        ));
    }
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) VALUES($1,$2,$3,'attendance.integrity_reviewed','attendance_integrity_event',$4,$5)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(id).bind(json!({"status":body.status})).execute(&state.pool).await.map_err(database_error)?;
    Ok(Json(json!({"ok":true})))
}

pub async fn record_location_signals(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    latitude: f64,
    longitude: f64,
    accuracy_meters: Option<f64>,
) -> Result<(), ApiError> {
    if let Some(accuracy) = accuracy_meters.filter(|accuracy| *accuracy >= 250.0) {
        add_event(
            transaction,
            context,
            "low_accuracy",
            if accuracy >= 1_000.0 {
                "high"
            } else {
                "warning"
            },
            json!({"accuracy_meters":accuracy}),
        )
        .await?;
    }
    let previous = sqlx::query_as::<_, (f64, f64, f64)>("SELECT latitude,longitude,EXTRACT(EPOCH FROM NOW()-occurred_at)::float8 FROM (SELECT check_in_latitude AS latitude,check_in_longitude AS longitude,checked_in_at AS occurred_at FROM attendances WHERE company_id=$1 AND user_id=$2 AND checked_in_at IS NOT NULL UNION ALL SELECT check_out_latitude,check_out_longitude,checked_out_at FROM attendances WHERE company_id=$1 AND user_id=$2 AND checked_out_at IS NOT NULL) p WHERE occurred_at < NOW() ORDER BY occurred_at DESC LIMIT 1")
        .bind(context.company_id).bind(context.user_id).fetch_optional(&mut **transaction).await.map_err(database_error)?;
    if let Some((last_latitude, last_longitude, seconds)) = previous
        && seconds > 0.0
        && seconds <= 21_600.0
    {
        let distance_km = haversine_km(last_latitude, last_longitude, latitude, longitude);
        let speed_kmh = distance_km * 3_600.0 / seconds;
        if speed_kmh > 250.0 {
            add_event(transaction, context, "impossible_speed", "high", json!({"distance_km":round(distance_km),"speed_kmh":round(speed_kmh),"seconds_since_previous":seconds})).await?;
        }
    }
    let nearby_users = sqlx::query_scalar::<_, i64>("SELECT COUNT(DISTINCT user_id) FROM (SELECT user_id,check_in_latitude AS latitude,check_in_longitude AS longitude FROM attendances WHERE company_id=$1 AND user_id<>$2 AND checked_in_at > NOW()-INTERVAL '10 minutes' UNION ALL SELECT user_id,check_out_latitude,check_out_longitude FROM attendances WHERE company_id=$1 AND user_id<>$2 AND checked_out_at > NOW()-INTERVAL '10 minutes') p WHERE abs(latitude-$3)<0.00001 AND abs(longitude-$4)<0.00001")
        .bind(context.company_id).bind(context.user_id).bind(latitude).bind(longitude).fetch_one(&mut **transaction).await.map_err(database_error)?;
    if nearby_users >= 3 {
        add_event(
            transaction,
            context,
            "reused_coordinates",
            "warning",
            json!({"nearby_distinct_users":nearby_users,"window_minutes":10,"precision_meters":2}),
        )
        .await?;
    }
    Ok(())
}

async fn add_event(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    event_type: &str,
    severity: &str,
    metadata: Value,
) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO attendance_integrity_events(id,company_id,user_id,event_type,severity,metadata) VALUES($1,$2,$3,$4,$5,$6)").bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(event_type).bind(severity).bind(metadata).execute(&mut **transaction).await.map_err(database_error)?;
    Ok(())
}
fn haversine_km(lat1: f64, lon1: f64, lat2: f64, lon2: f64) -> f64 {
    let dlat = (lat2 - lat1).to_radians();
    let dlon = (lon2 - lon1).to_radians();
    let a = (dlat / 2.0).sin().powi(2)
        + lat1.to_radians().cos() * lat2.to_radians().cos() * (dlon / 2.0).sin().powi(2);
    6_371.0 * 2.0 * a.sqrt().atan2((1.0 - a).sqrt())
}
fn round(value: f64) -> f64 {
    (value * 100.0).round() / 100.0
}
#[cfg(test)]
mod tests {
    use super::haversine_km;
    #[test]
    fn calculates_geodesic_distance() {
        assert!((haversine_km(-6.2, 106.8, -6.3, 106.9) - 15.67).abs() < 0.2);
    }
}
