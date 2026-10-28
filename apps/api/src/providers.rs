use super::*;
use base64::{Engine, engine::general_purpose::URL_SAFE_NO_PAD};
use hmac::{Hmac, Mac};
use serde::Deserialize;
use serde_json::{Value, json};
use sha2::{Digest, Sha256};

fn config(purpose: &str) -> Result<(String, String), ApiError> {
    let prefix = match purpose {
        "biometric_enrollment" => "BIOMETRIC_VERIFIER",
        "document_signature" => "DOCUMENT_SIGNER",
        _ => return Err(ApiError::BadRequest("invalid verification purpose")),
    };
    let url = paspapan_adapters::env_var(&format!("{prefix}_URL")).unwrap_or_default();
    let key = paspapan_adapters::env_var(&format!("{prefix}_SECRET")).unwrap_or_default();
    let uri = url
        .parse::<axum::http::Uri>()
        .map_err(|_| ApiError::Conflict("verification provider is not configured"))?;
    if uri.scheme_str() != Some("https")
        || uri.host().is_none()
        || uri.query().is_some()
        || uri
            .authority()
            .is_some_and(|authority| authority.as_str().contains('@'))
        || key.len() < 32
    {
        return Err(ApiError::Conflict(
            "verification provider is not configured",
        ));
    }
    Ok((url, key))
}

async fn runtime_config(
    connection: &mut sqlx::PgConnection,
    company: Uuid,
    purpose: &str,
) -> Result<integrations::Config, ApiError> {
    if let Some(config) = integrations::config(connection, company, purpose).await? {
        return Ok(config);
    }
    let (endpoint, secret) = config(purpose)?;
    Ok(integrations::Config {
        endpoint,
        secret,
        id: None,
        version: None,
    })
}

