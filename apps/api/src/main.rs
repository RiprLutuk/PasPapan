use axum::{
    Json, Router,
    extract::{Path, Query, Request, State},
    http::{HeaderName, HeaderValue, Method, StatusCode, header},
    middleware::Next,
    response::{IntoResponse, Response},
    routing::{get, post},
};
use hmac::{Hmac, Mac};
use paspapan_contracts::{
    AssignShiftRequest, CheckInRequest, CheckOutRequest, ConfirmPasswordResetRequest,
    CreateRequestRequest, CreateShiftRequest, CurrentCompany, CurrentSessionResponse, CurrentUser,
    DecideRequestRequest, DeleteShiftAssignmentRequest, EmployeeResponse, HealthResponse,
    LoginRequest, PasswordResetRequest, RegisterRequest, SessionResponse, ShiftAssignmentResponse,
    ShiftResponse, TodayAttendanceResponse, WorkforceRequestResponse,
};
use rand::RngCore;
use sha2::{Digest, Sha256};
use sqlx::PgPool;
use std::time::Duration;
use tokio::net::TcpListener;
use tower_http::cors::CorsLayer;
use tracing::{error, info};
use tracing_subscriber::EnvFilter;
use uuid::Uuid;

mod access;
mod access_admin;
mod account;
mod announcements;
mod api_docs;
mod asset_valuation;
mod assets;
mod attendance_import;
mod attendance_locations;
mod attendance_policy;
mod biometrics;
mod document_templates;
mod files;
mod hr_checklists;
mod hr_documents;
mod integration_vault;
mod integrations;
mod organization;
mod payroll_bank;
mod payroll_rules;
mod people;
mod performance;
mod performance_cycles;
mod providers;
mod request_effects;
mod request_payments;
mod tenant_sessions;
mod traffic;
mod work_calendar;
mod workforce_modules;

const SESSION_TTL_SECONDS: i64 = 60 * 60 * 8;
const SERVICE_KEY_HEADER: &str = "x-paspapan-service-key";

#[derive(Clone)]
struct AppState {
    pool: PgPool,
    app_origin: String,
    api_internal_key: Option<String>,
    dummy_password_hash: String,
    storage: paspapan_adapters::storage::ObjectStorage,
    mailer: paspapan_adapters::mailer::Mailer,
    traffic: traffic::Traffic,
}

enum ApiError {
    BadRequest(&'static str),
    Unauthorized,
    Conflict(&'static str),
    Forbidden,
    UntrustedOrigin,
    UntrustedServiceKey,
    Internal,
    RateLimited,
}

impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        let (status, message) = match self {
            Self::BadRequest(message) => (StatusCode::BAD_REQUEST, message),
            Self::Unauthorized => (StatusCode::UNAUTHORIZED, "invalid email or password"),
            Self::Conflict(message) => (StatusCode::CONFLICT, message),
            Self::Forbidden => (StatusCode::FORBIDDEN, "permission denied"),
            Self::UntrustedOrigin => (StatusCode::FORBIDDEN, "untrusted request origin"),
            Self::UntrustedServiceKey => (StatusCode::FORBIDDEN, "untrusted service key"),
            Self::Internal => (StatusCode::INTERNAL_SERVER_ERROR, "internal server error"),
            Self::RateLimited => (
                StatusCode::TOO_MANY_REQUESTS,
                "too many attempts; retry after 15 minutes",
            ),
        };

        let mut response = (status, Json(serde_json::json!({ "error": message }))).into_response();
        if status == StatusCode::TOO_MANY_REQUESTS {
            response
                .headers_mut()
                .insert(header::RETRY_AFTER, HeaderValue::from_static("900"));
        }
        response
    }
}

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    tracing_subscriber::fmt()
        .with_env_filter(EnvFilter::from_default_env())
        .init();

    let database_url =
        paspapan_adapters::env_var("DATABASE_URL").map_err(|_| "DATABASE_URL must be set")?;
    let app_origin =
        paspapan_adapters::env_var("APP_ORIGIN").map_err(|_| "APP_ORIGIN must be set")?;
    let allowed_origins = app_origin
        .split(',')
        .map(str::trim)
        .filter(|origin| !origin.is_empty())
        .map(str::parse::<HeaderValue>)
        .collect::<Result<Vec<_>, _>>()?;
    let pool = paspapan_adapters::connect(&database_url).await?;
    let storage = paspapan_adapters::storage::ObjectStorage::from_env()?;
    let mailer = paspapan_adapters::mailer::Mailer::from_env();
    let api_internal_key = paspapan_adapters::env_var("API_INTERNAL_KEY")
        .ok()
        .map(|value| value.trim().to_owned())
        .filter(|value| !value.is_empty());

    if paspapan_adapters::env_var("AUTO_MIGRATE")
        .map(|v| matches!(v.as_str(), "1" | "true" | "TRUE"))
        .unwrap_or(false)
    {
        let existing_schema = sqlx::query_scalar::<_, bool>(
            "SELECT to_regclass('public.companies') IS NOT NULL AND to_regclass('public._sqlx_migrations') IS NULL",
        )
        .fetch_one(&pool)
        .await
        .unwrap_or(false);
        if !existing_schema {
            paspapan_adapters::migrate(&pool).await?;
        }
    }

    let state = AppState {
        pool,
        app_origin,
        api_internal_key,
        dummy_password_hash: hash_password("paspapan-invalid-login-baseline")
            .map_err(|_| "could not initialize password verification")?,
        storage,
        mailer,
        traffic: traffic::Traffic::default(),
    };

    let app = Router::new()
        .route("/health", get(health))
        .route("/v1/auth/register", post(register))
        .route("/v1/auth/login", post(login))
        .route("/v1/auth/password-reset", post(request_password_reset))
        .route(
            "/v1/auth/password-reset/confirm",
            post(confirm_password_reset),
        )
        .route("/v1/auth/logout", post(logout))
        .route("/v1/auth/session", get(current_session))
        .route("/v1/assets/{id}/history", get(assets::history))
        .route(
            "/v1/admin/hr-checklist-templates",
            get(hr_checklists::templates).post(hr_checklists::save_template),
        )
        .route(
            "/v1/shift-swap-invitations",
            get(request_effects::invitations),
        )
        .route(
            "/v1/requests/{id}/swap-consent",
            post(request_effects::consent),
        )
        .route("/v1/account/companies", get(tenant_sessions::list))
        .route("/v1/account/company", post(tenant_sessions::switch))
        .route("/v1/hr-checklists", get(hr_checklists::list))
        .route(
            "/v1/hr-checklists/{id}/items/{item_id}",
            post(hr_checklists::toggle),
        )
        .route(
            "/v1/admin/hr-checklists",
            get(hr_checklists::admin_list).post(hr_checklists::create),
        )
        .route(
            "/v1/admin/hr-checklists/{id}/complete",
            post(hr_checklists::complete),
        )
        .route(
            "/v1/hr-documents",
            get(hr_documents::list).post(hr_documents::create),
        )
        .route("/v1/admin/hr-documents", get(hr_documents::admin_list))
        .route(
            "/v1/admin/hr-documents/{id}/decision",
            post(hr_documents::decide),
        )
        .route("/v1/performance-goals", get(performance::list))
        .route(
            "/v1/performance-goals/{id}/submit",
            post(performance::submit),
        )
        .route(
            "/v1/admin/performance-goals",
            get(performance::admin_list).post(performance::create),
        )
        .route(
            "/v1/admin/performance-goals/{id}/review",
            post(performance::review),
        )
        .route("/v1/organization/reference-data", get(organization::list))
        .route("/v1/leave-balances", get(organization::leave_balances))
        .route(
            "/v1/admin/organization/leave-entitlements",
            get(organization::entitlements).post(organization::save_entitlement),
        )
        .route(
            "/v1/admin/organization/reference-data",
            get(organization::admin_list).post(organization::save),
        )
        .route("/v1/assets", get(assets::list))
        .route("/v1/assets/{id}/acknowledge", post(assets::acknowledge))
        .route(
            "/v1/admin/assets",
            get(assets::admin_list).post(assets::save),
        )
        .route("/v1/admin/assets/{id}/assign", post(assets::assign))
        .route("/v1/admin/assets/{id}/return", post(assets::return_asset))
        .route("/v1/announcements", get(announcements::list))
        .route(
            "/v1/announcements/{id}/read",
            post(announcements::mark_read),
        )
        .route(
            "/v1/admin/announcements",
            get(announcements::admin_list).post(announcements::save),
        )
        .route(
            "/v1/account/profile",
            get(account::profile).post(account::save_profile),
        )
        .route("/v1/account/password", post(account::change_password))
        .route("/v1/account/sessions", get(account::sessions))
        .route(
            "/v1/account/sessions/{id}/revoke",
            post(account::revoke_session),
        )
        .route(
            "/v1/admin/employees/{id}",
            get(people::detail).post(people::update),
        )
        .route("/v1/admin/employees/{id}/status", post(people::status))
        .route("/v1/admin/employee-directory", get(people::directory))
        .route("/v1/attendance/today", get(today_attendance))
        .route("/v1/attendance/policy", get(attendance_policy::policy))
        .route(
            "/v1/admin/attendances/import",
            post(attendance_import::import),
        )
        .route("/v1/attendance/check-in", post(check_in))
        .route("/v1/attendance/check-out", post(check_out))
        .route("/v1/biometrics/profile", get(biometrics::profile))
        .route(
            "/v1/admin/integrations",
            get(integrations::list).post(integrations::save),
        )
        .route("/v1/providers/status", get(providers::status))
        .route("/v1/providers/challenges", post(providers::start))
        .route("/v1/biometrics/enroll", post(biometrics::enroll))
        .route("/v1/biometrics/delete", post(biometrics::delete_profile))
        .route(
            "/v1/admin/attendance-locations",
            get(attendance_locations::list).post(attendance_locations::create),
        )
        .route(
            "/v1/admin/attendance-locations/refresh",
            post(attendance_locations::refresh),
        )
        .route(
            "/v1/admin/attendance-locations/{id}/status",
            post(attendance_locations::set_status),
        )
        .route(
            "/v1/admin/employees",
            get(list_employees).post(create_employee),
        )
        .route("/v1/colleagues", get(list_colleagues))
        .route("/v1/admin/operational-members", get(operational_members))
        .route("/v1/admin/shifts", get(list_shifts).post(create_shift))
        .route("/v1/admin/scheduling-options", get(scheduling_options))
        .route(
            "/v1/admin/shift-assignments",
            get(list_shift_assignments).post(assign_shift),
        )
        .route(
            "/v1/admin/shift-assignments/delete",
            post(delete_shift_assignment),
        )
        .route("/v1/admin/access", get(access_admin::overview))
        .route("/v1/admin/access/roles", post(access_admin::save_role))
        .route(
            "/v1/admin/access/roles/{id}/delete",
            post(access_admin::delete_role),
        )
        .route(
            "/v1/admin/access/members/{id}",
            post(access_admin::save_member),
        )
        .route("/v1/requests", get(list_requests).post(create_request))
        .route("/v1/approvals", get(list_approvals))
        .route("/v1/admin/payroll-bank", get(payroll_bank::list))
        .route(
            "/v1/admin/payroll-bank/accounts",
            post(payroll_bank::save_account),
        )
        .route(
            "/v1/admin/payroll-bank/settings",
            post(payroll_bank::save_settings),
        )
        .route(
            "/v1/admin/payrolls/{id}/bank-export",
            post(payroll_bank::export),
        )
        .route("/v1/hr-document-templates", get(document_templates::list))
        .route(
            "/v1/admin/hr-document-templates",
            post(document_templates::save),
        )
        .route(
            "/v1/admin/assets/{id}/valuation",
            get(asset_valuation::load).post(asset_valuation::save),
        )
        .route("/v1/performance-cycles", get(performance_cycles::list))
        .route(
            "/v1/admin/performance-cycles",
            post(performance_cycles::save),
        )
        .route(
            "/v1/admin/work-calendar",
            get(work_calendar::load).post(work_calendar::save),
        )
        .route(
            "/v1/admin/request-payments",
            get(request_payments::approved),
        )
        .route(
            "/v1/requests/{id}/payments",
            get(request_payments::list).post(request_payments::record),
        )
        .route("/v1/schedule", get(my_schedule))
        .route("/v1/attendance/history", get(attendance_history))
        .route("/v1/notifications", get(my_notifications))
        .route(
            "/v1/notifications/unread-count",
            get(unread_notification_count),
        )
        .route("/v1/notifications/{id}/read", post(read_notification))
        .route("/v1/requests/{id}/manager-decision", post(manager_decision))
        .route("/v1/requests/{id}/admin-decision", post(admin_decision))
        .route("/v1/requests/{id}/cancel", post(cancel_request))
        .route("/v1/admin/overview", get(workforce_modules::overview))
        .route("/v1/admin/attendances", get(workforce_modules::attendances))
        .route(
            "/v1/admin/activity-logs",
            get(workforce_modules::activity_logs),
        )
        .route("/v1/payrolls", get(workforce_modules::list_payrolls))
        .route("/v1/admin/payrolls", post(workforce_modules::save_payroll))
        .route(
            "/v1/admin/payroll-policy",
            get(payroll_rules::load).post(payroll_rules::save),
        )
        .route(
            "/v1/workspace-records",
            get(workforce_modules::list_records).post(workforce_modules::create_record),
        )
        .route(
            "/v1/workspace-records/{id}/status",
            post(workforce_modules::update_record_status),
        )
        .route(
            "/v1/workspace-records/{id}/delete",
            post(workforce_modules::delete_record),
        )
        .route("/v1/storage/config", get(files::storage_config))
        .route("/v1/files", get(files::list_files).post(files::upload_file))
        .route("/v1/files/{id}/download", get(files::download_file))
        .route("/v1/files/{id}/delete", post(files::delete_file))
        .route(
            "/v1/admin/system-health",
            get(workforce_modules::system_health),
        )
        .route("/v1/admin/api-docs", get(api_docs::overview))
        .route("/v1/events/stream", get(workforce_modules::events_stream))
        .route_layer(axum::middleware::from_fn_with_state(
            state.clone(),
            traffic::observe,
        ))
        .route_layer(axum::middleware::from_fn_with_state(
            state.clone(),
            require_service_key,
        ))
        .layer(
            CorsLayer::new()
                .allow_origin(allowed_origins)
                .allow_credentials(true)
                .allow_methods([Method::GET, Method::POST])
                .allow_headers([
                    header::CONTENT_TYPE,
                    HeaderName::from_static(SERVICE_KEY_HEADER),
                ])
                .max_age(Duration::from_secs(600)),
        )
        .layer(axum::middleware::map_response(no_store))
        .with_state(state);
    let listener = TcpListener::bind(
        paspapan_adapters::env_var("API_BIND").unwrap_or_else(|_| "127.0.0.1:3000".to_owned()),
    )
    .await?;

    info!(address = %listener.local_addr().expect("listener has address"), "API started");
    axum::serve(listener, app).await?;

    Ok(())
}

