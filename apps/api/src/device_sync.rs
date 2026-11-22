use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Register {
    device_key: String,
    #[serde(default)]
    label: String,
    #[serde(default)]
    platform: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Mutation {
    device_key: String,
    idempotency_key: String,
    resource_type: String,
    resource_id: Uuid,
    #[serde(default)]
    payload: Value,
    #[serde(default)]
    credential: String,
    base_cursor: Option<i64>,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct QueueMedia {
    device_key: String,
    credential: String,
    idempotency_key: String,
    file_id: Option<Uuid>,
    #[serde(default)]
    metadata: Value,
}
fn digest(v: &str) -> String {
    format!("{:x}", Sha256::digest(v.as_bytes()))
}
fn credential() -> String {
    let mut bytes = [0u8; 32];
    rand::rng().fill_bytes(&mut bytes);
    bytes.iter().map(|byte| format!("{byte:02x}")).collect()
}
async fn ctx(
    s: &AppState,
    h: &axum::http::HeaderMap,
    write: bool,
) -> Result<AuthContext, ApiError> {
    if write {
        require_trusted_origin(h, &s.app_origin)?;
    }
    authenticated_context(&s.pool, h).await
}
pub async fn register(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Register>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.device_key.len() < 24 || b.device_key.len() > 512 {
        return Err(ApiError::BadRequest("invalid device key"));
    }
    let hash = digest(&b.device_key);
    let id = Uuid::now_v7();
    let issued = credential();
    let device_id=sqlx::query_scalar::<_,Uuid>("INSERT INTO device_sync_devices(id,company_id,user_id,device_key_hash,label,platform,credential_hash,credential_expires_at) VALUES($1,$2,$3,$4,$5,$6,$7,NOW()+INTERVAL '30 days') ON CONFLICT(company_id,device_key_hash) DO UPDATE SET last_seen_at=NOW(),label=EXCLUDED.label,platform=EXCLUDED.platform,credential_hash=EXCLUDED.credential_hash,credential_expires_at=EXCLUDED.credential_expires_at,revoked_at=NULL RETURNING id").bind(id).bind(c.company_id).bind(c.user_id).bind(hash).bind(b.label.trim()).bind(b.platform.trim()).bind(digest(&issued)).fetch_one(&s.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"device_id":device_id,"cursor":0,"credential":issued,"credential_expires_in_days":30}),
    ))
}
pub async fn mutate(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Mutation>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.idempotency_key.trim().is_empty()
        || b.idempotency_key.len() > 200
        || b.resource_type.trim().is_empty()
    {
        return Err(ApiError::BadRequest("invalid mutation"));
    }
    let device=sqlx::query_scalar::<_,Uuid>("SELECT id FROM device_sync_devices WHERE company_id=$1 AND user_id=$2 AND device_key_hash=$3 AND credential_hash=$4 AND credential_expires_at>NOW() AND revoked_at IS NULL").bind(c.company_id).bind(c.user_id).bind(digest(&b.device_key)).bind(digest(&b.credential)).fetch_optional(&s.pool).await.map_err(database_error)?.ok_or(ApiError::Unauthorized)?;
    // Keep the cursor check, idempotency insert, and cursor advance in one transaction.
    // A `FOR UPDATE` query outside a transaction releases its lock at statement end,
    // which can otherwise admit two stale offline mutations concurrently.
    let mut transaction = s.pool.begin().await.map_err(database_error)?;
    let current = sqlx::query_scalar::<_, i64>(
        "SELECT last_cursor FROM device_sync_devices WHERE id=$1 FOR UPDATE",
    )
    .bind(device)
    .fetch_one(&mut *transaction)
    .await
    .map_err(database_error)?;
    if let Some(base) = b.base_cursor
        && base != current
    {
        sqlx::query("UPDATE device_sync_devices SET conflict_count=conflict_count+1,last_seen_at=NOW() WHERE id=$1").bind(device).execute(&mut *transaction).await.map_err(database_error)?;
        transaction.commit().await.map_err(database_error)?;
        return Ok(Json(
            json!({"conflict":true,"cursor":current,"resolution":"refresh_then_reapply"}),
        ));
    }
    let row=sqlx::query_scalar::<_,Uuid>("INSERT INTO device_sync_mutations(id,company_id,device_id,idempotency_key,resource_type,resource_id,payload) VALUES($1,$2,$3,$4,$5,$6,$7) ON CONFLICT(device_id,idempotency_key) DO NOTHING RETURNING id").bind(Uuid::now_v7()).bind(c.company_id).bind(device).bind(b.idempotency_key.trim()).bind(b.resource_type.trim()).bind(b.resource_id).bind(b.payload).fetch_optional(&mut *transaction).await.map_err(database_error)?;
    let (row, cursor, duplicate) = if let Some(row) = row {
        let cursor=sqlx::query_scalar::<_,i64>("UPDATE device_sync_devices SET last_cursor=last_cursor+1,last_seen_at=NOW() WHERE id=$1 RETURNING last_cursor").bind(device).fetch_one(&mut *transaction).await.map_err(database_error)?;
        (row, cursor, false)
    } else {
        let row = sqlx::query_scalar::<_, Uuid>(
            "SELECT id FROM device_sync_mutations WHERE device_id=$1 AND idempotency_key=$2",
        )
        .bind(device)
        .bind(b.idempotency_key.trim())
        .fetch_one(&mut *transaction)
        .await
        .map_err(database_error)?;
        (row, current, true)
    };
    transaction.commit().await.map_err(database_error)?;
    Ok(Json(
        json!({"mutation_id":row,"cursor":cursor,"duplicate":duplicate,"conflict":false}),
    ))
}
pub async fn queue_media(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<QueueMedia>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.idempotency_key.trim().is_empty() || b.credential.len() < 32 {
        return Err(ApiError::BadRequest("invalid media queue request"));
    }
    let device=sqlx::query_scalar::<_,Uuid>("SELECT id FROM device_sync_devices WHERE company_id=$1 AND user_id=$2 AND device_key_hash=$3 AND credential_hash=$4 AND credential_expires_at>NOW() AND revoked_at IS NULL").bind(c.company_id).bind(c.user_id).bind(digest(&b.device_key)).bind(digest(&b.credential)).fetch_optional(&s.pool).await.map_err(database_error)?.ok_or(ApiError::Unauthorized)?;
    if let Some(file) = b.file_id {
        let valid=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM private_files WHERE id=$1 AND company_id=$2 AND uploaded_by=$3)").bind(file).bind(c.company_id).bind(c.user_id).fetch_one(&s.pool).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("media file not found"));
        }
    }
    let id = Uuid::now_v7();
    let queued=sqlx::query_scalar::<_,Uuid>("INSERT INTO device_sync_media_queue(id,company_id,device_id,file_id,idempotency_key,metadata) VALUES($1,$2,$3,$4,$5,$6) ON CONFLICT(device_id,idempotency_key) DO UPDATE SET idempotency_key=EXCLUDED.idempotency_key RETURNING id").bind(id).bind(c.company_id).bind(device).bind(b.file_id).bind(b.idempotency_key.trim()).bind(b.metadata).fetch_one(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":queued,"status":"queued"})))
}