pub async fn status(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let mut connection = state.pool.acquire().await.map_err(database_error)?;
    let biometric = runtime_config(&mut connection, context.company_id, "biometric_enrollment")
        .await
        .is_ok();
    let signing = runtime_config(&mut connection, context.company_id, "document_signature")
        .await
        .is_ok();
    Ok(Json(
        json!({"biometric_enrollment":biometric,"document_signature":signing}),
    ))
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct StartVerification {
    purpose: String,
    resource_id: Uuid,
}

fn signed(value: &Value, key: &str) -> String {
    let payload = URL_SAFE_NO_PAD.encode(serde_json::to_vec(value).expect("JSON value"));
    let mut mac = Hmac::<Sha256>::new_from_slice(key.as_bytes()).expect("HMAC key");
    mac.update(payload.as_bytes());
    format!(
        "{payload}.{}",
        URL_SAFE_NO_PAD.encode(mac.finalize().into_bytes())
    )
}

fn verified(receipt: &str, key: &str) -> Result<Value, ApiError> {
    if receipt.len() > 8192 {
        return Err(ApiError::BadRequest("invalid provider receipt"));
    }
    let (payload, signature) = receipt.split_once('.').ok_or(ApiError::Forbidden)?;
    let signature = URL_SAFE_NO_PAD
        .decode(signature)
        .map_err(|_| ApiError::Forbidden)?;
    let mut mac = Hmac::<Sha256>::new_from_slice(key.as_bytes()).map_err(|_| ApiError::Internal)?;
    mac.update(payload.as_bytes());
    mac.verify_slice(&signature)
        .map_err(|_| ApiError::Forbidden)?;
    serde_json::from_slice(
        &URL_SAFE_NO_PAD
            .decode(payload)
            .map_err(|_| ApiError::Forbidden)?,
    )
    .map_err(|_| ApiError::Forbidden)
}

pub async fn start(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<StartVerification>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let configuration = runtime_config(
        &mut *state.pool.acquire().await.map_err(database_error)?,
        context.company_id,
        &body.purpose,
    )
    .await?;
    let (url, key) = (configuration.endpoint, configuration.secret);
    tenant_sessions::limit_auth(
        &state.pool,
        "provider_challenge",
        &format!("{}:{}", context.company_id, context.user_id),
        10,
    )
    .await?;
    if body.purpose == "document_signature" {
        require_capability(&context, access::DOCUMENTS_MANAGE)?;
    }
    let content_type = if body.purpose == "document_signature" {
        "application/pdf"
    } else {
        "image/%"
    };
    let object_key: String = sqlx::query_scalar("SELECT object_key FROM private_files WHERE company_id=$1 AND uploaded_by=$2 AND id=$3 AND content_type LIKE $4")
        .bind(context.company_id).bind(context.user_id).bind(body.resource_id).bind(content_type).fetch_optional(&state.pool).await.map_err(database_error)?.ok_or(ApiError::BadRequest("verification resource not found"))?;
    let bytes = state
        .storage
        .get_object(&object_key)
        .await
        .map_err(|_| ApiError::Internal)?;
    let digest = format!("{:x}", Sha256::digest(&bytes));
    let id = Uuid::now_v7();
    let expires: i64 = sqlx::query_scalar("INSERT INTO provider_verifications(id,company_id,user_id,purpose,resource_id,resource_sha256,provider_id,provider_version) VALUES($1,$2,$3,$4,$5,$6,$7,$8) RETURNING extract(epoch FROM expires_at)::bigint")
        .bind(id).bind(context.company_id).bind(context.user_id).bind(&body.purpose).bind(body.resource_id).bind(&digest).bind(configuration.id).bind(configuration.version).fetch_one(&state.pool).await.map_err(database_error)?;
    let request = signed(
        &json!({"type":"request","challenge_id":id,"company_id":context.company_id,"user_id":context.user_id,"purpose":body.purpose,"resource_id":body.resource_id,"resource_sha256":digest,"expires_at":expires}),
        &key,
    );
    Ok(Json(
        json!({"id":id,"expires_at":expires,"verification_url":format!("{url}?request={request}")}),
    ))
}

pub async fn consume(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    purpose: &str,
    resource: Uuid,
    receipt: Option<&str>,
) -> Result<String, ApiError> {
    let configuration = runtime_config(tx, context.company_id, purpose).await?;
    let key = configuration.secret;
    let claims = verified(
        receipt.ok_or(ApiError::BadRequest(
            "server verification receipt is required",
        ))?,
        &key,
    )?;
    let id = claims["challenge_id"]
        .as_str()
        .and_then(|v| Uuid::parse_str(v).ok())
        .ok_or(ApiError::Forbidden)?;
    if claims["type"] != "receipt"
        || claims["verified"] != true
        || claims["purpose"] != purpose
        || claims["resource_id"] != resource.to_string()
        || claims["company_id"] != context.company_id.to_string()
        || claims["user_id"] != context.user_id.to_string()
    {
        return Err(ApiError::Forbidden);
    }
    let reference = claims["reference"]
        .as_str()
        .filter(|v| !v.trim().is_empty() && v.len() <= 200)
        .ok_or(ApiError::Forbidden)?;
    let digest = claims["resource_sha256"]
        .as_str()
        .ok_or(ApiError::Forbidden)?;
    let count = sqlx::query("UPDATE provider_verifications SET consumed_at=now() WHERE id=$1 AND company_id=$2 AND user_id=$3 AND purpose=$4 AND resource_id=$5 AND resource_sha256=$6 AND provider_id IS NOT DISTINCT FROM $7 AND provider_version IS NOT DISTINCT FROM $8 AND consumed_at IS NULL AND expires_at>clock_timestamp()")
        .bind(id).bind(context.company_id).bind(context.user_id).bind(purpose).bind(resource).bind(digest).bind(configuration.id).bind(configuration.version).execute(&mut **tx).await.map_err(database_error)?.rows_affected();
    if count != 1 {
        return Err(ApiError::Conflict("verification expired or already used"));
    }
    Ok(reference.to_owned())
}

#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn provider_receipts_require_authentic_signature() {
        let receipt = signed(
            &json!({"type":"receipt","verified":true}),
            "a secret only the verifier knows",
        );
        assert!(verified(&receipt, "a secret only the verifier knows").is_ok());
        assert!(verified(&receipt, "another secret").is_err());
        assert!(verified("forged.payload", "another secret").is_err());
        assert!(verified(&"x".repeat(8193), "another secret").is_err());
    }
}
