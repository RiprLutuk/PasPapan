use axum::{
    Json, Router,
    extract::State,
    http::{HeaderValue, StatusCode, header},
    response::{IntoResponse, Response},
    routing::{get, post},
};
use paspapan_contracts::{
    CheckInRequest, CheckOutRequest, CurrentCompany, CurrentSessionResponse, CurrentUser,
    HealthResponse, LoginRequest, RegisterRequest, SessionResponse, TodayAttendanceResponse,
};
use rand::RngCore;
use sha2::{Digest, Sha256};
use sqlx::PgPool;
use tokio::net::TcpListener;
use tracing::{error, info};
use tracing_subscriber::EnvFilter;
use uuid::Uuid;

const SESSION_TTL_SECONDS: i64 = 60 * 60 * 8;

#[derive(Clone)]
struct AppState {
    pool: PgPool,
    app_origin: String,
}

enum ApiError {
    BadRequest(&'static str),
    Unauthorized,
    Conflict(&'static str),
    Forbidden,
    Internal,
}

impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        let (status, message) = match self {
            Self::BadRequest(message) => (StatusCode::BAD_REQUEST, message),
            Self::Unauthorized => (StatusCode::UNAUTHORIZED, "invalid email or password"),
            Self::Conflict(message) => (StatusCode::CONFLICT, message),
            Self::Forbidden => (StatusCode::FORBIDDEN, "untrusted request origin"),
            Self::Internal => (StatusCode::INTERNAL_SERVER_ERROR, "internal server error"),
        };

        (status, Json(serde_json::json!({ "error": message }))).into_response()
    }
}

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    tracing_subscriber::fmt()
        .with_env_filter(EnvFilter::from_default_env())
        .init();

    let database_url = std::env::var("DATABASE_URL").map_err(|_| "DATABASE_URL must be set")?;
    let app_origin = std::env::var("APP_ORIGIN").map_err(|_| "APP_ORIGIN must be set")?;
    let pool = paspapan_adapters::connect(&database_url).await?;

    let app = Router::new()
        .route("/health", get(health))
        .route("/v1/auth/register", post(register))
        .route("/v1/auth/login", post(login))
        .route("/v1/auth/session", get(current_session))
        .route("/v1/attendance/today", get(today_attendance))
        .route("/v1/attendance/check-in", post(check_in))
        .route("/v1/attendance/check-out", post(check_out))
        .with_state(AppState { pool, app_origin });
    let listener = TcpListener::bind("0.0.0.0:3000").await?;

    info!(address = %listener.local_addr().expect("listener has address"), "API started");
    axum::serve(listener, app).await?;

    Ok(())
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
    if request.password.is_empty() || request.email.trim().is_empty() {
        return Err(ApiError::Unauthorized);
    }

    let user = sqlx::query_as::<_, (Uuid, String)>(
        "SELECT id, password_hash FROM users WHERE email = $1 AND status = 'active'",
    )
    .bind(normalise_email(&request.email))
    .fetch_optional(&state.pool)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::Unauthorized)?;

    verify_password(&request.password, &user.1)?;
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    let response = create_session(&mut transaction, user.0).await?;
    transaction.commit().await.map_err(database_error)?;
    Ok(response)
}

