use super::*;
use axum::extract::Path;
use base64::{Engine as _, engine::general_purpose::STANDARD as BASE64};
use paspapan_adapters::storage::{ObjectStorage, StorageDriver};
use serde::Deserialize;
use serde_json::{Value, json};

const MAX_UPLOAD_BYTES: usize = 10 * 1024 * 1024;

fn can_manage_files(context: &AuthContext) -> bool {
    context
        .capabilities
        .iter()
        .any(|capability| capability == access::FILES_MANAGE)
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct UploadFileRequest {
    pub file_name: String,
    pub content_type: String,
    pub data_base64: String,
}

pub async fn storage_config(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let _context = authenticated_context(&state.pool, &headers).await?;
    let cfg = state.storage.config();
    Ok(Json(json!({
        "driver": state.storage.driver_name(),
        "bucket": cfg.bucket,
        "region": cfg.region,
        "endpoint": cfg.endpoint,
        "force_path_style": cfg.force_path_style
    })))
}

pub async fn list_files(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let can_manage = can_manage_files(&context);
    let rows = sqlx::query_as::<_, (Uuid, String, String, String, i64, String, String)>(
        "SELECT f.id, f.object_key, f.file_name, f.content_type, f.byte_size, u.full_name,
                to_char(f.created_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI')
         FROM private_files f
         JOIN users u ON u.id = f.uploaded_by
         WHERE f.company_id = $1 AND ($2 OR f.uploaded_by = $3)
         ORDER BY f.created_at DESC
         LIMIT 100",
    )
    .bind(context.company_id)
    .bind(can_manage)
    .bind(context.user_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;

    let driver = state.storage.driver_name();
    let files = rows
        .into_iter()
        .map(
            |(id, object_key, file_name, content_type, byte_size, uploaded_by, created_at)| {
                let id_str = id.to_string();
                let download_url = state
                    .storage
                    .download_url_or_presigned(&id_str, &object_key, 900)
                    .unwrap_or_else(|_| format!("/v1/files/{id_str}/download"));
                json!({
                    "id": id_str,
                    "object_key": object_key,
                    "file_name": file_name,
                    "content_type": content_type,
                    "byte_size": byte_size,
                    "uploaded_by": uploaded_by,
                    "driver": driver,
                    "download_url": download_url,
                    "created_at": created_at
                })
            },
        )
        .collect();

    Ok(Json(files))
}

pub async fn upload_file(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<UploadFileRequest>,
) -> Result<(StatusCode, Json<Value>), ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;

    let file_name = request.file_name.trim();
    let content_type = request.content_type.trim();
    if file_name.is_empty() || file_name.len() > 180 {
        return Err(ApiError::BadRequest("invalid file name"));
    }
    if content_type.is_empty() || content_type.len() > 120 {
        return Err(ApiError::BadRequest("invalid content type"));
    }

    let payload = request
        .data_base64
        .trim()
        .split_once(',')
        .map(|(_, b64)| b64)
        .unwrap_or(request.data_base64.trim());
    let bytes = BASE64
        .decode(payload)
        .map_err(|_| ApiError::BadRequest("invalid base64 file content"))?;
    if bytes.is_empty() || bytes.len() > MAX_UPLOAD_BYTES {
        return Err(ApiError::BadRequest(
            "file size must be between 1 byte and 10 MB",
        ));
    }

    let file_id = Uuid::now_v7();
    let object_key = ObjectStorage::build_object_key(
        &context.company_id.to_string(),
        &file_id.to_string(),
        file_name,
    )
    .map_err(ApiError::BadRequest)?;

    state
        .storage
        .put_object(&object_key, content_type, &bytes)
        .await
        .map_err(|_| ApiError::Internal)?;

    sqlx::query(
        "INSERT INTO private_files (id, company_id, uploaded_by, object_key, file_name, content_type, byte_size)
         VALUES ($1, $2, $3, $4, $5, $6, $7)",
    )
    .bind(file_id)
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(&object_key)
    .bind(file_name)
    .bind(content_type)
    .bind(bytes.len() as i64)
    .execute(&state.pool)
    .await
    .map_err(database_error)?;

    let id_str = file_id.to_string();
    let download_url = state
        .storage
        .download_url_or_presigned(&id_str, &object_key, 900)
        .unwrap_or_else(|_| format!("/v1/files/{id_str}/download"));

    Ok((
        StatusCode::CREATED,
        Json(json!({
            "id": id_str,
            "object_key": object_key,
            "file_name": file_name,
            "content_type": content_type,
            "byte_size": bytes.len(),
            "driver": state.storage.driver_name(),
            "download_url": download_url
        })),
    ))
}

pub async fn download_file(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<String>,
) -> Result<Response, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let file_id = Uuid::parse_str(&id).map_err(|_| ApiError::BadRequest("invalid file id"))?;

    let can_manage = can_manage_files(&context);
    let (object_key, file_name, content_type): (String, String, String) = sqlx::query_as(
        "SELECT object_key, file_name, content_type
         FROM private_files
         WHERE company_id = $1 AND id = $2 AND ($3 OR uploaded_by = $4 OR EXISTS (
             SELECT 1 FROM workforce_requests r
             WHERE r.company_id = private_files.company_id AND r.attachment_file_id = private_files.id
               AND $5 AND r.user_id <> $4
               AND ($6 OR EXISTS(SELECT 1 FROM employee_profiles ep
                   WHERE ep.company_id = r.company_id AND ep.user_id = r.user_id AND ep.manager_user_id = $4))
         ) OR EXISTS (
             SELECT 1 FROM hr_document_requests d
             WHERE d.company_id=private_files.company_id AND d.file_id=private_files.id
             AND (d.user_id=$4 OR $7)
         ))",
    )
    .bind(context.company_id)
    .bind(file_id)
    .bind(can_manage)
    .bind(context.user_id)
    .bind(context.capabilities.iter().any(|p| p == access::APPROVALS_VIEW))
    .bind(context.capabilities.iter().any(|p| p == access::APPROVALS_COMPANY))
    .bind(context.capabilities.iter().any(|p| p == access::DOCUMENTS_MANAGE))
    .fetch_optional(&state.pool)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("file not found"))?;

    if let Ok(bytes) = state.storage.get_object(&object_key).await {
        let safe_name = file_name.replace('"', "");
        let mut response = (StatusCode::OK, bytes).into_response();
        if let Ok(ct) = HeaderValue::from_str(&content_type) {
            response.headers_mut().insert(header::CONTENT_TYPE, ct);
        }
        if let Ok(cd) = HeaderValue::from_str(&format!("attachment; filename=\"{safe_name}\"")) {
            response
                .headers_mut()
                .insert(header::CONTENT_DISPOSITION, cd);
        }
        return Ok(response);
    }

    if state.storage.config().driver != StorageDriver::Local {
        let presigned = state
            .storage
            .download_url_or_presigned(&id, &object_key, 900)
            .map_err(|_| ApiError::Internal)?;
        let location = HeaderValue::from_str(&presigned).map_err(|_| ApiError::Internal)?;
        let mut response = StatusCode::TEMPORARY_REDIRECT.into_response();
        response.headers_mut().insert(header::LOCATION, location);
        return Ok(response);
    }

    Err(ApiError::BadRequest("file object not found"))
}

