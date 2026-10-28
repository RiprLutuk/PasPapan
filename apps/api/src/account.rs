use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

pub async fn profile(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let c = authenticated_context(&state.pool, &headers).await?;
    let record = sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('full_name',u.full_name,'email',u.email,'phone',p.phone,'address',p.address,'team',p.team,'job_title',p.job_title,'employee_code',p.employee_code,'version',p.version) FROM users u JOIN employee_profiles p ON p.user_id=u.id WHERE p.company_id=$1 AND u.id=$2")
        .bind(c.company_id).bind(c.user_id).fetch_one(&state.pool).await.map_err(database_error)?;
    Ok(Json(record))
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct ProfileInput {
    full_name: String,
    phone: String,
    address: String,
    version: i64,
}

pub async fn save_profile(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<ProfileInput>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let c = authenticated_context(&state.pool, &headers).await?;
    if body.full_name.trim().is_empty()
        || body.full_name.len() > 200
        || body.phone.len() > 64
        || body.address.len() > 500
    {
        return Err(ApiError::BadRequest("invalid profile details"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, c.company_id).await?;
    let before = sqlx::query_as::<_,(String,String,i64)>("SELECT phone,address,version FROM employee_profiles WHERE company_id=$1 AND user_id=$2 FOR UPDATE")
        .bind(c.company_id).bind(c.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if before.2 != body.version {
        return Err(ApiError::Conflict("profile changed; reload before saving"));
    }
    let name =
        sqlx::query_scalar::<_, String>("SELECT full_name FROM users WHERE id=$1 FOR UPDATE")
            .bind(c.user_id)
            .fetch_one(&mut *tx)
            .await
            .map_err(database_error)?;
    sqlx::query("UPDATE users SET full_name=$2,updated_at=now() WHERE id=$1")
        .bind(c.user_id)
        .bind(body.full_name.trim())
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    sqlx::query("UPDATE employee_profiles SET phone=$3,address=$4,version=version+1,updated_at=now() WHERE company_id=$1 AND user_id=$2")
        .bind(c.company_id).bind(c.user_id).bind(body.phone.trim()).bind(body.address.trim()).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(&mut tx,&c,c.user_id,"account.profile_updated",json!({"before":{"full_name":name,"phone":before.0,"address":before.1},"after":{"full_name":body.full_name.trim(),"phone":body.phone.trim(),"address":body.address.trim()}})).await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct PasswordInput {
    current_password: String,
    new_password: String,
}

async fn verify_current(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    user: Uuid,
    password: &str,
) -> Result<(), ApiError> {
    if password.is_empty() || password.len() > 1024 {
        return Err(ApiError::BadRequest("invalid current password"));
    }
    let hash =
        sqlx::query_scalar::<_, String>("SELECT password_hash FROM users WHERE id=$1 FOR UPDATE")
            .bind(user)
            .fetch_one(&mut **tx)
            .await
            .map_err(database_error)?;
    verify_password(password, &hash)
}

pub async fn change_password(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<PasswordInput>,
) -> Result<Response, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let c = authenticated_context(&state.pool, &headers).await?;
    if !(12..=1024).contains(&body.new_password.len()) {
        return Err(ApiError::BadRequest("password must contain 12–1024 bytes"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    verify_current(&mut tx, c.user_id, &body.current_password).await?;
    let hash = hash_password(&body.new_password)?;
    sqlx::query("UPDATE users SET password_hash=$2,updated_at=now() WHERE id=$1")
        .bind(c.user_id)
        .bind(hash)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    sqlx::query("UPDATE sessions SET revoked_at=now() WHERE user_id=$1 AND revoked_at IS NULL")
        .bind(c.user_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    people::audit(
        &mut tx,
        &c,
        c.user_id,
        "account.password_changed",
        json!({}),
    )
    .await?;
    let response = create_session_in_company(&mut tx, c.user_id, Some(c.company_id), None).await?;
    tx.commit().await.map_err(database_error)?;
    Ok(response)
}

pub async fn sessions(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let c = authenticated_context(&state.pool, &headers).await?;
    let token = session_token(&headers).ok_or(ApiError::Unauthorized)?;
    let hash = hash_session_token(token);
    let records = sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'created_at',created_at,'expires_at',expires_at,'last_active_at',last_active_at,'idle_expires_at',idle_expires_at,'current',token_hash=$2) FROM sessions WHERE user_id=$1 AND revoked_at IS NULL AND expires_at>now() AND idle_expires_at>now() ORDER BY created_at DESC,id DESC LIMIT 100")
        .bind(c.user_id).bind(hash).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(json!(records)))
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct RevokeInput {
    current_password: String,
}

pub async fn revoke_session(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<RevokeInput>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let c = authenticated_context(&state.pool, &headers).await?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    verify_current(&mut tx, c.user_id, &body.current_password).await?;
    let changed = sqlx::query(
        "UPDATE sessions SET revoked_at=COALESCE(revoked_at,now()) WHERE id=$1 AND user_id=$2",
    )
    .bind(id)
    .bind(c.user_id)
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;
    if changed.rows_affected() == 0 {
        return Err(ApiError::BadRequest("session not found"));
    }
    people::audit(
        &mut tx,
        &c,
        c.user_id,
        "account.session_revoked",
        json!({"session_id":id}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
