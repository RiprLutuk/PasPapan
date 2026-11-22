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
}
fn digest(v: &str) -> String {
    format!("{:x}", Sha256::digest(v.as_bytes()))
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
    let device_id=sqlx::query_scalar::<_,Uuid>("INSERT INTO device_sync_devices(id,company_id,user_id,device_key_hash,label,platform) VALUES($1,$2,$3,$4,$5,$6) ON CONFLICT(company_id,device_key_hash) DO UPDATE SET last_seen_at=NOW(),label=EXCLUDED.label,platform=EXCLUDED.platform,revoked_at=NULL RETURNING id").bind(id).bind(c.company_id).bind(c.user_id).bind(hash).bind(b.label.trim()).bind(b.platform.trim()).fetch_one(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"device_id":device_id,"cursor":0})))
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
    let device=sqlx::query_scalar::<_,Uuid>("SELECT id FROM device_sync_devices WHERE company_id=$1 AND user_id=$2 AND device_key_hash=$3 AND revoked_at IS NULL").bind(c.company_id).bind(c.user_id).bind(digest(&b.device_key)).fetch_optional(&s.pool).await.map_err(database_error)?.ok_or(ApiError::Unauthorized)?;
    let row=sqlx::query_scalar::<_,Uuid>("INSERT INTO device_sync_mutations(id,company_id,device_id,idempotency_key,resource_type,resource_id,payload) VALUES($1,$2,$3,$4,$5,$6,$7) ON CONFLICT(device_id,idempotency_key) DO UPDATE SET idempotency_key=EXCLUDED.idempotency_key RETURNING id").bind(Uuid::now_v7()).bind(c.company_id).bind(device).bind(b.idempotency_key.trim()).bind(b.resource_type.trim()).bind(b.resource_id).bind(b.payload).fetch_one(&s.pool).await.map_err(database_error)?;
    let cursor=sqlx::query_scalar::<_,i64>("UPDATE device_sync_devices SET last_cursor=last_cursor+1,last_seen_at=NOW() WHERE id=$1 RETURNING last_cursor").bind(device).fetch_one(&s.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"mutation_id":row,"cursor":cursor,"duplicate":false}),
    ))
}