pub async fn delete_file(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<String>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let file_id = Uuid::parse_str(&id).map_err(|_| ApiError::BadRequest("invalid file id"))?;

    let can_manage = can_manage_files(&context);
    let protected: bool = sqlx::query_scalar(
        "SELECT EXISTS(SELECT 1 FROM hr_document_requests WHERE company_id=$1 AND file_id=$2)
         OR EXISTS(SELECT 1 FROM biometric_profiles WHERE company_id=$1 AND photo_file_id=$2)",
    )
    .bind(context.company_id)
    .bind(file_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;
    if protected {
        return Err(ApiError::Conflict(
            "file is referenced by an issued document or biometric profile",
        ));
    }
    let object_key: Option<String> = sqlx::query_scalar(
        "DELETE FROM private_files
         WHERE company_id = $1 AND id = $2 AND ($3 OR uploaded_by = $4)
         RETURNING object_key",
    )
    .bind(context.company_id)
    .bind(file_id)
    .bind(can_manage)
    .bind(context.user_id)
    .fetch_optional(&state.pool)
    .await
    .map_err(database_error)?;

    let Some(key) = object_key else {
        return Err(ApiError::BadRequest("file not found"));
    };

    let _ = state.storage.delete_object(&key).await;
    Ok(StatusCode::NO_CONTENT)
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn company_file_access_requires_explicit_capability() {
        let base = AuthContext {
            capabilities: vec![],
            user_id: Uuid::nil(),
            full_name: "Employee".to_owned(),
            email: "employee@example.test".to_owned(),
            company_id: Uuid::nil(),
            company_name: "Company".to_owned(),
            role: "employee".to_owned(),
        };
        assert!(!can_manage_files(&base));
        let privileged = AuthContext {
            capabilities: vec![access::FILES_MANAGE.to_owned()],
            ..base
        };
        assert!(can_manage_files(&privileged));
    }
}
