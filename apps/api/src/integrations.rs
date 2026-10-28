use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

pub fn require_owner(context: &AuthContext) -> Result<(), ApiError> {
    if context.role != "owner" {
        return Err(ApiError::Forbidden);
    }
    require_capability(context, access::ACCESS_MANAGE)
}
pub fn validate_endpoint(endpoint: &str) -> Result<(), ApiError> {
    let uri = endpoint
        .parse::<axum::http::Uri>()
        .map_err(|_| ApiError::BadRequest("provider endpoint must be an HTTPS URL"))?;
    if endpoint.len() > 2000
        || uri.scheme_str() != Some("https")
        || uri.host().is_none()
        || uri.query().is_some()
        || uri.authority().is_some_and(|a| a.as_str().contains('@'))
    {
        return Err(ApiError::BadRequest(
            "provider endpoint must use HTTPS without credentials or query",
        ));
    }
    Ok(())
}
pub struct Config {
    pub endpoint: String,
    pub secret: String,
    pub id: Option<Uuid>,
    pub version: Option<i64>,
}
pub async fn config(
    connection: &mut sqlx::PgConnection,
    company: Uuid,
    purpose: &str,
) -> Result<Option<Config>, ApiError> {
    if !matches!(purpose, "biometric_enrollment" | "document_signature") {
        return Err(ApiError::BadRequest("invalid verification purpose"));
    }
    // Lock only the selected provider. Locking inactive rows too would invert the
    // activation transaction's lock order when switching between providers.
    let row=sqlx::query_as::<_,(Uuid,String,String,i64)>("SELECT id,endpoint,encrypted_secret,version FROM integration_providers WHERE company_id=$1 AND purpose=$2 AND active FOR SHARE").bind(company).bind(purpose).fetch_optional(&mut *connection).await.map_err(database_error)?;
    let Some((id, endpoint, secret, version)) = row else {
        let managed: bool = sqlx::query_scalar(
            "SELECT EXISTS(SELECT 1 FROM integration_providers WHERE company_id=$1 AND purpose=$2)",
        )
        .bind(company)
        .bind(purpose)
        .fetch_one(connection)
        .await
        .map_err(database_error)?;
        return if managed {
            Err(ApiError::Conflict("verification provider is disabled"))
        } else {
            Ok(None)
        };
    };
    validate_endpoint(&endpoint)?;
    Ok(Some(Config {
        secret: integration_vault::open(company, id, purpose, &secret)?,
        endpoint,
        id: Some(id),
        version: Some(version),
    }))
}
pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_owner(&context)?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'purpose',purpose,'driver',driver,'name',name,'endpoint',endpoint,'active',active,'has_secret',encrypted_secret IS NOT NULL,'version',version,'updated_at',updated_at) FROM integration_providers WHERE company_id=$1 ORDER BY purpose,name,id").bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"providers":rows,"vault_ready":integration_vault::ready(),"drivers":[{"id":"signed_bridge_v1","name":"Signed verification bridge"}]}),
    ))
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Save {
    id: Option<Uuid>,
    version: Option<i64>,
    purpose: String,
    driver: String,
    name: String,
    endpoint: String,
    active: bool,
    secret: Option<String>,
    #[serde(default)]
    clear_secret: bool,
}
pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<Save>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_owner(&context)?;
    if !matches!(
        body.purpose.as_str(),
        "biometric_enrollment" | "document_signature"
    ) || body.driver != "signed_bridge_v1"
        || body.name.trim().is_empty()
        || body.name.len() > 100
        || body
            .secret
            .as_ref()
            .is_some_and(|value| !(32..=4096).contains(&value.len()))
        || body.clear_secret && body.secret.is_some()
    {
        return Err(ApiError::BadRequest("invalid integration configuration"));
    }
    validate_endpoint(body.endpoint.trim())?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    let previous=sqlx::query_as::<_,(String,Option<String>,i64)>("SELECT purpose,encrypted_secret,version FROM integration_providers WHERE company_id=$1 AND id=$2 FOR UPDATE").bind(context.company_id).bind(id).fetch_optional(&mut *tx).await.map_err(database_error)?;
    if body.id.is_some()
        && previous
            .as_ref()
            .is_none_or(|old| Some(old.2) != body.version || old.0 != body.purpose)
    {
        return Err(ApiError::Conflict("integration changed; reload"));
    }
    let credential_changed = body.clear_secret || body.secret.is_some();
    let encrypted = if let Some(secret) = body.secret {
        Some(integration_vault::seal(
            context.company_id,
            id,
            &body.purpose,
            &secret,
        )?)
    } else if body.clear_secret {
        None
    } else {
        previous.and_then(|old| old.1)
    };
    if body.active {
        let secret = encrypted.as_deref().ok_or(ApiError::BadRequest(
            "active integration requires a credential",
        ))?;
        integration_vault::open(context.company_id, id, &body.purpose, secret)?;
        sqlx::query("UPDATE integration_providers SET active=false,version=version+1,updated_at=now() WHERE company_id=$1 AND purpose=$2 AND active AND id<>$3").bind(context.company_id).bind(&body.purpose).bind(id).execute(&mut *tx).await.map_err(database_error)?;
    }
    sqlx::query("INSERT INTO integration_providers(id,company_id,purpose,driver,name,endpoint,encrypted_secret,active) VALUES($1,$2,$3,$4,$5,$6,$7,$8) ON CONFLICT(id) DO UPDATE SET name=EXCLUDED.name,endpoint=EXCLUDED.endpoint,driver=EXCLUDED.driver,encrypted_secret=EXCLUDED.encrypted_secret,active=EXCLUDED.active,version=integration_providers.version+1,updated_at=now() WHERE integration_providers.company_id=EXCLUDED.company_id AND integration_providers.purpose=EXCLUDED.purpose")
        .bind(id).bind(context.company_id).bind(&body.purpose).bind(&body.driver).bind(body.name.trim()).bind(body.endpoint.trim()).bind(encrypted).bind(body.active).execute(&mut *tx).await.map_err(database_error)?;
    sqlx::query("UPDATE provider_verifications SET expires_at=least(expires_at,clock_timestamp()) WHERE company_id=$1 AND purpose=$2 AND consumed_at IS NULL").bind(context.company_id).bind(&body.purpose).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(&mut tx,&context,id,"integration.configuration_saved",json!({"purpose":body.purpose,"active":body.active,"credential_changed":credential_changed})).await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
