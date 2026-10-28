use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SwitchCompany {
    company_id: Uuid,
}

pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let token_hash = hash_session_token(session_token(&headers).ok_or(ApiError::Unauthorized)?);
    let rows = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object('id',c.id,'name',c.name,'role',m.role,'current',c.id=$2)
         FROM company_memberships m JOIN companies c ON c.id=m.company_id
         JOIN sessions s ON s.user_id=m.user_id AND s.token_hash=$3
         WHERE m.user_id=$1 AND m.employment_status='active'
         AND (m.access_revoked_at IS NULL OR s.created_at>m.access_revoked_at)
         ORDER BY c.name,c.id",
    )
    .bind(context.user_id)
    .bind(context.company_id)
    .bind(token_hash)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(rows))
}

pub async fn switch(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SwitchCompany>,
) -> Result<Response, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let token_hash = hash_session_token(session_token(&headers).ok_or(ApiError::Unauthorized)?);
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let valid: bool = sqlx::query_scalar(
        "SELECT EXISTS(SELECT 1 FROM company_memberships m JOIN sessions s ON s.user_id=m.user_id
         WHERE s.token_hash=$1 AND s.revoked_at IS NULL AND s.expires_at>now() AND s.idle_expires_at>now()
         AND m.company_id=$2 AND m.user_id=$3 AND m.employment_status='active'
         AND (m.access_revoked_at IS NULL OR s.created_at>m.access_revoked_at))",
    )
    .bind(&token_hash)
    .bind(body.company_id)
    .bind(context.user_id)
    .fetch_one(&mut *tx)
    .await
    .map_err(database_error)?;
    if !valid {
        return Err(ApiError::Forbidden);
    }
    let revoked=sqlx::query("UPDATE sessions SET revoked_at=now() WHERE token_hash=$1 AND user_id=$2 AND revoked_at IS NULL")
        .bind(&token_hash).bind(context.user_id).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if revoked != 1 {
        return Err(ApiError::Unauthorized);
    }
    let response =
        create_session_in_company(&mut tx, context.user_id, Some(body.company_id)).await?;
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) VALUES($1,$2,$3,'session.tenant_switched','company',$2,$4)")
        .bind(Uuid::now_v7()).bind(body.company_id).bind(context.user_id).bind(json!({"previous_company_id":context.company_id})).execute(&mut *tx).await.map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(response)
}

pub async fn limit_auth(
    pool: &PgPool,
    action: &str,
    identity: &str,
    limit: i32,
) -> Result<(), ApiError> {
    sqlx::query("DELETE FROM auth_attempt_windows WHERE started_at < now()-interval '1 day'")
        .execute(pool)
        .await
        .map_err(database_error)?;
    let key = hex_encode(&Sha256::digest(
        format!("{action}:{}", normalise_email(identity)).as_bytes(),
    ));
    let attempts:i32=sqlx::query_scalar(
        "INSERT INTO auth_attempt_windows(bucket_hash,attempts,started_at) VALUES($1,1,now())
         ON CONFLICT(bucket_hash) DO UPDATE SET
         attempts=CASE WHEN auth_attempt_windows.started_at<=now()-interval '15 minutes' THEN 1 ELSE LEAST(auth_attempt_windows.attempts+1,1000000) END,
         started_at=CASE WHEN auth_attempt_windows.started_at<=now()-interval '15 minutes' THEN now() ELSE auth_attempt_windows.started_at END
         RETURNING attempts",
    ).bind(key).fetch_one(pool).await.map_err(database_error)?;
    if attempts > limit {
        return Err(ApiError::RateLimited);
    }
    Ok(())
}