async fn no_store(mut response: Response) -> Response {
    response
        .headers_mut()
        .insert(header::CACHE_CONTROL, HeaderValue::from_static("no-store"));
    response
}

async fn health() -> Json<HealthResponse> {
    Json(HealthResponse { status: "ok" })
}

async fn register(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<RegisterRequest>,
) -> Result<Response, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    validate_registration(&request)?;

    let email = normalise_email(&request.email);
    let password_hash = hash_password(&request.password)?;
    let company_id = Uuid::now_v7();
    let user_id = Uuid::now_v7();
    let mut transaction = state.pool.begin().await.map_err(database_error)?;

    let company = sqlx::query(
        "INSERT INTO companies (id, name, slug) VALUES ($1, $2, $3) ON CONFLICT DO NOTHING",
    )
    .bind(company_id)
    .bind(request.company_name.trim())
    .bind(request.company_slug.trim().to_ascii_lowercase())
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    if company.rows_affected() == 0 {
        return Err(ApiError::Conflict("company slug already exists"));
    }

    let user = sqlx::query(
        "INSERT INTO users (id, email, password_hash, full_name) VALUES ($1, $2, $3, $4) ON CONFLICT DO NOTHING",
    )
    .bind(user_id)
    .bind(&email)
    .bind(password_hash)
    .bind(request.full_name.trim())
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    if user.rows_affected() == 0 {
        return Err(ApiError::Conflict("email already exists"));
    }

    sqlx::query(
        "INSERT INTO company_memberships (company_id, user_id, role) VALUES ($1, $2, 'owner')",
    )
    .bind(company_id)
    .bind(user_id)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;

    sqlx::query(
        "INSERT INTO employee_profiles (company_id, user_id, employee_code) VALUES ($1, $2, $3)",
    )
    .bind(company_id)
    .bind(user_id)
    .bind(format!("OWNER-{}", &user_id.to_string()[..8]))
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;

    let response = create_session(&mut transaction, user_id).await?;
    transaction.commit().await.map_err(database_error)?;
    Ok(response)
}

async fn login(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<LoginRequest>,
) -> Result<Response, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    tenant_sessions::limit_auth(&state.pool, "login", &request.email, 20).await?;
    if request.password.is_empty()
        || request.password.len() > 1024
        || request.email.trim().is_empty()
        || request.email.len() > 254
    {
        return Err(ApiError::Unauthorized);
    }

    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let user = sqlx::query_as::<_, (Uuid, String)>(
        "SELECT id, password_hash FROM users WHERE email = $1 AND status = 'active' AND EXISTS(SELECT 1 FROM company_memberships WHERE user_id=users.id AND employment_status='active') FOR UPDATE",
    )
    .bind(normalise_email(&request.email))
    .fetch_optional(&mut *transaction)
    .await
    .map_err(database_error)?;

    let Some(user) = user else {
        // Perform the same expensive Argon2id work for unknown and known accounts so
        // response timing does not disclose whether an email address is registered.
        let _ = verify_password(&request.password, &state.dummy_password_hash);
        return Err(ApiError::Unauthorized);
    };

    verify_password(&request.password, &user.1)?;
    let response = create_session(&mut transaction, user.0).await?;
    transaction.commit().await.map_err(database_error)?;
    Ok(response)
}

