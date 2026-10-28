use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

const REQUIRED_CHALLENGES: [&str; 5] = ["position", "blink", "turn_left", "turn_right", "steady"];

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct EnrollBiometricRequest {
    photo_file_id: Uuid,
    descriptor: Vec<f32>,
    model_version: String,
    challenges_passed: Vec<String>,
    consented: bool,
    verification_receipt: Option<String>,
}

fn validate_enrollment(body: &EnrollBiometricRequest) -> Result<(), ApiError> {
    if !body.consented {
        return Err(ApiError::BadRequest("biometric consent is required"));
    }
    if body.descriptor.len() != 128
        || body
            .descriptor
            .iter()
            .any(|value| !value.is_finite() || !(-2.0..=2.0).contains(value))
    {
        return Err(ApiError::BadRequest("invalid biometric descriptor"));
    }
    if body.model_version.trim().is_empty() || body.model_version.len() > 80 {
        return Err(ApiError::BadRequest("invalid biometric model version"));
    }
    if !REQUIRED_CHALLENGES.iter().all(|required| {
        body.challenges_passed
            .iter()
            .any(|actual| actual == required)
    }) {
        return Err(ApiError::BadRequest("liveness challenge is incomplete"));
    }
    Ok(())
}

pub async fn profile(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let profile = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'enrolled', true,
            'server_verified', b.verified_at IS NOT NULL,
            'photo_file_id', b.photo_file_id,
            'photo_url', '/v1/files/' || b.photo_file_id::text || '/download',
            'file_name', f.file_name,
            'model_version', b.model_version,
            'enrolled_at', to_char(b.enrolled_at AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"')
         )
         FROM biometric_profiles b
         JOIN private_files f ON f.id = b.photo_file_id AND f.company_id = b.company_id
         WHERE b.company_id = $1 AND b.user_id = $2",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_optional(&state.pool)
    .await
    .map_err(database_error)?;

    Ok(Json(
        profile.unwrap_or_else(|| json!({ "enrolled": false })),
    ))
}

pub async fn enroll(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<EnrollBiometricRequest>,
) -> Result<(StatusCode, Json<Value>), ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    validate_enrollment(&body)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let reference = providers::consume(
        &mut tx,
        &context,
        "biometric_enrollment",
        body.photo_file_id,
        body.verification_receipt.as_deref(),
    )
    .await?;

    let file_name = sqlx::query_scalar::<_, String>(
        "SELECT file_name FROM private_files
         WHERE id = $1 AND company_id = $2 AND uploaded_by = $3 AND content_type LIKE 'image/%'",
    )
    .bind(body.photo_file_id)
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("biometric photo file not found"))?;

    sqlx::query(
        "INSERT INTO biometric_profiles (
            company_id, user_id, photo_file_id, descriptor, model_version,
            capture_evidence, consented_at, verified_at
         ) VALUES ($1, $2, $3, $4, $5, $6, now(), now())
         ON CONFLICT (company_id, user_id) DO UPDATE SET
            photo_file_id = EXCLUDED.photo_file_id,
            descriptor = EXCLUDED.descriptor,
            model_version = EXCLUDED.model_version,
            capture_evidence = EXCLUDED.capture_evidence,
            consented_at = EXCLUDED.consented_at,
            verified_at = now(),
            enrolled_at = now(),
            updated_at = now()",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(body.photo_file_id)
    .bind(&body.descriptor)
    .bind(body.model_version.trim())
    .bind(json!({ "challenges_passed": body.challenges_passed, "provider_reference": reference }))
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;

    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata)
         VALUES ($1, $2, $3, 'biometric.enrolled', 'biometric_profile', $3, $4)",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(json!({ "model_version": body.model_version, "photo_file_id": body.photo_file_id }))
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;

    Ok((
        StatusCode::CREATED,
        Json(json!({
            "enrolled": true,
            "photo_file_id": body.photo_file_id,
            "photo_url": format!("/v1/files/{}/download", body.photo_file_id),
            "file_name": file_name,
            "model_version": body.model_version
        })),
    ))
}

pub async fn delete_profile(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let photo_file_id = sqlx::query_scalar::<_, Uuid>(
        "DELETE FROM biometric_profiles
         WHERE company_id = $1 AND user_id = $2
         RETURNING photo_file_id",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("biometric profile not found"))?;

    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata)
         VALUES ($1, $2, $3, 'biometric.deleted', 'biometric_profile', $3, $4)",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(json!({ "photo_file_id": photo_file_id }))
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

#[cfg(test)]
mod tests {
    use super::*;

    fn valid_request() -> EnrollBiometricRequest {
        EnrollBiometricRequest {
            photo_file_id: Uuid::nil(),
            descriptor: vec![0.01; 128],
            model_version: "face-api-128d-v1".to_owned(),
            challenges_passed: REQUIRED_CHALLENGES
                .iter()
                .map(|value| (*value).to_owned())
                .collect(),
            consented: true,
            verification_receipt: None,
        }
    }

    #[test]
    fn rejects_incomplete_or_invalid_biometric_evidence() {
        assert!(validate_enrollment(&valid_request()).is_ok());
        let mut missing_challenge = valid_request();
        missing_challenge.challenges_passed.pop();
        assert!(validate_enrollment(&missing_challenge).is_err());
        let mut invalid_descriptor = valid_request();
        invalid_descriptor.descriptor[0] = f32::NAN;
        assert!(validate_enrollment(&invalid_descriptor).is_err());
        let mut no_consent = valid_request();
        no_consent.consented = false;
        assert!(validate_enrollment(&no_consent).is_err());
    }
}
