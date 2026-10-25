use super::*;
use serde_json::{Value, json};

pub async fn overview(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ACCESS_MANAGE)?;

    let pg_version = sqlx::query_scalar::<_, String>("SELECT current_setting('server_version')")
        .fetch_one(&state.pool)
        .await
        .unwrap_or_else(|_| "18".to_owned());

    Ok(Json(json!({
        "version": "5.0.2",
        "title": "PasPapan Workforce & HR Platform API",
        "superadmin_only": true,
        "required_capability": access::ACCESS_MANAGE,
        "runtime": {
            "app_origin": state.app_origin,
            "service_key_enabled": state.api_internal_key.is_some(),
            "service_key_header": SERVICE_KEY_HEADER,
            "session_cookie_name": "paspapan_session",
            "session_hmac_enabled": paspapan_adapters::env_var("SESSION_SECRET").map(|v| !v.trim().is_empty()).unwrap_or(false),
            "storage_driver": state.storage.driver_name(),
            "postgres_version": pg_version
        }
    })))
}