async fn request_password_reset(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<PasswordResetRequest>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    tenant_sessions::limit_auth(&state.pool, "password-reset", &request.email, 5).await?;
    let email = normalise_email(&request.email);
    if email.is_empty() || email.len() > 254 || !email.contains('@') {
        return Err(ApiError::BadRequest("invalid email address"));
    }

    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let target = sqlx::query_as::<_, (Uuid, String, Uuid)>(
        "SELECT u.id, u.full_name, m.company_id \
         FROM users u \
         INNER JOIN company_memberships m ON m.user_id = u.id \
         WHERE u.email = $1 AND u.status = 'active' AND m.employment_status = 'active' \
         ORDER BY m.created_at ASC LIMIT 1",
    )
    .bind(&email)
    .fetch_optional(&mut *transaction)
    .await
    .map_err(database_error)?;

    if let Some((user_id, full_name, company_id)) = target {
        let mut raw_token = [0_u8; 32];
        rand::rng().fill_bytes(&mut raw_token);
        let reset_token = hex_encode(&raw_token);
        let token_hash = hash_session_token(&reset_token);
        let primary_origin = state
            .app_origin
            .split(',')
            .map(str::trim)
            .find(|origin| !origin.is_empty())
            .unwrap_or("http://127.0.0.1:4173");
        let reset_url = format!("{primary_origin}/reset-password?token={reset_token}");

        sqlx::query(
            "INSERT INTO password_reset_tokens (id, company_id, user_id, token_hash, expires_at) \
             VALUES ($1, $2, $3, $4, NOW() + INTERVAL '30 minutes')",
        )
        .bind(Uuid::now_v7())
        .bind(company_id)
        .bind(user_id)
        .bind(&token_hash)
        .execute(&mut *transaction)
        .await
        .map_err(database_error)?;

        let outbox_id = Uuid::now_v7();
        sqlx::query(
            "INSERT INTO outbox_events (id, company_id, event_type, payload) \
             VALUES ($1, $2, 'auth.password_reset_requested', jsonb_build_object('user_id', $3, 'full_name', $4, 'email', $5, 'reset_url', $6))",
        )
        .bind(outbox_id)
        .bind(company_id)
        .bind(user_id)
        .bind(&full_name)
        .bind(&email)
        .bind(&reset_url)
        .execute(&mut *transaction)
        .await
        .map_err(database_error)?;

        sqlx::query(
            "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata) \
             VALUES ($1, $2, $3, 'account.password_reset_requested', 'user', $3, jsonb_build_object('email', $4))",
        )
        .bind(Uuid::now_v7())
        .bind(company_id)
        .bind(user_id)
        .bind(&email)
        .execute(&mut *transaction)
        .await
        .map_err(database_error)?;

        sqlx::query(
            "INSERT INTO notifications (id, company_id, user_id, title, body) \
             SELECT gen_random_uuid(), $1, m.user_id, 'Permintaan pemulihan akses', $2 \
             FROM company_memberships m \
             WHERE m.company_id = $1 AND m.employment_status = 'active' AND m.role IN ('owner', 'admin') AND m.user_id <> $3",
        )
        .bind(company_id)
        .bind(format!("{full_name} ({email}) meminta bantuan pemulihan kata sandi."))
        .bind(user_id)
        .execute(&mut *transaction)
        .await
        .map_err(database_error)?;

        let mail = paspapan_adapters::mailer::build_password_reset_email(
            &full_name, &email, &reset_url, 30,
        );
        if state.mailer.send(&mail).await.is_ok() {
            let _ = sqlx::query(
                "UPDATE outbox_events SET processed_at = NOW(), attempts = 1 WHERE id = $1",
            )
            .bind(outbox_id)
            .execute(&mut *transaction)
            .await;
        }
    }

    transaction.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

async fn confirm_password_reset(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<ConfirmPasswordResetRequest>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let token = request.token.trim();
    if token.len() != 64 || !token.bytes().all(|byte| byte.is_ascii_hexdigit()) {
        return Err(ApiError::BadRequest(
            "Token pemulihan tidak valid atau sudah kedaluwarsa.",
        ));
    }
    if !(12..=1024).contains(&request.new_password.len()) {
        return Err(ApiError::BadRequest("Kata sandi baru minimal 12 karakter."));
    }

    let token_hash = hash_session_token(token);
    let password_hash = hash_password(&request.new_password)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;

    let row = sqlx::query_as::<_, (Uuid, Uuid, Uuid)>(
        "SELECT id, company_id, user_id \
         FROM password_reset_tokens \
         WHERE token_hash = $1 AND used_at IS NULL AND expires_at > NOW() \
         FOR UPDATE",
    )
    .bind(&token_hash)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest(
        "Token pemulihan tidak valid atau sudah kedaluwarsa.",
    ))?;

    let (reset_id, company_id, user_id) = row;

    sqlx::query("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = $1")
        .bind(reset_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;

    sqlx::query("UPDATE users SET password_hash = $2, updated_at = NOW() WHERE id = $1")
        .bind(user_id)
        .bind(&password_hash)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;

    sqlx::query("UPDATE sessions SET revoked_at = NOW() WHERE user_id = $1 AND revoked_at IS NULL")
        .bind(user_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;

    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata) \
         VALUES ($1, $2, $3, 'account.password_reset_completed', 'user', $3, '{}'::jsonb)",
    )
    .bind(Uuid::now_v7())
    .bind(company_id)
    .bind(user_id)
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;

    sqlx::query(
        "INSERT INTO notifications (id, company_id, user_id, title, body) \
         VALUES ($1, $2, $3, 'Kata sandi diperbarui', 'Kata sandi akun kerja Anda baru saja diatur ulang dan sesi di perangkat lain telah dikeluarkan.')",
    )
    .bind(Uuid::now_v7())
    .bind(company_id)
    .bind(user_id)
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;

    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

async fn current_session(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<CurrentSessionResponse>, ApiError> {
    let session = authenticated_context(&state.pool, &headers).await?;
    Ok(Json(CurrentSessionResponse {
        capabilities: session.capabilities,
        user: CurrentUser {
            id: session.user_id.to_string(),
            full_name: session.full_name,
            email: session.email,
        },
        company: CurrentCompany {
            id: session.company_id.to_string(),
            name: session.company_name,
            role: session.role,
        },
    }))
}

async fn today_attendance(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<TodayAttendanceResponse>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    Ok(Json(
        today_attendance_for(&state.pool, context.user_id, context.company_id).await?,
    ))
}

async fn today_attendance_for(
    pool: &PgPool,
    user_id: Uuid,
    company_id: Uuid,
) -> Result<TodayAttendanceResponse, ApiError> {
    let row = sqlx::query_as::<
        _,
        (
            String,
            Option<String>,
            Option<String>,
            Option<String>,
            Option<String>,
            Option<String>,
            Option<String>,
        ),
    >(
        "SELECT (NOW() AT TIME ZONE companies.timezone)::date::text, \
                to_char(attendances.checked_in_at AT TIME ZONE companies.timezone, 'HH24:MI'), \
                to_char(attendances.checked_out_at AT TIME ZONE companies.timezone, 'HH24:MI'), \
                shifts.name, shifts.starts_at::text, shifts.ends_at::text, attendances.work_mode \
         FROM companies \
         LEFT JOIN attendances ON attendances.company_id = companies.id \
             AND attendances.user_id = $1 \
             AND attendances.attendance_date = (NOW() AT TIME ZONE companies.timezone)::date \
         LEFT JOIN shift_assignments ON shift_assignments.company_id = companies.id \
             AND shift_assignments.user_id = $1 \
             AND shift_assignments.work_date = (NOW() AT TIME ZONE companies.timezone)::date \
         LEFT JOIN shifts ON shifts.id = shift_assignments.shift_id \
         WHERE companies.id = $2",
    )
    .bind(user_id)
    .bind(company_id)
    .fetch_one(pool)
    .await
    .map_err(database_error)?;

    Ok(TodayAttendanceResponse {
        work_mode: row.6,
        date: row.0,
        status: if row.2.is_some() {
            "checked_out"
        } else if row.1.is_some() {
            "checked_in"
        } else {
            "not_checked_in"
        }
        .to_owned(),
        check_in_time: row.1,
        check_out_time: row.2,
        shift_name: row.3,
        starts_at: row.4,
        ends_at: row.5,
    })
}

async fn check_in(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<CheckInRequest>,
) -> Result<Json<TodayAttendanceResponse>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    validate_coordinates(request.latitude, request.longitude)?;

    let context = authenticated_context(&state.pool, &headers).await?;
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let location_id = attendance_policy::validate(
        &mut transaction,
        &context,
        request.mode.as_deref(),
        &request.qr_token,
        request.latitude,
        request.longitude,
    )
    .await?;
    sqlx::query(
        "INSERT INTO attendances (id, company_id, user_id, attendance_date, checked_in_at, check_in_latitude, check_in_longitude, check_in_location_id, work_mode) \
         SELECT $1, companies.id, $2, (NOW() AT TIME ZONE companies.timezone)::date, NOW(), $3, $4, $5, $7 \
         FROM companies WHERE companies.id = $6 \
         ON CONFLICT (company_id, user_id, attendance_date) DO UPDATE \
           SET checked_in_at = COALESCE(attendances.checked_in_at, EXCLUDED.checked_in_at), \
               check_in_latitude = COALESCE(attendances.check_in_latitude, EXCLUDED.check_in_latitude), \
               check_in_longitude = COALESCE(attendances.check_in_longitude, EXCLUDED.check_in_longitude), \
               check_in_location_id = COALESCE(attendances.check_in_location_id, EXCLUDED.check_in_location_id), \
               updated_at = NOW()",
    )
    .bind(Uuid::now_v7())
    .bind(context.user_id)
    .bind(request.latitude)
    .bind(request.longitude)
    .bind(location_id)
    .bind(context.company_id)
    .bind(request.mode.as_deref().unwrap_or("onsite"))
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;

    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata) \
         VALUES ($1, $2, $3, 'attendance.checked_in', 'attendance', NULL, jsonb_build_object('latitude', $4, 'longitude', $5, 'location_id', $6))",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(request.latitude)
    .bind(request.longitude)
    .bind(location_id)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;

    Ok(Json(
        today_attendance_for(&state.pool, context.user_id, context.company_id).await?,
    ))
}

