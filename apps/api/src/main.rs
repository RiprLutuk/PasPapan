use axum::{
    Json, Router,
    extract::{Path, State},
    http::{HeaderValue, StatusCode, header},
    response::{IntoResponse, Response},
    routing::{get, post},
};
use paspapan_contracts::{
    AssignShiftRequest, CheckInRequest, CheckOutRequest, CreateRequestRequest, CreateShiftRequest,
    CurrentCompany, CurrentSessionResponse, CurrentUser, DecideRequestRequest, EmployeeResponse,
    HealthResponse, LoginRequest, RegisterRequest, SessionResponse, ShiftResponse,
    TodayAttendanceResponse, WorkforceRequestResponse,
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
        .route("/v1/auth/logout", post(logout))
        .route("/v1/auth/session", get(current_session))
        .route("/v1/attendance/today", get(today_attendance))
        .route("/v1/attendance/check-in", post(check_in))
        .route("/v1/attendance/check-out", post(check_out))
        .route(
            "/v1/admin/employees",
            get(list_employees).post(create_employee),
        )
        .route("/v1/admin/shifts", get(list_shifts).post(create_shift))
        .route("/v1/admin/shift-assignments", post(assign_shift))
        .route("/v1/requests", get(list_requests).post(create_request))
        .route("/v1/approvals", get(list_approvals))
        .route("/v1/schedule", get(my_schedule))
        .route("/v1/attendance/history", get(attendance_history))
        .route("/v1/notifications", get(my_notifications))
        .route("/v1/notifications/{id}/read", post(read_notification))
        .route("/v1/requests/{id}/manager-decision", post(manager_decision))
        .route("/v1/requests/{id}/admin-decision", post(admin_decision))
        .with_state(AppState { pool, app_origin });
    let listener = TcpListener::bind(
        std::env::var("API_BIND").unwrap_or_else(|_| "127.0.0.1:3000".to_owned()),
    )
    .await?;

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

async fn list_employees(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<EmployeeResponse>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_admin(&context)?;
    let rows = sqlx::query_as::<_, (Uuid, String, String, String, Option<String>)>(
        "SELECT users.id, users.full_name, users.email, company_memberships.role, employee_profiles.employee_code \
         FROM company_memberships \
         INNER JOIN users ON users.id = company_memberships.user_id \
         LEFT JOIN employee_profiles ON employee_profiles.company_id = company_memberships.company_id \
             AND employee_profiles.user_id = company_memberships.user_id \
         WHERE company_memberships.company_id = $1 AND users.status = 'active' \
         ORDER BY users.full_name",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(rows.into_iter().map(employee_response).collect()))
}

async fn list_shifts(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<ShiftResponse>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_admin(&context)?;
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
    require_admin(&context)?;
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

async fn assign_shift(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<AssignShiftRequest>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_admin(&context)?;
    let user_id =
        Uuid::parse_str(&request.user_id).map_err(|_| ApiError::BadRequest("invalid employee"))?;
    let shift_id =
        Uuid::parse_str(&request.shift_id).map_err(|_| ApiError::BadRequest("invalid shift"))?;
    if !is_date(&request.work_date) {
        return Err(ApiError::BadRequest("invalid work date"));
    }
    let assigned = sqlx::query(
        "INSERT INTO shift_assignments (id, company_id, user_id, shift_id, work_date) \
         SELECT $1, $2, $3, shifts.id, $4::date FROM shifts WHERE shifts.id = $5 AND shifts.company_id = $2 \
         ON CONFLICT (company_id, user_id, work_date) DO UPDATE SET shift_id = EXCLUDED.shift_id",
    )
    .bind(Uuid::now_v7()).bind(context.company_id).bind(user_id).bind(&request.work_date).bind(shift_id)
    .execute(&state.pool).await.map_err(database_error)?;
    if assigned.rows_affected() == 0 {
        return Err(ApiError::BadRequest("shift not found"));
    }
    Ok(StatusCode::NO_CONTENT)
}

async fn list_requests(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<WorkforceRequestResponse>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows = sqlx::query_as::<_, (Uuid, String, String, String, Option<i64>, String, String)>(
        "SELECT id, kind, starts_on::text, ends_on::text, minutes, reason, status FROM workforce_requests WHERE company_id = $1 AND user_id = $2 ORDER BY created_at DESC",
    ).bind(context.company_id).bind(context.user_id).fetch_all(&state.pool).await.map_err(database_error)?;
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
        "leave" | "overtime" | "attendance_correction" | "reimbursement"
    ) || !is_date(&request.starts_on)
        || !is_date(&request.ends_on)
        || request.ends_on < request.starts_on
        || request.reason.trim().is_empty()
        || request.reason.len() > 4000
        || request.minutes.is_some_and(|minutes| minutes <= 0)
    {
        return Err(ApiError::BadRequest("invalid request details"));
    }
    let context = authenticated_context(&state.pool, &headers).await?;
    let id = Uuid::now_v7();
    let mut transaction = state.pool.begin().await.map_err(database_error)?;
    sqlx::query("INSERT INTO workforce_requests (id, company_id, user_id, kind, starts_on, ends_on, minutes, reason) VALUES ($1,$2,$3,$4,$5::date,$6::date,$7,$8)")
        .bind(id).bind(context.company_id).bind(context.user_id).bind(&request.kind).bind(&request.starts_on).bind(&request.ends_on).bind(request.minutes).bind(request.reason.trim()).execute(&mut *transaction).await.map_err(database_error)?;
    record_request_audit(&mut transaction, &context, id, "request.created").await?;
    transaction.commit().await.map_err(database_error)?;
    Ok(Json(WorkforceRequestResponse {
        id: id.to_string(),
        kind: request.kind,
        starts_on: request.starts_on,
        ends_on: request.ends_on,
        minutes: request.minutes,
        reason: request.reason.trim().to_owned(),
        status: "pending_manager".to_owned(),
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
    if stage == "manager" && !matches!(context.role.as_str(), "manager" | "admin" | "owner") {
        return Err(ApiError::Forbidden);
    }
    if stage == "admin" {
        require_admin(&context)?;
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
    let changed = sqlx::query("UPDATE workforce_requests AS r SET status = $1, updated_at = NOW()
        WHERE r.id = $2 AND r.company_id = $3 AND r.status = $4 AND r.user_id <> $5
        AND ($6 OR EXISTS (SELECT 1 FROM employee_profiles p
            WHERE p.company_id = r.company_id AND p.user_id = r.user_id AND p.manager_user_id = $5))")
        .bind(next).bind(request_id).bind(context.company_id).bind(expected)
        .bind(context.user_id).bind(matches!(context.role.as_str(), "owner" | "admin"))
        .execute(&mut *transaction).await.map_err(database_error)?;
    if changed.rows_affected() == 0 {
        return Err(ApiError::BadRequest(
            "request is not awaiting this decision",
        ));
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
    if !matches!(context.role.as_str(), "owner" | "admin" | "manager") {
        return Err(ApiError::Forbidden);
    }
    let rows = sqlx::query_as::<_, (Uuid, String, String, String, Option<i64>, String, String, String)>(
        "SELECT r.id, r.kind, r.starts_on::text, r.ends_on::text, r.minutes, r.reason, r.status, u.full_name
        FROM workforce_requests r JOIN users u ON u.id = r.user_id
        WHERE r.company_id = $1 AND r.user_id <> $2
        AND r.status IN ('pending_manager', 'pending_admin')
        AND ($3 OR (r.status = 'pending_manager' AND EXISTS (
            SELECT 1 FROM employee_profiles p WHERE p.company_id = r.company_id
            AND p.user_id = r.user_id AND p.manager_user_id = $2)))
        ORDER BY r.created_at, r.id LIMIT 200")
        .bind(context.company_id).bind(context.user_id).bind(matches!(context.role.as_str(), "owner" | "admin"))
        .fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(serde_json::json!(
        rows.into_iter()
            .map(|r| serde_json::json!({
                "id":r.0, "kind":r.1, "starts_on":r.2, "ends_on":r.3, "minutes":r.4,
                "reason":r.5, "status":r.6, "full_name":r.7
            }))
            .collect::<Vec<_>>()
    )))
}

struct AuthContext {
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
        .bind(hex_encode(&Sha256::digest(token.as_bytes())))
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
    require_admin(&context)?;
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
    let manager_id = match body.manager_user_id.filter(|s| !s.is_empty()) {
        Some(id) => {
            let id = Uuid::parse_str(&id).map_err(|_| ApiError::BadRequest("invalid manager"))?;
            let allowed = sqlx::query_scalar::<_, bool>("SELECT EXISTS (
                SELECT 1 FROM company_memberships m JOIN users u ON u.id=m.user_id
                WHERE m.company_id=$1 AND m.user_id=$2 AND m.role IN ('owner','admin','manager') AND u.status='active')")
                .bind(context.company_id).bind(id).fetch_one(&mut *transaction).await.map_err(database_error)?;
            if !allowed {
                return Err(ApiError::BadRequest("manager not found in this company"));
            }
            Some(id)
        }
        None => None,
    };
    let password_hash = hash_password(&body.password)?;
    let id = Uuid::now_v7();
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

fn require_admin(context: &AuthContext) -> Result<(), ApiError> {
    if matches!(context.role.as_str(), "owner" | "admin") {
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

fn request_response(
    row: (Uuid, String, String, String, Option<i64>, String, String),
) -> WorkforceRequestResponse {
    WorkforceRequestResponse {
        id: row.0.to_string(),
        kind: row.1,
        starts_on: row.2,
        ends_on: row.3,
        minutes: row.4,
        reason: row.5,
        status: row.6,
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