async fn current_session(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<CurrentSessionResponse>, ApiError> {
    let session = authenticated_context(&state.pool, &headers).await?;
    Ok(Json(CurrentSessionResponse {
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
    let row = sqlx::query_as::<_, (String, Option<String>, Option<String>, Option<String>, Option<String>, Option<String>)>(
        "SELECT (NOW() AT TIME ZONE companies.timezone)::date::text, \
                attendances.checked_in_at::text, attendances.checked_out_at::text, shifts.name, shifts.starts_at::text, shifts.ends_at::text \
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
    .bind(context.user_id)
    .bind(context.company_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    Ok(Json(TodayAttendanceResponse {
        date: row.0,
        status: if row.2.is_some() {
            "checked_out"
        } else if row.1.is_some() {
            "checked_in"
        } else {
            "not_checked_in"
        }
        .to_owned(),
        shift_name: row.3,
        starts_at: row.4,
        ends_at: row.5,
    }))
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
    let attendance = sqlx::query_as::<_, (String, Option<String>, Option<String>, Option<String>, Option<String>)>(
        "INSERT INTO attendances (id, company_id, user_id, attendance_date, checked_in_at, check_in_latitude, check_in_longitude) \
         SELECT $1, companies.id, $2, (NOW() AT TIME ZONE companies.timezone)::date, NOW(), $3, $4 \
         FROM companies WHERE companies.id = $5 \
         ON CONFLICT (company_id, user_id, attendance_date) DO UPDATE \
           SET checked_in_at = COALESCE(attendances.checked_in_at, EXCLUDED.checked_in_at), \
               check_in_latitude = COALESCE(attendances.check_in_latitude, EXCLUDED.check_in_latitude), \
               check_in_longitude = COALESCE(attendances.check_in_longitude, EXCLUDED.check_in_longitude), \
               updated_at = NOW() \
         RETURNING attendance_date::text, checked_in_at::text, NULL::text, NULL::text, NULL::text",
    )
    .bind(Uuid::now_v7())
    .bind(context.user_id)
    .bind(request.latitude)
    .bind(request.longitude)
    .bind(context.company_id)
    .fetch_one(&mut *transaction)
    .await
    .map_err(database_error)?;

    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata) \
         VALUES ($1, $2, $3, 'attendance.checked_in', 'attendance', NULL, jsonb_build_object('latitude', $4, 'longitude', $5))",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(request.latitude)
    .bind(request.longitude)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;

    Ok(Json(TodayAttendanceResponse {
        date: attendance.0,
        status: "checked_in".to_owned(),
        shift_name: attendance.2,
        starts_at: attendance.3,
        ends_at: attendance.4,
    }))
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
    let checked_out = sqlx::query_as::<_, (String,)>(
        "UPDATE attendances SET checked_out_at = NOW(), check_out_latitude = $1, check_out_longitude = $2, updated_at = NOW() \
         WHERE company_id = $3 AND user_id = $4 \
           AND attendance_date = (SELECT (NOW() AT TIME ZONE timezone)::date FROM companies WHERE id = $3) \
           AND checked_in_at IS NOT NULL AND checked_out_at IS NULL \
         RETURNING attendance_date::text",
    )
    .bind(request.latitude)
    .bind(request.longitude)
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_optional(&mut *transaction)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("check-in is required before check-out"))?;

    sqlx::query(
        "INSERT INTO audit_events (id, company_id, actor_id, event_type, subject_type, subject_id, metadata) \
         VALUES ($1, $2, $3, 'attendance.checked_out', 'attendance', NULL, jsonb_build_object('latitude', $4, 'longitude', $5))",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(request.latitude)
    .bind(request.longitude)
    .execute(&mut *transaction)
    .await
    .map_err(database_error)?;
    transaction.commit().await.map_err(database_error)?;

    Ok(Json(TodayAttendanceResponse {
        date: checked_out.0,
        status: "checked_out".to_owned(),
        shift_name: None,
        starts_at: None,
        ends_at: None,
    }))
}

struct AuthContext {
    user_id: Uuid,
    full_name: String,
    email: String,
    company_id: Uuid,
    company_name: String,
    role: String,
}

async fn authenticated_context(
    pool: &PgPool,
    headers: &axum::http::HeaderMap,
) -> Result<AuthContext, ApiError> {
    let token = session_token(headers).ok_or(ApiError::Unauthorized)?;
    let token_hash = hex_encode(&Sha256::digest(token.as_bytes()));
    let session = sqlx::query_as::<_, (Uuid, String, String, Uuid, String, String)>(
        "SELECT users.id, users.full_name, users.email, companies.id, companies.name, company_memberships.role \
         FROM sessions \
         INNER JOIN users ON users.id = sessions.user_id \
         INNER JOIN company_memberships ON company_memberships.user_id = users.id \
         INNER JOIN companies ON companies.id = company_memberships.company_id \
         WHERE sessions.token_hash = $1 \
           AND sessions.revoked_at IS NULL \
           AND sessions.expires_at > NOW() \
           AND users.status = 'active' \
         ORDER BY company_memberships.created_at ASC LIMIT 1",
    )
    .bind(token_hash)
    .fetch_optional(pool)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::Unauthorized)?;

    Ok(AuthContext {
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
    if origin == Some(app_origin) {
        Ok(())
    } else {
        Err(ApiError::Forbidden)
    }
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

async fn create_session(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    user_id: Uuid,
) -> Result<Response, ApiError> {
    let mut raw_token = [0_u8; 32];
    rand::rng().fill_bytes(&mut raw_token);
    let token = hex_encode(&raw_token);
    let token_hash = hex_encode(&Sha256::digest(token.as_bytes()));

    sqlx::query(
        "INSERT INTO sessions (id, user_id, token_hash, expires_at) VALUES ($1, $2, $3, NOW() + ($4 * INTERVAL '1 second'))",
    )
    .bind(Uuid::now_v7())
    .bind(user_id)
    .bind(token_hash)
    .bind(SESSION_TTL_SECONDS)
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
    std::env::var("SESSION_COOKIE_SECURE")
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