async fn check_out(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<CheckOutRequest>,
) -> Result<Json<TodayAttendanceResponse>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    validate_coordinates(request.latitude, request.longitude)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let location_id = attendance_policy::validate(
        &mut transaction,
        &context,
        request.mode.as_deref(),
        &request.qr_token,
        request.latitude,
        request.longitude,
    )
    .await?;
    let checked_out = sqlx::query(
        "UPDATE attendances SET checked_out_at = NOW(), check_out_latitude = $1, check_out_longitude = $2, check_out_location_id = $3, updated_at = NOW() \
         WHERE company_id = $4 AND user_id = $5 \
           AND attendance_date = (SELECT (NOW() AT TIME ZONE timezone)::date FROM companies WHERE id = $4) \
           AND checked_in_at IS NOT NULL AND checked_out_at IS NULL",
    )
    .bind(request.latitude)
    .bind(request.longitude)
    .bind(location_id)
    .bind(context.company_id)
    .bind(context.user_id)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;

    if checked_out.rows_affected() == 0 {
        return Err(ApiError::BadRequest(
            "check-in is required before check-out",
        ));
    }

    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata) \
         VALUES ($1, $2, $3, 'attendance.checked_out', 'attendance', NULL, jsonb_build_object('latitude', $4, 'longitude', $5, 'location_id', $6))",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(request.latitude)
    .bind(request.longitude)
    .bind(location_id)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;

    Ok(Json(
        today_attendance_for(&state.pool, context.user_id, context.company_id).await?,
    ))
}

async fn list_employees(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<EmployeeResponse>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::EMPLOYEES_VIEW)?;
    let rows = sqlx::query_as::<_, (Uuid, String, String, String, Option<String>)>(
        "SELECT users.id, users.full_name, users.email, company_memberships.role, employee_profiles.employee_code \
         FROM company_memberships \
         INNER JOIN users ON users.id = company_memberships.user_id \
         LEFT JOIN employee_profiles ON employee_profiles.company_id = company_memberships.company_id \
             AND employee_profiles.user_id = company_memberships.user_id \
         WHERE company_memberships.company_id = $1 AND users.status = 'active' AND company_memberships.employment_status='active' \
         ORDER BY users.full_name",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(rows.into_iter().map(employee_response).collect()))
}

async fn list_colleagues(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows = sqlx::query_as::<_, (Uuid, String)>(
        "SELECT u.id, u.full_name
         FROM company_memberships membership
         INNER JOIN users u ON u.id = membership.user_id
         WHERE membership.company_id = $1 AND membership.employment_status = 'active' AND u.id <> $2
         ORDER BY u.full_name, u.id
         LIMIT 500",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(serde_json::json!(
        rows.into_iter()
            .map(|(id, full_name)| serde_json::json!({ "id": id, "full_name": full_name }))
            .collect::<Vec<_>>()
    )))
}

async fn operational_members(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    if ![
        access::PAYROLL_MANAGE,
        access::ASSETS_MANAGE,
        access::ORGANIZATION_MANAGE,
        access::PERFORMANCE_MANAGE,
        access::CHECKLISTS_MANAGE,
    ]
    .iter()
    .any(|permission| {
        context
            .capabilities
            .iter()
            .any(|actual| actual == permission)
    }) {
        return Err(ApiError::Forbidden);
    }
    let rows=sqlx::query_as::<_,(Uuid,String)>("SELECT u.id,u.full_name FROM company_memberships m JOIN users u ON u.id=m.user_id WHERE m.company_id=$1 AND m.employment_status='active' AND u.status='active' ORDER BY u.full_name,u.id")
        .bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(serde_json::json!(
        rows.into_iter()
            .map(|(id, name)| serde_json::json!({"id":id,"full_name":name}))
            .collect::<Vec<_>>()
    )))
}

async fn scheduling_options(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::SCHEDULES_MANAGE)?;
    let members = sqlx::query_scalar::<_, serde_json::Value>(
        "SELECT jsonb_build_object('id',m.user_id,'full_name',u.full_name,'employee_code',p.employee_code)
         FROM company_memberships m JOIN users u ON u.id=m.user_id
         JOIN employee_profiles p ON p.company_id=m.company_id AND p.user_id=m.user_id
         WHERE m.company_id=$1 AND m.employment_status='active' AND u.status='active'
         ORDER BY u.full_name,m.user_id",
    ).bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    let shifts = sqlx::query_as::<_, (Uuid, String, String, String, bool)>(
        "SELECT id,name,starts_at::text,ends_at::text,crosses_midnight FROM shifts WHERE company_id=$1 ORDER BY starts_at,name",
    ).bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(
        serde_json::json!({"members":members,"shifts":shifts.into_iter().map(shift_response).collect::<Vec<_>>()}),
    ))
}

async fn list_shifts(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<ShiftResponse>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::SHIFTS_MANAGE)?;
    let rows = sqlx::query_as::<_, (Uuid, String, String, String, bool)>(
        "SELECT id, name, starts_at::text, ends_at::text, crosses_midnight FROM shifts WHERE company_id = $1 ORDER BY starts_at, name",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(rows.into_iter().map(shift_response).collect()))
}

async fn create_shift(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<CreateShiftRequest>,
) -> Result<Json<ShiftResponse>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    if request.name.trim().is_empty() || !is_time(&request.starts_at) || !is_time(&request.ends_at)
    {
        return Err(ApiError::BadRequest("invalid shift details"));
    }
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::SHIFTS_MANAGE)?;
    let id = Uuid::now_v7();
    sqlx::query(
        "INSERT INTO shifts (id, company_id, name, starts_at, ends_at, crosses_midnight) VALUES ($1, $2, $3, $4::time, $5::time, $6)",
    )
    .bind(id)
    .bind(context.company_id)
    .bind(request.name.trim())
    .bind(&request.starts_at)
    .bind(&request.ends_at)
    .bind(request.crosses_midnight)
    .execute(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(ShiftResponse {
        id: id.to_string(),
        name: request.name.trim().to_owned(),
        starts_at: request.starts_at,
        ends_at: request.ends_at,
        crosses_midnight: request.crosses_midnight,
    }))
}

#[derive(serde::Deserialize)]
struct ShiftAssignmentsQuery {
    user_id: String,
    month: String,
}

async fn list_shift_assignments(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Query(query): Query<ShiftAssignmentsQuery>,
) -> Result<Json<Vec<ShiftAssignmentResponse>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::SCHEDULES_MANAGE)?;
    let user_id =
        Uuid::parse_str(&query.user_id).map_err(|_| ApiError::BadRequest("invalid employee"))?;
    if !is_month(&query.month) {
        return Err(ApiError::BadRequest("invalid schedule month"));
    }
    let month_start = format!("{}-01", query.month);
    let rows = sqlx::query_as::<
        _,
        (Uuid, Uuid, Uuid, String, String, String, bool, String),
    >(
        "SELECT a.id, a.user_id, a.shift_id, s.name, s.starts_at::text, s.ends_at::text, s.crosses_midnight, a.work_date::text \
         FROM shift_assignments a \
         INNER JOIN shifts s ON s.id = a.shift_id AND s.company_id = a.company_id \
         WHERE a.company_id = $1 AND a.user_id = $2 \
           AND a.work_date >= $3::date \
           AND a.work_date < ($3::date + INTERVAL '1 month')::date \
         ORDER BY a.work_date ASC",
    )
    .bind(context.company_id)
    .bind(user_id)
    .bind(month_start)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;

    Ok(Json(
        rows.into_iter()
            .map(|r| ShiftAssignmentResponse {
                id: r.0.to_string(),
                user_id: r.1.to_string(),
                shift_id: r.2.to_string(),
                shift_name: r.3,
                starts_at: r.4,
                ends_at: r.5,
                crosses_midnight: r.6,
                work_date: r.7,
            })
            .collect(),
    ))
}

