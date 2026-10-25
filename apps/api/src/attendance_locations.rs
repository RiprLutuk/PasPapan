use super::*;
use axum::extract::Path;
use serde::Deserialize;
use serde_json::Value;

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct CreateLocationRequest {
    name: String,
    latitude: f64,
    longitude: f64,
    radius_meters: i32,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct LocationStatusRequest {
    active: bool,
}

pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::BARCODES_MANAGE)?;
    let locations = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object('id',id,'name',name,'qr_token',qr_token,'qr_expires_at',qr_expires_at,'latitude',latitude,'longitude',longitude,'radius_meters',radius_meters,'active',active) \
         FROM attendance_locations WHERE company_id=$1 ORDER BY lower(name),id",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(locations))
}

pub async fn create(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<CreateLocationRequest>,
) -> Result<(StatusCode, Json<Value>), ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::BARCODES_MANAGE)?;
    let name = request.name.trim();
    if name.is_empty() || name.len() > 120 {
        return Err(ApiError::BadRequest(
            "location name must be 1 to 120 characters",
        ));
    }
    validate_coordinates(request.latitude, request.longitude)?;
    if !(1..=10_000).contains(&request.radius_meters) {
        return Err(ApiError::BadRequest(
            "location radius must be between 1 and 10000 meters",
        ));
    }

    let mut token_bytes = [0u8; 32];
    rand::rng().fill_bytes(&mut token_bytes);
    let qr_token = token_bytes
        .iter()
        .map(|byte| format!("{byte:02x}"))
        .collect::<String>();
    let id = Uuid::now_v7();
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let location = sqlx::query_scalar::<_, Value>(
        "INSERT INTO attendance_locations(id,company_id,name,qr_token,latitude,longitude,radius_meters,qr_expires_at) \
         VALUES($1,$2,$3,$4,$5,$6,$7,clock_timestamp()+interval '30 seconds') \
         RETURNING jsonb_build_object('id',id,'name',name,'qr_token',qr_token,'qr_expires_at',qr_expires_at,'latitude',latitude,'longitude',longitude,'radius_meters',radius_meters,'active',active)",
    )
    .bind(id)
    .bind(context.company_id)
    .bind(name)
    .bind(&qr_token)
    .bind(request.latitude)
    .bind(request.longitude)
    .bind(request.radius_meters)
    .fetch_one(&mut *transaction)
    .await
    .map_err(database_error)?;
    sqlx::query(
        "INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) \
         VALUES($1,$2,$3,'attendance.location.created','attendance_location',$4,jsonb_build_object('name',$5,'radius_meters',$6))",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(id)
    .bind(name)
    .bind(request.radius_meters)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;
    Ok((StatusCode::CREATED, Json(location)))
}

pub async fn set_status(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(request): Json<LocationStatusRequest>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::BARCODES_MANAGE)?;
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let previous = sqlx::query_scalar::<_, bool>(
        "WITH previous AS ( \
             SELECT active FROM attendance_locations WHERE company_id=$2 AND id=$3 FOR UPDATE \
         ), updated AS ( \
             UPDATE attendance_locations SET active=$1,updated_at=NOW(),qr_expires_at=clock_timestamp() \
             WHERE company_id=$2 AND id=$3 RETURNING id \
         ) SELECT previous.active FROM previous JOIN updated ON true",
    )
    .bind(request.active)
    .bind(context.company_id)
    .bind(id)
    .fetch_optional(&mut *transaction)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("attendance location not found"))?;
    sqlx::query(
        "INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) \
         VALUES($1,$2,$3,'attendance.location.status_changed','attendance_location',$4,jsonb_build_object('before',jsonb_build_object('active',$5),'after',jsonb_build_object('active',$6)))",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(id)
    .bind(previous)
    .bind(request.active)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

// Multiple displays share the same live token. Renew only after expiry, so opening
// another display does not invalidate a QR that an employee is currently scanning.
pub async fn refresh(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::BARCODES_MANAGE)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let expired = sqlx::query_scalar::<_, Uuid>(
        "SELECT id FROM attendance_locations WHERE company_id=$1 AND active=true \
         AND qr_expires_at<=clock_timestamp() ORDER BY id FOR UPDATE",
    )
    .bind(context.company_id)
    .fetch_all(&mut *tx)
    .await
    .map_err(database_error)?;
    for id in expired {
        let mut bytes = [0u8; 32];
        rand::rng().fill_bytes(&mut bytes);
        let token = bytes
            .iter()
            .map(|byte| format!("{byte:02x}"))
            .collect::<String>();
        sqlx::query("UPDATE attendance_locations SET qr_token=$1,qr_expires_at=clock_timestamp()+interval '30 seconds' WHERE id=$2")
            .bind(token).bind(id).execute(&mut *tx).await.map_err(database_error)?;
    }
    tx.commit().await.map_err(database_error)?;
    list(State(state), headers).await
}

pub(super) async fn validate_scan(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    company_id: Uuid,
    qr_token: &str,
    latitude: f64,
    longitude: f64,
) -> Result<Uuid, ApiError> {
    if qr_token.len() != 64 || !qr_token.bytes().all(|byte| byte.is_ascii_hexdigit()) {
        return Err(ApiError::BadRequest("invalid attendance QR code"));
    }
    let normalized_token = qr_token.to_ascii_lowercase();
    let location = sqlx::query_as::<_, (Uuid, f64, f64, i32)>(
        "SELECT id,latitude,longitude,radius_meters FROM attendance_locations \
         WHERE company_id=$1 AND qr_token=$2 AND active=true AND qr_expires_at>clock_timestamp() FOR SHARE",
    )
    .bind(company_id)
    .bind(normalized_token)
    .fetch_optional(&mut **transaction)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("QR tidak berlaku atau kedaluwarsa. Pindai ulang QR terbaru di layar lokasi."))?;

    let distance = distance_meters(latitude, longitude, location.1, location.2);
    if distance > f64::from(location.3) {
        return Err(ApiError::BadRequest(
            "you are outside the allowed location radius",
        ));
    }
    Ok(location.0)
}

fn distance_meters(latitude_a: f64, longitude_a: f64, latitude_b: f64, longitude_b: f64) -> f64 {
    let radians = std::f64::consts::PI / 180.0;
    let delta_latitude = (latitude_b - latitude_a) * radians;
    let delta_longitude = (longitude_b - longitude_a) * radians;
    let haversine = (delta_latitude / 2.0).sin().powi(2)
        + latitude_a.to_radians().cos()
            * latitude_b.to_radians().cos()
            * (delta_longitude / 2.0).sin().powi(2);
    6_371_000.0 * 2.0 * haversine.sqrt().atan2((1.0 - haversine).sqrt())
}

#[cfg(test)]
mod tests {
    use super::distance_meters;

    #[test]
    fn measures_zero_and_known_distances() {
        assert_eq!(distance_meters(-6.2, 106.8, -6.2, 106.8), 0.0);
        let distance = distance_meters(-6.2, 106.8, -6.2, 106.801);
        assert!((distance - 111.2).abs() < 1.0);
    }
}
