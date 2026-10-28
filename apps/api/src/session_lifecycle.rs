use super::*;

pub fn limits() -> Result<(i64, i64), &'static str> {
    fn read(key: &str, default: i64) -> Result<i64, &'static str> {
        match paspapan_adapters::env_var(key) {
            Ok(v) => v.parse().map_err(|_| "invalid session timeout"),
            Err(_) => Ok(default),
        }
    }
    let absolute = read("SESSION_TTL_SECONDS", 28800)?;
    let idle = read("SESSION_IDLE_SECONDS", 1800)?;
    if !(300..=86400).contains(&absolute) || !(60..=absolute).contains(&idle) {
        return Err(
            "session timeouts must satisfy 60 <= idle <= absolute <= 86400, absolute >= 300",
        );
    }
    Ok((absolute, idle))
}
#[derive(serde::Serialize)]
pub struct Lifetime {
    id: Uuid,
    expires_at: i64,
    idle_expires_at: i64,
    idle_timeout_seconds: i64,
    server_time: i64,
}
pub async fn lifetime(
    pool: &PgPool,
    headers: &axum::http::HeaderMap,
    context: &AuthContext,
) -> Result<Lifetime, ApiError> {
    let hash = hash_session_token(session_token(headers).ok_or(ApiError::SessionExpired)?);
    let (id,absolute,idle,timeout,now)=sqlx::query_as::<_,(Uuid,i64,i64,i32,i64)>("SELECT id,extract(epoch FROM expires_at)::bigint,extract(epoch FROM idle_expires_at)::bigint,idle_timeout_seconds,extract(epoch FROM clock_timestamp())::bigint FROM sessions WHERE token_hash=$1 AND user_id=$2 AND company_id=$3 AND revoked_at IS NULL AND expires_at>clock_timestamp() AND idle_expires_at>clock_timestamp()")
        .bind(hash).bind(context.user_id).bind(context.company_id).fetch_optional(pool).await.map_err(database_error)?.ok_or(ApiError::SessionExpired)?;
    Ok(Lifetime {
        id,
        expires_at: absolute,
        idle_expires_at: idle,
        idle_timeout_seconds: i64::from(timeout),
        server_time: now,
    })
}
pub async fn activity(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Lifetime>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let hash = hash_session_token(session_token(&headers).ok_or(ApiError::SessionExpired)?);
    let changed=sqlx::query("UPDATE sessions SET last_active_at=clock_timestamp(),idle_expires_at=least(expires_at,clock_timestamp()+idle_timeout_seconds*interval '1 second') WHERE token_hash=$1 AND user_id=$2 AND company_id=$3 AND revoked_at IS NULL AND expires_at>clock_timestamp() AND idle_expires_at>clock_timestamp()")
        .bind(hash).bind(context.user_id).bind(context.company_id).execute(&state.pool).await.map_err(database_error)?;
    if changed.rows_affected() != 1 {
        return Err(ApiError::SessionExpired);
    }
    Ok(Json(lifetime(&state.pool, &headers, &context).await?))
}