async fn assign_shift(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<AssignShiftRequest>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::SCHEDULES_MANAGE)?;
    let user_id =
        Uuid::parse_str(&request.user_id).map_err(|_| ApiError::BadRequest("invalid employee"))?;
    let shift_id =
        Uuid::parse_str(&request.shift_id).map_err(|_| ApiError::BadRequest("invalid shift"))?;
    if !is_date(&request.work_date) {
        return Err(ApiError::BadRequest("invalid work date"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let assigned = sqlx::query(
        "INSERT INTO shift_assignments (id, company_id, user_id, shift_id, work_date) \
         SELECT $1, $2, $3, shifts.id, $4::date FROM shifts WHERE shifts.id = $5 AND shifts.company_id = $2 \
         AND EXISTS(SELECT 1 FROM company_memberships m JOIN users u ON u.id=m.user_id WHERE m.company_id=$2 AND m.user_id=$3 AND m.employment_status='active' AND u.status='active') \
         ON CONFLICT (company_id, user_id, work_date) DO UPDATE SET shift_id = EXCLUDED.shift_id",
    )
    .bind(Uuid::now_v7()).bind(context.company_id).bind(user_id).bind(&request.work_date).bind(shift_id)
    .execute(&mut *tx).await.map_err(database_error)?;
    if assigned.rows_affected() == 0 {
        return Err(ApiError::BadRequest(
            "shift or active employee not found in this company",
        ));
    }
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

async fn delete_shift_assignment(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<DeleteShiftAssignmentRequest>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::SCHEDULES_MANAGE)?;
    let user_id =
        Uuid::parse_str(&request.user_id).map_err(|_| ApiError::BadRequest("invalid employee"))?;
    if !is_date(&request.work_date) {
        return Err(ApiError::BadRequest("invalid work date"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    sqlx::query(
        "DELETE FROM shift_assignments WHERE company_id = $1 AND user_id = $2 AND work_date = $3::date",
    )
    .bind(context.company_id)
    .bind(user_id)
    .bind(&request.work_date)
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

async fn list_requests(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<WorkforceRequestResponse>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows = sqlx::query_as::<
        _,
        (
            Uuid,
            String,
            String,
            String,
            Option<i64>,
            Option<i64>,
            Option<Uuid>,
            Option<String>,
            Option<Uuid>,
            Option<String>,
            String,
            String,
            Option<String>,
            Option<String>,
            serde_json::Value,
        ),
    >(
        "SELECT r.id, r.kind, r.starts_on::text, r.ends_on::text, r.minutes, r.amount_cents, \
                r.target_user_id, target.full_name, r.attachment_file_id, file.file_name, \
                r.reason, r.status, \
                latest.note, latest.approver_name, r.metadata \
         FROM workforce_requests r \
         LEFT JOIN users target ON target.id = r.target_user_id \
         LEFT JOIN private_files file ON file.id = r.attachment_file_id AND file.company_id = r.company_id \
         LEFT JOIN LATERAL ( \
             SELECT d.note, u.full_name AS approver_name \
             FROM request_decisions d \
             INNER JOIN users u ON u.id = d.approver_id \
             WHERE d.company_id = r.company_id AND d.request_id = r.id \
             ORDER BY d.decided_at DESC LIMIT 1 \
         ) latest ON true \
         WHERE r.company_id = $1 AND r.user_id = $2 \
         ORDER BY r.created_at DESC",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(rows.into_iter().map(request_response).collect()))
}

async fn create_request(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<CreateRequestRequest>,
) -> Result<Json<WorkforceRequestResponse>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    if !matches!(
        request.kind.as_str(),
        "leave"
            | "overtime"
            | "attendance_correction"
            | "reimbursement"
            | "wfh"
            | "cash_advance"
            | "shift_swap"
    ) || !is_date(&request.starts_on)
        || !is_date(&request.ends_on)
        || request.ends_on < request.starts_on
        || request.reason.trim().is_empty()
        || request.reason.len() > 4000
        || request.minutes.is_some_and(|minutes| minutes <= 0)
        || request.amount_cents.is_some_and(|amount| amount <= 0)
        || (request.kind == "overtime" && request.minutes.is_none())
        || (matches!(request.kind.as_str(), "reimbursement" | "cash_advance")
            && request.amount_cents.is_none())
        || (request.kind == "shift_swap" && request.target_user_id.is_none())
    {
        return Err(ApiError::BadRequest("invalid request details"));
    }
    let context = authenticated_context(&state.pool, &headers).await?;
    let target_user_id = request
        .target_user_id
        .as_deref()
        .map(Uuid::parse_str)
        .transpose()
        .map_err(|_| ApiError::BadRequest("invalid target employee"))?;
    let attachment_file_id = request
        .attachment_file_id
        .as_deref()
        .map(Uuid::parse_str)
        .transpose()
        .map_err(|_| ApiError::BadRequest("invalid attachment"))?;
    if request.kind != "shift_swap" && target_user_id.is_some() {
        return Err(ApiError::BadRequest(
            "target employee is only valid for shift swaps",
        ));
    }
    if target_user_id == Some(context.user_id) {
        return Err(ApiError::BadRequest(
            "shift swap target must be another employee",
        ));
    }
    if let Some(target_id) = target_user_id {
        let exists: bool = sqlx::query_scalar(
            "SELECT EXISTS(SELECT 1 FROM company_memberships WHERE company_id = $1 AND user_id = $2 AND employment_status = 'active')",
        )
        .bind(context.company_id)
        .bind(target_id)
        .fetch_one(&state.pool)
        .await
        .map_err(database_error)?;
        if !exists {
            return Err(ApiError::BadRequest("target employee not found"));
        }
    }
    if let Some(file_id) = attachment_file_id {
        let owns_file: bool = sqlx::query_scalar(
            "SELECT EXISTS(SELECT 1 FROM private_files WHERE id = $1 AND company_id = $2 AND uploaded_by = $3)",
        )
        .bind(file_id)
        .bind(context.company_id)
        .bind(context.user_id)
        .fetch_one(&state.pool)
        .await
        .map_err(database_error)?;
        if !owns_file {
            return Err(ApiError::BadRequest("attachment not found"));
        }
    }
    let id = Uuid::now_v7();
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let mut details = request_effects::prepare(&mut transaction, &context, &request).await?;
    let charged_days = if request.kind == "leave" {
        if request.starts_on.get(..4) != request.ends_on.get(..4) {
            return Err(ApiError::BadRequest(
                "split leave requests across calendar years",
            ));
        }
        people::lock_company(&mut transaction, context.company_id).await?;
        let days = sqlx::query_scalar::<_, i64>("SELECT leave_charge_days($1,$2::date,$3::date)")
            .bind(context.company_id)
            .bind(&request.starts_on)
            .bind(&request.ends_on)
            .fetch_one(&mut *transaction)
            .await
            .map_err(database_error)?;
        if days == 0 {
            return Err(ApiError::BadRequest("leave does not include a working day"));
        }
        details["charged_days"] = serde_json::json!(days);
        Some(days)
    } else {
        None
    };
    let leave_type_id = request
        .leave_type_id
        .as_deref()
        .map(Uuid::parse_str)
        .transpose()
        .map_err(|_| ApiError::BadRequest("invalid leave type"))?;
    if request.kind == "leave" && leave_type_id.is_none() {
        let configured:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM organization_reference_data WHERE company_id=$1 AND kind='leave_type' AND active)")
            .bind(context.company_id).fetch_one(&mut *transaction).await.map_err(database_error)?;
        if configured {
            return Err(ApiError::BadRequest("choose a leave type"));
        }
    }
    if leave_type_id.is_some() && request.kind != "leave" {
        return Err(ApiError::BadRequest(
            "leave type is only valid for leave requests",
        ));
    }
    if let Some(leave_type_id) = leave_type_id {
        people::lock_company(&mut transaction, context.company_id).await?;
        let allowance = sqlx::query_scalar::<_,i32>("SELECT COALESCE(e.days+e.carryover_days,r.annual_days) FROM organization_reference_data r LEFT JOIN employee_leave_entitlements e ON e.company_id=r.company_id AND e.leave_type_id=r.id AND e.user_id=$2 AND e.year=extract(year FROM $4::date)::int WHERE r.company_id=$1 AND r.id=$3 AND r.kind='leave_type' AND r.active")
            .bind(context.company_id).bind(context.user_id).bind(leave_type_id).bind(&request.starts_on).fetch_optional(&mut *transaction).await.map_err(database_error)?.ok_or(ApiError::BadRequest("active leave type not found"))?;
        if request.starts_on.get(..4) != request.ends_on.get(..4) {
            return Err(ApiError::BadRequest(
                "split leave requests across calendar years",
            ));
        }
        let available:bool=sqlx::query_scalar("SELECT ($5::bigint + COALESCE(sum(charged_leave_days),0)) <= $6::bigint FROM workforce_requests WHERE company_id=$1 AND user_id=$2 AND leave_type_id=$3 AND extract(year FROM starts_on)=extract(year FROM $4::date) AND status IN ('pending_manager','pending_admin','approved')")
            .bind(context.company_id).bind(context.user_id).bind(leave_type_id).bind(&request.starts_on)
            .bind(charged_days.unwrap_or(0))
            .bind(allowance as i64).fetch_one(&mut *transaction).await.map_err(database_error)?;
        if !available {
            return Err(ApiError::BadRequest("insufficient leave allowance"));
        }
    }
    sqlx::query("INSERT INTO workforce_requests (id, company_id, user_id, kind, starts_on, ends_on, minutes, amount_cents, target_user_id, attachment_file_id, reason,leave_type_id) VALUES ($1,$2,$3,$4,$5::date,$6::date,$7,$8,$9,$10,$11,$12)")
        .bind(id).bind(context.company_id).bind(context.user_id).bind(&request.kind).bind(&request.starts_on).bind(&request.ends_on).bind(request.minutes).bind(request.amount_cents).bind(target_user_id).bind(attachment_file_id).bind(request.reason.trim()).bind(leave_type_id).execute(&mut *transaction).await.map_err(database_error)?;
    record_request_audit(&mut transaction, &context, id, "request.created").await?;
    sqlx::query("UPDATE workforce_requests SET metadata=$3,charged_leave_days=$4 WHERE company_id=$1 AND id=$2")
        .bind(context.company_id)
        .bind(id)
        .bind(&details)
        .bind(charged_days.map(|v|v as i32))
        .execute(&mut *transaction)
        .await
        .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;
    Ok(Json(WorkforceRequestResponse {
        id: id.to_string(),
        kind: request.kind,
        starts_on: request.starts_on,
        ends_on: request.ends_on,
        minutes: request.minutes,
        amount_cents: request.amount_cents,
        target_user_id: target_user_id.map(|id| id.to_string()),
        target_user_name: None,
        attachment_file_id: attachment_file_id.map(|id| id.to_string()),
        attachment_file_name: None,
        attachment_download_url: attachment_file_id.map(|id| format!("/v1/files/{id}/download")),
        reason: request.reason.trim().to_owned(),
        status: "pending_manager".to_owned(),
        decision_note: None,
        decided_by: None,
        details,
    }))
}

async fn manager_decision(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<String>,
    Json(body): Json<DecideRequestRequest>,
) -> Result<StatusCode, ApiError> {
    decide_request(&state, &headers, &id, &body, "manager").await
}

async fn cancel_request(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let changed=sqlx::query("UPDATE workforce_requests SET status='cancelled',updated_at=now() WHERE company_id=$1 AND id=$2 AND user_id=$3 AND status IN ('pending_manager','pending_admin')")
        .bind(context.company_id).bind(id).bind(context.user_id).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::Conflict(
            "only your pending requests can be cancelled",
        ));
    }
    record_request_audit(&mut tx, &context, id, "request.cancelled").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

async fn admin_decision(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<String>,
    Json(body): Json<DecideRequestRequest>,
) -> Result<StatusCode, ApiError> {
    decide_request(&state, &headers, &id, &body, "admin").await
}

async fn decide_request(
    state: &AppState,
    headers: &axum::http::HeaderMap,
    id: &str,
    body: &DecideRequestRequest,
    stage: &str,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(headers, &state.app_origin)?;
    if !matches!(body.decision.as_str(), "approved" | "rejected")
        || body.note.as_ref().is_some_and(|note| note.len() > 4000)
    {
        return Err(ApiError::BadRequest("invalid decision"));
    }
    let context = authenticated_context(&state.pool, headers).await?;
    if stage == "manager" {
        require_capability(&context, access::APPROVALS_MANAGER)?;
    }
    if stage == "admin" {
        require_capability(&context, access::APPROVALS_ADMIN)?;
    }
    let request_id = Uuid::parse_str(id).map_err(|_| ApiError::BadRequest("invalid request"))?;
    let expected = if stage == "manager" {
        "pending_manager"
    } else {
        "pending_admin"
    };
    let next = if body.decision == "rejected" {
        "rejected"
    } else if stage == "manager" {
        "pending_admin"
    } else {
        "approved"
    };
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut transaction, context.company_id).await?;
    let unconsented:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM workforce_requests WHERE company_id=$1 AND id=$2 AND kind='shift_swap' AND COALESCE((metadata->>'colleague_consented')::bool,false)=false)")
        .bind(context.company_id).bind(request_id).fetch_one(&mut *transaction).await.map_err(database_error)?;
    if body.decision == "approved" && unconsented {
        return Err(ApiError::Conflict(
            "colleague must consent before approving the swap",
        ));
    }
    let changed = sqlx::query("UPDATE workforce_requests AS r SET status = $1, updated_at = NOW()
        WHERE r.id = $2 AND r.company_id = $3 AND r.status = $4 AND r.user_id <> $5
        AND ($6 OR EXISTS (SELECT 1 FROM employee_profiles p
            WHERE p.company_id = r.company_id AND p.user_id = r.user_id AND p.manager_user_id = $5))")
        .bind(next).bind(request_id).bind(context.company_id).bind(expected)
        .bind(context.user_id).bind(context.capabilities.iter().any(|p| p == access::APPROVALS_COMPANY))
        .execute(&mut *transaction).await.map_err(database_error)?;
    if changed.rows_affected() == 0 {
        return Err(ApiError::BadRequest(
            "request is not awaiting this decision",
        ));
    }
    if next == "approved" {
        request_effects::apply(&mut transaction, &context, request_id).await?;
    }
    sqlx::query("INSERT INTO request_decisions (id, request_id, company_id, approver_id, stage, decision, note) VALUES ($1,$2,$3,$4,$5,$6,$7)")
        .bind(Uuid::now_v7()).bind(request_id).bind(context.company_id).bind(context.user_id).bind(stage).bind(&body.decision).bind(&body.note).execute(&mut *transaction).await.map_err(database_error)?;
    record_request_audit(
        &mut transaction,
        &context,
        request_id,
        &format!("request.{stage}.{}", body.decision),
    )
    .await?;
    sqlx::query("INSERT INTO notifications (id, company_id, user_id, title, body)
        SELECT $1, company_id, user_id, 'Status pengajuan diperbarui', $2 FROM workforce_requests WHERE id = $3 AND company_id = $4")
        .bind(Uuid::now_v7()).bind(next).bind(request_id).bind(context.company_id)
        .execute(&mut *transaction).await.map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

async fn record_request_audit(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
    event: &str,
) -> Result<(), ApiError> {
    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id)
        VALUES ($1, $2, $3, $4, 'workforce_request', $5)",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(event)
    .bind(id)
    .execute(&mut **transaction)
    .await
    .map_err(database_error)?;
    Ok(())
}

async fn list_approvals(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::APPROVALS_VIEW)?;
    let rows = sqlx::query_as::<
        _,
        (
            Uuid,
            String,
            String,
            String,
            Option<i64>,
            Option<i64>,
            Option<Uuid>,
            Option<String>,
            Option<Uuid>,
            Option<String>,
            String,
            String,
            String,
            Option<String>,
            Option<String>,
            serde_json::Value,
        ),
    >(
        "SELECT r.id, r.kind, r.starts_on::text, r.ends_on::text, r.minutes, r.amount_cents, \
                r.target_user_id, target.full_name, r.attachment_file_id, file.file_name, \
                r.reason, r.status, u.full_name, \
                latest.note, latest.approver_name, r.metadata \
        FROM workforce_requests r \
        JOIN users u ON u.id = r.user_id \
        LEFT JOIN users target ON target.id = r.target_user_id \
        LEFT JOIN private_files file ON file.id = r.attachment_file_id AND file.company_id = r.company_id \
        LEFT JOIN LATERAL ( \
            SELECT d.note, au.full_name AS approver_name \
            FROM request_decisions d \
            INNER JOIN users au ON au.id = d.approver_id \
            WHERE d.company_id = r.company_id AND d.request_id = r.id \
            ORDER BY d.decided_at DESC LIMIT 1 \
        ) latest ON true \
        WHERE r.company_id = $1 AND r.user_id <> $2 \
        AND r.status IN ('pending_manager', 'pending_admin') \
        AND ($3 OR ((r.status = 'pending_manager' OR $4) AND EXISTS ( \
            SELECT 1 FROM employee_profiles p WHERE p.company_id = r.company_id \
            AND p.user_id = r.user_id AND p.manager_user_id = $2))) \
        ORDER BY r.created_at, r.id LIMIT 200",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(
        context
            .capabilities
            .iter()
            .any(|p| p == access::APPROVALS_COMPANY),
    )
    .bind(
        context
            .capabilities
            .iter()
            .any(|p| p == access::APPROVALS_ADMIN),
    )
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(serde_json::json!(
        rows.into_iter()
            .map(|r| serde_json::json!({
                "id":r.0, "kind":r.1, "starts_on":r.2, "ends_on":r.3, "minutes":r.4,
                "amount_cents":r.5, "target_user_id":r.6, "target_user_name":r.7,
                "attachment_file_id":r.8, "attachment_file_name":r.9,
                "attachment_download_url":r.8.map(|id| format!("/v1/files/{id}/download")),
                "reason":r.10, "status":r.11, "full_name":r.12,
                "decision_note":r.13, "decided_by":r.14, "details":r.15
            }))
            .collect::<Vec<_>>()
    )))
}

struct AuthContext {
    capabilities: Vec<String>,
    user_id: Uuid,
    full_name: String,
    email: String,
    company_id: Uuid,
    company_name: String,
    role: String,
}

async fn logout(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Response, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    if let Some(token) = session_token(&headers) {
        sqlx::query(
            "UPDATE sessions SET revoked_at=COALESCE(revoked_at,NOW()) WHERE token_hash=$1",
        )
        .bind(hash_session_token(token))
        .execute(&state.pool)
        .await
        .map_err(database_error)?;
    }
    let cookie = format!(
        "paspapan_session=; HttpOnly; SameSite=Lax; Path=/; Max-Age=0{}",
        if session_cookie_is_secure() {
            "; Secure"
        } else {
            ""
        }
    );
    let mut response = StatusCode::NO_CONTENT.into_response();
    response.headers_mut().insert(
        header::SET_COOKIE,
        HeaderValue::from_str(&cookie).map_err(|_| ApiError::Internal)?,
    );
    response
        .headers_mut()
        .insert(header::CACHE_CONTROL, HeaderValue::from_static("no-store"));
    Ok(response)
}

async fn create_employee(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<paspapan_contracts::CreateEmployeeRequest>,
) -> Result<(StatusCode, Json<EmployeeResponse>), ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::EMPLOYEES_MANAGE)?;
    let email = normalise_email(&body.email);
    if body.full_name.trim().is_empty()
        || body.full_name.len() > 200
        || body.employee_code.trim().is_empty()
        || body.employee_code.len() > 80
        || email.len() > 254
        || !email.contains('@')
        || email.chars().any(char::is_whitespace)
        || body.password.len() < 12
        || body.password.len() > 1024
    {
        return Err(ApiError::BadRequest(
            "invalid employee details; password must have at least 12 characters",
        ));
    }
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut transaction, context.company_id).await?;
    let id = Uuid::now_v7();
    let manager_id = body
        .manager_user_id
        .filter(|s| !s.is_empty())
        .map(|value| Uuid::parse_str(&value).map_err(|_| ApiError::BadRequest("invalid manager")))
        .transpose()?;
    people::validate_manager(&mut transaction, context.company_id, id, manager_id).await?;
    let password_hash = hash_password(&body.password)?;
    let inserted = sqlx::query("INSERT INTO users (id,email,password_hash,full_name) VALUES ($1,$2,$3,$4) ON CONFLICT DO NOTHING")
        .bind(id).bind(&email).bind(password_hash).bind(body.full_name.trim())
        .execute(&mut *transaction).await.map_err(database_error)?;
    if inserted.rows_affected() == 0 {
        return Err(ApiError::Conflict("email already registered"));
    }
    sqlx::query(
        "INSERT INTO company_memberships(company_id,user_id,role) VALUES ($1,$2,'employee')",
    )
    .bind(context.company_id)
    .bind(id)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    let profile = sqlx::query(
        "INSERT INTO employee_profiles(company_id,user_id,employee_code,manager_user_id)
        VALUES ($1,$2,$3,$4) ON CONFLICT DO NOTHING",
    )
    .bind(context.company_id)
    .bind(id)
    .bind(body.employee_code.trim())
    .bind(manager_id)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    if profile.rows_affected() == 0 {
        return Err(ApiError::Conflict("employee code already exists"));
    }
    sqlx::query(
        "INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id)
        VALUES ($1,$2,$3,'employee.created','user',$4)",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(id)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;
    Ok((
        StatusCode::CREATED,
        Json(EmployeeResponse {
            id: id.to_string(),
            full_name: body.full_name.trim().to_owned(),
            email,
            role: "employee".to_owned(),
            employee_code: Some(body.employee_code.trim().to_owned()),
        }),
    ))
}

async fn my_schedule(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows = sqlx::query_as::<_, (String, String, String, String, bool)>(
        "SELECT a.work_date::text, s.name, s.starts_at::text, s.ends_at::text, s.crosses_midnight
        FROM shift_assignments a JOIN shifts s ON s.id=a.shift_id AND s.company_id=a.company_id
        WHERE a.company_id=$1 AND a.user_id=$2 ORDER BY a.work_date DESC LIMIT 100",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(serde_json::json!(
        rows.into_iter()
            .map(|r| serde_json::json!({
                "date":r.0, "name":r.1, "starts_at":r.2, "ends_at":r.3, "crosses_midnight":r.4
            }))
            .collect::<Vec<_>>()
    )))
}

async fn attendance_history(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows = sqlx::query_as::<_, (Uuid, String, Option<String>, Option<String>)>(
        "SELECT a.id, a.attendance_date::text,
        to_char(a.checked_in_at AT TIME ZONE c.timezone, 'HH24:MI:SS'),
        to_char(a.checked_out_at AT TIME ZONE c.timezone, 'HH24:MI:SS')
        FROM attendances a JOIN companies c ON c.id=a.company_id
        WHERE a.company_id=$1 AND a.user_id=$2 ORDER BY a.attendance_date DESC LIMIT 100",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(serde_json::json!(
        rows.into_iter()
            .map(|r| serde_json::json!({
                "id":r.0, "date":r.1, "checked_in_at":r.2, "checked_out_at":r.3
            }))
            .collect::<Vec<_>>()
    )))
}

async fn my_notifications(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows = sqlx::query_as::<_, (Uuid, String, String, bool)>(
        "SELECT id,title,body,read_at IS NOT NULL FROM notifications WHERE company_id=$1 AND user_id=$2 ORDER BY created_at DESC LIMIT 100")
        .bind(context.company_id).bind(context.user_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(serde_json::json!(
        rows.into_iter()
            .map(|r| serde_json::json!({
                "id":r.0,"title":r.1,"body":r.2,"read":r.3
            }))
            .collect::<Vec<_>>()
    )))
}

async fn unread_notification_count(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<serde_json::Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let count = sqlx::query_scalar::<_, i64>(
        "SELECT count(*) FROM notifications WHERE company_id=$1 AND user_id=$2 AND read_at IS NULL",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(serde_json::json!({ "count": count })))
}

async fn read_notification(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let result = sqlx::query("UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=$1 AND company_id=$2 AND user_id=$3")
        .bind(id).bind(context.company_id).bind(context.user_id).execute(&state.pool).await.map_err(database_error)?;
    if result.rows_affected() == 0 {
        return Err(ApiError::BadRequest("notification not found"));
    }
    Ok(StatusCode::NO_CONTENT)
}

async fn authenticated_context(
    pool: &PgPool,
    headers: &axum::http::HeaderMap,
) -> Result<AuthContext, ApiError> {
    let token = session_token(headers).ok_or(ApiError::Unauthorized)?;
    let token_hash = hash_session_token(token);
    let session = sqlx::query_as::<_, (Uuid, String, String, Uuid, String, String)>(
        "SELECT users.id, users.full_name, users.email, companies.id, companies.name, company_memberships.role \
         FROM sessions \
         INNER JOIN users ON users.id = sessions.user_id \
         INNER JOIN company_memberships ON company_memberships.user_id = users.id \
             AND company_memberships.company_id = sessions.company_id \
         INNER JOIN companies ON companies.id = company_memberships.company_id \
         WHERE sessions.token_hash = $1 \
           AND sessions.revoked_at IS NULL \
           AND sessions.expires_at > NOW() \
           AND users.status = 'active' \
           AND company_memberships.employment_status = 'active' \
           AND (company_memberships.access_revoked_at IS NULL OR sessions.created_at > company_memberships.access_revoked_at) \
         ORDER BY company_memberships.created_at ASC LIMIT 1",
    )
    .bind(token_hash)
    .fetch_optional(pool)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::Unauthorized)?;

    let policy = sqlx::query_as::<_, (Option<Vec<String>>, Vec<String>, Vec<String>)>(
        "SELECT r.permissions, a.grants, a.denies FROM member_access a LEFT JOIN access_roles r ON r.company_id=a.company_id AND r.id=a.role_id WHERE a.company_id=$1 AND a.user_id=$2"
    ).bind(session.3).bind(session.0).fetch_optional(pool).await.map_err(database_error)?;
    let effective = match policy {
        Some((custom, grants, denies)) => {
            access::effective(&session.5, custom.as_deref(), &grants, &denies)
        }
        None => access::effective(&session.5, None, &[], &[]),
    };
    Ok(AuthContext {
        capabilities: effective,
        user_id: session.0,
        full_name: session.1,
        email: session.2,
        company_id: session.3,
        company_name: session.4,
        role: session.5,
    })
}

fn validate_registration(request: &RegisterRequest) -> Result<(), ApiError> {
    let slug = request.company_slug.trim();
    let slug_is_valid = (3..=63).contains(&slug.len())
        && slug
            .bytes()
            .all(|byte| byte.is_ascii_lowercase() || byte.is_ascii_digit() || byte == b'-')
        && !slug.starts_with('-')
        && !slug.ends_with('-');

    if request.company_name.trim().is_empty()
        || request.full_name.trim().is_empty()
        || !slug_is_valid
    {
        return Err(ApiError::BadRequest("invalid company or user details"));
    }
    if !request.email.contains('@') || request.password.len() < 12 {
        return Err(ApiError::BadRequest(
            "email must be valid and password must be at least 12 characters",
        ));
    }
    Ok(())
}

fn validate_coordinates(latitude: f64, longitude: f64) -> Result<(), ApiError> {
    if !latitude.is_finite()
        || !longitude.is_finite()
        || !(-90.0..=90.0).contains(&latitude)
        || !(-180.0..=180.0).contains(&longitude)
    {
        return Err(ApiError::BadRequest("invalid GPS coordinates"));
    }
    Ok(())
}

fn require_capability(context: &AuthContext, capability: &str) -> Result<(), ApiError> {
    if context.capabilities.iter().any(|value| value == capability) {
        Ok(())
    } else {
        Err(ApiError::Forbidden)
    }
}

fn is_time(value: &str) -> bool {
    let bytes = value.as_bytes();
    (bytes.len() == 5 || bytes.len() == 8)
        && bytes.get(2) == Some(&b':')
        && (bytes.len() == 5 || bytes.get(5) == Some(&b':'))
        && bytes.iter().enumerate().all(|(index, byte)| {
            matches!(index, 2 | 5 if bytes.len() == 8)
                || (index == 2 && bytes.len() == 5)
                || byte.is_ascii_digit()
        })
        && value[..2].parse::<u8>().is_ok_and(|v| v < 24)
        && value[3..5].parse::<u8>().is_ok_and(|v| v < 60)
        && (bytes.len() == 5 || value[6..8].parse::<u8>().is_ok_and(|v| v < 60))
}

fn is_date(value: &str) -> bool {
    if value.len() != 10
        || !value.bytes().enumerate().all(|(i, b)| {
            if matches!(i, 4 | 7) {
                b == b'-'
            } else {
                b.is_ascii_digit()
            }
        })
    {
        return false;
    }
    let year = value[0..4].parse::<u32>().unwrap_or(0);
    let month = value[5..7].parse::<u32>().unwrap_or(0);
    let day = value[8..10].parse::<u32>().unwrap_or(0);
    let max_day = match month {
        4 | 6 | 9 | 11 => 30,
        2 if year % 4 == 0 && (year % 100 != 0 || year % 400 == 0) => 29,
        2 => 28,
        1 | 3 | 5 | 7 | 8 | 10 | 12 => 31,
        _ => return false,
    };
    year > 0 && day > 0 && day <= max_day
}

fn is_month(value: &str) -> bool {
    value.len() == 7
        && value.as_bytes().get(4) == Some(&b'-')
        && value[0..4].parse::<u32>().is_ok_and(|y| y > 0)
        && value[5..7]
            .parse::<u32>()
            .is_ok_and(|m| (1..=12).contains(&m))
}

fn employee_response(row: (Uuid, String, String, String, Option<String>)) -> EmployeeResponse {
    EmployeeResponse {
        id: row.0.to_string(),
        full_name: row.1,
        email: row.2,
        role: row.3,
        employee_code: row.4,
    }
}

fn shift_response(row: (Uuid, String, String, String, bool)) -> ShiftResponse {
    ShiftResponse {
        id: row.0.to_string(),
        name: row.1,
        starts_at: row.2,
        ends_at: row.3,
        crosses_midnight: row.4,
    }
}

type WorkforceRequestRow = (
    Uuid,
    String,
    String,
    String,
    Option<i64>,
    Option<i64>,
    Option<Uuid>,
    Option<String>,
    Option<Uuid>,
    Option<String>,
    String,
    String,
    Option<String>,
    Option<String>,
    serde_json::Value,
);

fn request_response(row: WorkforceRequestRow) -> WorkforceRequestResponse {
    WorkforceRequestResponse {
        id: row.0.to_string(),
        kind: row.1,
        starts_on: row.2,
        ends_on: row.3,
        minutes: row.4,
        amount_cents: row.5,
        target_user_id: row.6.map(|id| id.to_string()),
        target_user_name: row.7,
        attachment_file_id: row.8.map(|id| id.to_string()),
        attachment_file_name: row.9,
        attachment_download_url: row.8.map(|id| format!("/v1/files/{id}/download")),
        reason: row.10,
        status: row.11,
        decision_note: row.12,
        decided_by: row.13,
        details: row.14,
    }
}

fn normalise_email(email: &str) -> String {
    email.trim().to_ascii_lowercase()
}

fn session_token(headers: &axum::http::HeaderMap) -> Option<&str> {
    headers
        .get(header::COOKIE)?
        .to_str()
        .ok()?
        .split(';')
        .map(str::trim)
        .find_map(|entry| entry.strip_prefix("paspapan_session="))
        .filter(|token| token.len() == 64 && token.bytes().all(|byte| byte.is_ascii_hexdigit()))
}

fn require_trusted_origin(
    headers: &axum::http::HeaderMap,
    app_origin: &str,
) -> Result<(), ApiError> {
    let origin = headers
        .get(header::ORIGIN)
        .and_then(|value| value.to_str().ok());
    if let Some(candidate) = origin
        && app_origin
            .split(',')
            .map(str::trim)
            .any(|allowed| is_origin_allowed(allowed, candidate))
    {
        Ok(())
    } else {
        Err(ApiError::UntrustedOrigin)
    }
}

fn is_origin_allowed(allowed: &str, candidate: &str) -> bool {
    if allowed.is_empty() {
        return false;
    }
    if allowed == candidate {
        return true;
    }
    if let Some(suffix) = allowed.strip_prefix("https://*.")
        && let Some(candidate_domain) = candidate.strip_prefix("https://")
    {
        return candidate_domain == suffix || candidate_domain.ends_with(&format!(".{suffix}"));
    }
    false
}

fn hash_password(password: &str) -> Result<String, ApiError> {
    use argon2::{
        Argon2, PasswordHasher,
        password_hash::{SaltString, rand_core::OsRng},
    };

    let salt = SaltString::generate(&mut OsRng);
    Argon2::default()
        .hash_password(password.as_bytes(), &salt)
        .map(|hash| hash.to_string())
        .map_err(|_| ApiError::Internal)
}

fn verify_password(password: &str, password_hash: &str) -> Result<(), ApiError> {
    use argon2::{Argon2, PasswordHash, PasswordVerifier};

    let parsed_hash = PasswordHash::new(password_hash).map_err(|_| ApiError::Unauthorized)?;
    Argon2::default()
        .verify_password(password.as_bytes(), &parsed_hash)
        .map_err(|_| ApiError::Unauthorized)
}

async fn require_service_key(
    State(state): State<AppState>,
    request: Request,
    next: Next,
) -> Result<Response, ApiError> {
    if request.uri().path() != "/health"
        && let Some(expected_key) = state.api_internal_key.as_deref()
    {
        verify_service_key_header(request.headers(), expected_key)?;
    }
    Ok(next.run(request).await)
}

fn verify_service_key_header(
    headers: &axum::http::HeaderMap,
    expected_key: &str,
) -> Result<(), ApiError> {
    let provided = headers
        .get(SERVICE_KEY_HEADER)
        .and_then(|value| value.to_str().ok())
        .map(str::trim)
        .ok_or(ApiError::UntrustedServiceKey)?;
    if constant_time_eq(provided.as_bytes(), expected_key.as_bytes()) {
        Ok(())
    } else {
        Err(ApiError::UntrustedServiceKey)
    }
}

fn constant_time_eq(left: &[u8], right: &[u8]) -> bool {
    if left.len() != right.len() || left.is_empty() {
        return false;
    }
    let mut diff = 0_u8;
    for (&lhs, &rhs) in left.iter().zip(right.iter()) {
        diff |= lhs ^ rhs;
    }
    diff == 0
}

pub(crate) fn hash_session_token(token: &str) -> String {
    let secret = paspapan_adapters::env_var("SESSION_SECRET").ok();
    hash_session_token_with_secret(token, secret.as_deref())
}

fn hash_session_token_with_secret(token: &str, secret: Option<&str>) -> String {
    if let Some(pepper) = secret.map(str::trim).filter(|value| !value.is_empty())
        && let Ok(mut mac) = Hmac::<Sha256>::new_from_slice(pepper.as_bytes())
    {
        mac.update(token.as_bytes());
        return hex_encode(&mac.finalize().into_bytes());
    }
    hex_encode(&Sha256::digest(token.as_bytes()))
}

async fn create_session(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    user_id: Uuid,
) -> Result<Response, ApiError> {
    create_session_in_company(transaction, user_id, None).await
}

async fn create_session_in_company(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    user_id: Uuid,
    company_id: Option<Uuid>,
) -> Result<Response, ApiError> {
    let selected:Uuid=sqlx::query_scalar("SELECT company_id FROM company_memberships WHERE user_id=$1 AND employment_status='active' AND ($2::uuid IS NULL OR company_id=$2) ORDER BY created_at,company_id LIMIT 1")
        .bind(user_id).bind(company_id).fetch_optional(&mut **transaction).await.map_err(database_error)?.ok_or(ApiError::Unauthorized)?;
    let mut raw_token = [0_u8; 32];
    rand::rng().fill_bytes(&mut raw_token);
    let token = hex_encode(&raw_token);
    let token_hash = hash_session_token(&token);

    sqlx::query(
        "INSERT INTO sessions (id, user_id, token_hash, expires_at, company_id) VALUES ($1, $2, $3, NOW() + ($4 * INTERVAL '1 second'),$5)",
    )
    .bind(Uuid::now_v7())
    .bind(user_id)
    .bind(token_hash)
    .bind(SESSION_TTL_SECONDS)
    .bind(selected)
    .execute(&mut **transaction)
    .await
    .map_err(database_error)?;

    let cookie = format!(
        "paspapan_session={token}; HttpOnly; SameSite=Lax; Path=/; Max-Age={SESSION_TTL_SECONDS}{}",
        if session_cookie_is_secure() {
            "; Secure"
        } else {
            ""
        },
    );
    let mut response = Json(SessionResponse {
        expires_in_seconds: SESSION_TTL_SECONDS,
    })
    .into_response();
    response.headers_mut().insert(
        header::SET_COOKIE,
        HeaderValue::from_str(&cookie).map_err(|_| ApiError::Internal)?,
    );
    response
        .headers_mut()
        .insert(header::CACHE_CONTROL, HeaderValue::from_static("no-store"));
    Ok(response)
}

fn session_cookie_is_secure() -> bool {
    paspapan_adapters::env_var("SESSION_COOKIE_SECURE")
        .map(|value| value != "false")
        .unwrap_or(true)
}

fn hex_encode(bytes: &[u8]) -> String {
    const HEX: &[u8; 16] = b"0123456789abcdef";
    let mut encoded = String::with_capacity(bytes.len() * 2);
    for byte in bytes {
        encoded.push(HEX[(byte >> 4) as usize] as char);
        encoded.push(HEX[(byte & 0x0f) as usize] as char);
    }
    encoded
}

fn database_error(error: sqlx::Error) -> ApiError {
    error!(%error, "database operation failed");
    ApiError::Internal
}

#[cfg(test)]
mod tests {
    use super::*;
    use axum::http::HeaderMap;

    #[test]
    fn time_values_reject_out_of_range_and_unicode_input() {
        assert!(is_time("23:59:59"));
        assert!(is_time("00:00"));
        assert!(!is_time("24:00"));
        assert!(!is_time("12:60"));
        assert!(!is_time("12:30:60"));
        assert!(!is_time("é:00"));
    }

    #[test]
    fn service_key_verification_is_constant_time_and_strict() {
        let mut headers = HeaderMap::new();
        assert!(verify_service_key_header(&headers, "paspapan-secret-key").is_err());

        headers.insert(
            SERVICE_KEY_HEADER,
            HeaderValue::from_static("wrong-secret-key"),
        );
        assert!(verify_service_key_header(&headers, "paspapan-secret-key").is_err());

        headers.insert(
            SERVICE_KEY_HEADER,
            HeaderValue::from_static("paspapan-secret-key"),
        );
        assert!(verify_service_key_header(&headers, "paspapan-secret-key").is_ok());
    }

    #[test]
    fn session_token_hashing_uses_hmac_pepper_when_configured() {
        let token = "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef";
        let unpeppered = hash_session_token_with_secret(token, None);
        let peppered_a = hash_session_token_with_secret(token, Some("pepper-alpha"));
        let peppered_b = hash_session_token_with_secret(token, Some("pepper-beta"));

        assert_eq!(unpeppered.len(), 64);
        assert_eq!(peppered_a.len(), 64);
        assert_ne!(unpeppered, peppered_a);
        assert_ne!(peppered_a, peppered_b);
    }

    #[test]
    fn origin_validation_supports_exact_and_wildcard_matching() {
        assert!(is_origin_allowed(
            "https://paspapan.demo.pandanteknik.com",
            "https://paspapan.demo.pandanteknik.com"
        ));
        assert!(is_origin_allowed(
            "https://*.pandanteknik.com",
            "https://paspapan.demo.pandanteknik.com"
        ));
        assert!(is_origin_allowed(
            "https://*.pages.dev",
            "https://paspapan.pages.dev"
        ));
        assert!(is_origin_allowed(
            "https://*.pages.dev",
            "https://e51926d2.paspapan.pages.dev"
        ));
        assert!(!is_origin_allowed(
            "https://*.pandanteknik.com",
            "https://evil-attacker.com"
        ));
    }
}
