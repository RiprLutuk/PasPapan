use super::*;
use axum::extract::{Path, Query};
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
pub struct ModuleQuery {
    pub module: Option<String>,
    pub month: Option<String>,
    pub mine: Option<bool>,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct CreateWorkspaceRecordRequest {
    pub module_key: String,
    pub title: String,
    pub detail: String,
    pub status: Option<String>,
    pub amount: Option<String>,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct UpdateWorkspaceRecordStatusRequest {
    pub status: String,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SavePayrollRequest {
    pub period_start: String,
    pub period_end: String,
    pub status: Option<String>,
    pub user_id: Option<String>,
    pub gross_amount: Option<i64>,
    pub deduction_amount: Option<i64>,
}

fn require_module_access(context: &AuthContext, module_key: &str) -> Result<(), ApiError> {
    if module_key.starts_with("/admin") {
        if context
            .capabilities
            .contains(&access::DASHBOARD_VIEW.to_owned())
            || context
                .capabilities
                .contains(&access::EMPLOYEES_VIEW.to_owned())
            || context
                .capabilities
                .contains(&access::SHIFTS_MANAGE.to_owned())
            || context
                .capabilities
                .contains(&access::SCHEDULES_MANAGE.to_owned())
            || context
                .capabilities
                .contains(&access::APPROVALS_ADMIN.to_owned())
        {
            Ok(())
        } else {
            Err(ApiError::Forbidden)
        }
    } else {
        Ok(())
    }
}

pub async fn overview(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::DASHBOARD_VIEW)?;

    let employee_count: i64 = sqlx::query_scalar(
        "SELECT COUNT(*) FROM company_memberships WHERE company_id = $1 AND employment_status = 'active'",
    )
    .bind(context.company_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    let today_present_count: i64 = sqlx::query_scalar(
        "SELECT COUNT(*) FROM attendances WHERE company_id = $1 AND attendance_date = CURRENT_DATE AND checked_in_at IS NOT NULL",
    )
    .bind(context.company_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    let pending_requests_count: i64 = sqlx::query_scalar(
        "SELECT COUNT(*) FROM workforce_requests WHERE company_id = $1 AND status IN ('pending_manager', 'pending_admin')",
    )
    .bind(context.company_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    let active_locations_count: i64 = sqlx::query_scalar(
        "SELECT COUNT(*) FROM attendance_locations WHERE company_id = $1 AND active = true",
    )
    .bind(context.company_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    let recent_attendances = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'id', a.id,
            'full_name', u.full_name,
            'date', a.attendance_date::text,
            'checked_in_at', to_char(a.checked_in_at AT TIME ZONE 'UTC', 'HH24:MI'),
            'checked_out_at', to_char(a.checked_out_at AT TIME ZONE 'UTC', 'HH24:MI'),
            'location_name', COALESCE(l.name, 'Lokasi kerja')
        )
        FROM attendances a
        JOIN users u ON u.id = a.user_id
        LEFT JOIN attendance_locations l ON l.company_id = a.company_id AND l.id = a.check_in_location_id
        WHERE a.company_id = $1
        ORDER BY a.attendance_date DESC, a.checked_in_at DESC NULLS LAST
        LIMIT 8",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;

    let recent_audit_events = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'id', e.id,
            'event_type', e.event_type,
            'actor_name', COALESCE(u.full_name, 'Sistem'),
            'subject_type', e.subject_type,
            'created_at', to_char(e.created_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI')
        )
        FROM audit_events e
        LEFT JOIN users u ON u.id = e.actor_user_id
        WHERE e.company_id = $1
        ORDER BY e.created_at DESC
        LIMIT 8",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;

    Ok(Json(json!({
        "employee_count": employee_count,
        "today_present_count": today_present_count,
        "pending_requests_count": pending_requests_count,
        "active_locations_count": active_locations_count,
        "recent_attendances": recent_attendances,
        "recent_audit_events": recent_audit_events
    })))
}

pub async fn attendances(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Query(query): Query<ModuleQuery>,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_module_access(&context, "/admin/attendances")?;

    let month_prefix = query.month.unwrap_or_default();
    let rows = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'id', a.id,
            'user_id', a.user_id,
            'full_name', u.full_name,
            'email', u.email,
            'employee_code', ep.employee_code,
            'date', a.attendance_date::text,
            'checked_in_at', to_char(a.checked_in_at AT TIME ZONE 'UTC', 'HH24:MI'),
            'checked_out_at', to_char(a.checked_out_at AT TIME ZONE 'UTC', 'HH24:MI'),
            'location_name', COALESCE(l.name, 'Lokasi kerja'),
            'status', CASE
                WHEN a.checked_in_at IS NOT NULL AND a.checked_out_at IS NOT NULL THEN 'Lengkap'
                WHEN a.checked_in_at IS NOT NULL THEN 'Sudah masuk'
                ELSE 'Belum tercatat'
            END
        )
        FROM attendances a
        JOIN users u ON u.id = a.user_id
        LEFT JOIN employee_profiles ep ON ep.company_id = a.company_id AND ep.user_id = a.user_id
        LEFT JOIN attendance_locations l ON l.company_id = a.company_id AND l.id = a.check_in_location_id
        WHERE a.company_id = $1
          AND ($2 = '' OR a.attendance_date::text LIKE ($2 || '%'))
        ORDER BY a.attendance_date DESC, u.full_name ASC
        LIMIT 200",
    )
    .bind(context.company_id)
    .bind(month_prefix.trim())
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;

    Ok(Json(rows))
}

pub async fn activity_logs(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_module_access(&context, "/admin/activity-logs")?;

    let logs = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'id', e.id,
            'title', e.event_type,
            'detail', COALESCE(u.full_name, 'Sistem') || ' · ' || e.subject_type || ' (' || left(e.subject_id, 8) || ')',
            'status', 'Tercatat',
            'updated', to_char(e.created_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI')
        )
        FROM audit_events e
        LEFT JOIN users u ON u.id = e.actor_user_id
        WHERE e.company_id = $1
        ORDER BY e.created_at DESC
        LIMIT 100",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;

    Ok(Json(logs))
}

pub async fn list_payrolls(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let is_admin = context
        .capabilities
        .contains(&access::DASHBOARD_VIEW.to_owned());

    let periods = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'id', id,
            'period_start', period_start::text,
            'period_end', period_end::text,
            'status', status
        )
        FROM payroll_periods
        WHERE company_id = $1
        ORDER BY period_start DESC",
    )
    .bind(context.company_id)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;

    let records = if is_admin {
        sqlx::query_scalar::<_, Value>(
            "SELECT jsonb_build_object(
                'id', pr.id,
                'period_id', pr.period_id,
                'user_id', pr.user_id,
                'full_name', u.full_name,
                'period_start', pp.period_start::text,
                'period_end', pp.period_end::text,
                'period_status', pp.status,
                'gross_amount', pr.gross_amount::bigint,
                'deduction_amount', pr.deduction_amount::bigint,
                'net_amount', pr.net_amount::bigint
            )
            FROM payroll_records pr
            JOIN payroll_periods pp ON pp.id = pr.period_id
            JOIN users u ON u.id = pr.user_id
            WHERE pr.company_id = $1
            ORDER BY pp.period_start DESC, u.full_name ASC",
        )
        .bind(context.company_id)
        .fetch_all(&state.pool)
        .await
        .map_err(database_error)?
    } else {
        sqlx::query_scalar::<_, Value>(
            "SELECT jsonb_build_object(
                'id', pr.id,
                'period_id', pr.period_id,
                'user_id', pr.user_id,
                'full_name', u.full_name,
                'period_start', pp.period_start::text,
                'period_end', pp.period_end::text,
                'period_status', pp.status,
                'gross_amount', pr.gross_amount::bigint,
                'deduction_amount', pr.deduction_amount::bigint,
                'net_amount', pr.net_amount::bigint
            )
            FROM payroll_records pr
            JOIN payroll_periods pp ON pp.id = pr.period_id
            JOIN users u ON u.id = pr.user_id
            WHERE pr.company_id = $1 AND pr.user_id = $2
            ORDER BY pp.period_start DESC",
        )
        .bind(context.company_id)
        .bind(context.user_id)
        .fetch_all(&state.pool)
        .await
        .map_err(database_error)?
    };

    Ok(Json(json!({
        "periods": periods,
        "records": records
    })))
}

pub async fn save_payroll(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<SavePayrollRequest>,
) -> Result<(StatusCode, Json<Value>), ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::DASHBOARD_VIEW)?;

    let period_start = request.period_start.trim();
    let period_end = request.period_end.trim();
    if !is_date(period_start) || !is_date(period_end) || period_end < period_start {
        return Err(ApiError::BadRequest("invalid payroll period dates"));
    }
    let status = request.status.as_deref().unwrap_or("draft").trim();
    if !matches!(status, "draft" | "published" | "closed") {
        return Err(ApiError::BadRequest("invalid payroll status"));
    }

    let period_id: Uuid = sqlx::query_scalar(
        "INSERT INTO payroll_periods (id, company_id, period_start, period_end, status)
         VALUES ($1, $2, $3::date, $4::date, $5)
         ON CONFLICT (company_id, period_start, period_end)
         DO UPDATE SET status = EXCLUDED.status
         RETURNING id",
    )
    .bind(Uuid::now_v7())
    .bind(context.company_id)
    .bind(period_start)
    .bind(period_end)
    .bind(status)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    if let Some(raw_user_id) = request.user_id.as_deref()
        && !raw_user_id.trim().is_empty()
    {
        let target_user_id = Uuid::parse_str(raw_user_id.trim())
            .map_err(|_| ApiError::BadRequest("invalid user"))?;
        let gross = request.gross_amount.unwrap_or(0);
        let deduction = request.deduction_amount.unwrap_or(0);
        if gross < 0 || deduction < 0 || deduction > gross {
            return Err(ApiError::BadRequest("invalid payroll amounts"));
        }

        sqlx::query(
            "INSERT INTO payroll_records (id, period_id, company_id, user_id, gross_amount, deduction_amount)
             VALUES ($1, $2, $3, $4, $5, $6)
             ON CONFLICT (period_id, user_id)
             DO UPDATE SET gross_amount = EXCLUDED.gross_amount, deduction_amount = EXCLUDED.deduction_amount, updated_at = now()",
        )
        .bind(Uuid::now_v7())
        .bind(period_id)
        .bind(context.company_id)
        .bind(target_user_id)
        .bind(gross)
        .bind(deduction)
        .execute(&state.pool)
        .await
        .map_err(database_error)?;
    }

    Ok((
        StatusCode::CREATED,
        Json(json!({ "period_id": period_id, "status": status })),
    ))
}

pub async fn list_records(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Query(query): Query<ModuleQuery>,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let module_key = query
        .module
        .as_deref()
        .unwrap_or("/workspace")
        .trim()
        .to_owned();
    if module_key.is_empty() || module_key.len() > 120 {
        return Err(ApiError::BadRequest("invalid module key"));
    }
    require_module_access(&context, &module_key)?;

    let only_mine = query.mine.unwrap_or(false) || module_key == "/face-enrollment";
    let rows = if only_mine {
        sqlx::query_scalar::<_, Value>(
            "SELECT jsonb_build_object(
                'id', r.id,
                'module_key', r.module_key,
                'title', r.title,
                'detail', r.detail,
                'status', r.status,
                'amount', r.amount,
                'created_by_name', u.full_name,
                'updated', to_char(r.updated_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI')
            )
            FROM workspace_records r
            JOIN users u ON u.id = r.created_by
            WHERE r.company_id = $1 AND r.module_key = $2 AND r.created_by = $3
            ORDER BY r.created_at DESC
            LIMIT 200",
        )
        .bind(context.company_id)
        .bind(&module_key)
        .bind(context.user_id)
        .fetch_all(&state.pool)
        .await
        .map_err(database_error)?
    } else {
        sqlx::query_scalar::<_, Value>(
            "SELECT jsonb_build_object(
                'id', r.id,
                'module_key', r.module_key,
                'title', r.title,
                'detail', r.detail,
                'status', r.status,
                'amount', r.amount,
                'created_by_name', u.full_name,
                'updated', to_char(r.updated_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI')
            )
            FROM workspace_records r
            JOIN users u ON u.id = r.created_by
            WHERE r.company_id = $1 AND r.module_key = $2
            ORDER BY r.created_at DESC
            LIMIT 200",
        )
        .bind(context.company_id)
        .bind(&module_key)
        .fetch_all(&state.pool)
        .await
        .map_err(database_error)?
    };

    Ok(Json(rows))
}

pub async fn create_record(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(request): Json<CreateWorkspaceRecordRequest>,
) -> Result<(StatusCode, Json<Value>), ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;

    let module_key = request.module_key.trim();
    let title = request.title.trim();
    let detail = request.detail.trim();
    let status = request.status.as_deref().unwrap_or("Aktif").trim();
    let amount = request
        .amount
        .as_deref()
        .map(str::trim)
        .filter(|value| !value.is_empty());

    if module_key.is_empty() || module_key.len() > 120 {
        return Err(ApiError::BadRequest("invalid module key"));
    }
    if title.is_empty() || title.len() > 200 {
        return Err(ApiError::BadRequest("title must be 1 to 200 characters"));
    }
    if detail.len() > 1000 {
        return Err(ApiError::BadRequest("detail is too long"));
    }
    if status.is_empty() || status.len() > 80 {
        return Err(ApiError::BadRequest("invalid status"));
    }
    require_module_access(&context, module_key)?;

    if module_key == "/face-enrollment" {
        sqlx::query(
            "DELETE FROM workspace_records
             WHERE company_id = $1 AND created_by = $2 AND module_key = $3",
        )
        .bind(context.company_id)
        .bind(context.user_id)
        .bind(module_key)
        .execute(&state.pool)
        .await
        .map_err(database_error)?;
    }

    let id = Uuid::now_v7();
    let record = sqlx::query_scalar::<_, Value>(
        "INSERT INTO workspace_records (id, company_id, created_by, module_key, title, detail, status, amount)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
         RETURNING jsonb_build_object(
            'id', id,
            'module_key', module_key,
            'title', title,
            'detail', detail,
            'status', status,
            'amount', amount,
            'updated', to_char(updated_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI')
         )",
    )
    .bind(id)
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(module_key)
    .bind(title)
    .bind(detail)
    .bind(status)
    .bind(amount)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    Ok((StatusCode::CREATED, Json(record)))
}

pub async fn update_record_status(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<String>,
    Json(request): Json<UpdateWorkspaceRecordStatusRequest>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let record_id = Uuid::parse_str(&id).map_err(|_| ApiError::BadRequest("invalid record id"))?;
    let status = request.status.trim();
    if status.is_empty() || status.len() > 80 {
        return Err(ApiError::BadRequest("invalid status"));
    }

    let updated = sqlx::query_scalar::<_, Value>(
        "UPDATE workspace_records
         SET status = $1, updated_at = now()
         WHERE company_id = $2 AND id = $3
         RETURNING jsonb_build_object(
            'id', id,
            'module_key', module_key,
            'title', title,
            'detail', detail,
            'status', status,
            'amount', amount,
            'updated', to_char(updated_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI')
         )",
    )
    .bind(status)
    .bind(context.company_id)
    .bind(record_id)
    .fetch_optional(&state.pool)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("record not found"))?;

    Ok(Json(updated))
}

pub async fn delete_record(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<String>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let record_id = Uuid::parse_str(&id).map_err(|_| ApiError::BadRequest("invalid record id"))?;

    let affected = sqlx::query("DELETE FROM workspace_records WHERE company_id = $1 AND id = $2")
        .bind(context.company_id)
        .bind(record_id)
        .execute(&state.pool)
        .await
        .map_err(database_error)?
        .rows_affected();

    if affected == 0 {
        return Err(ApiError::BadRequest("record not found"));
    }

    Ok(StatusCode::NO_CONTENT)
}

pub async fn system_health(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::DASHBOARD_VIEW)?;

    let pg_telemetry = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'server_version', current_setting('server_version'),
            'max_connections', current_setting('max_connections')::int,
            'database_size_bytes', pg_database_size(current_database())::bigint,
            'database_size_pretty', pg_size_pretty(pg_database_size(current_database())),
            'activity', (
                SELECT jsonb_build_object(
                    'total_connections', count(*)::int,
                    'active_queries', count(*) FILTER (WHERE state = 'active')::int,
                    'idle_connections', count(*) FILTER (WHERE state = 'idle')::int,
                    'idle_in_transaction', count(*) FILTER (WHERE state LIKE 'idle in transaction%')::int,
                    'waiting_locks', count(*) FILTER (WHERE wait_event_type = 'Lock')::int,
                    'longest_query_ms', COALESCE(MAX(EXTRACT(EPOCH FROM (clock_timestamp() - query_start)) * 1000) FILTER (WHERE state = 'active'), 0)::bigint
                )
                FROM pg_stat_activity
                WHERE datname = current_database()
            ),
            'sessions', COALESCE((
                SELECT jsonb_agg(row_to_json(s))
                FROM (
                    SELECT
                        pid,
                        COALESCE(usename, 'background') AS usename,
                        COALESCE(NULLIF(application_name, ''), 'sqlx-pool') AS application_name,
                        COALESCE(state, 'background') AS state,
                        COALESCE(wait_event_type, '-') AS wait_event_type,
                        GREATEST(0, COALESCE(EXTRACT(EPOCH FROM (clock_timestamp() - state_change)) * 1000, 0))::bigint AS state_duration_ms,
                        left(regexp_replace(COALESCE(query, ''), '\\s+', ' ', 'g'), 120) AS query_preview
                    FROM pg_stat_activity
                    WHERE datname = current_database()
                    ORDER BY CASE WHEN state = 'active' THEN 0 ELSE 1 END, query_start DESC NULLS LAST
                    LIMIT 15
                ) s
            ), '[]'::jsonb),
            'database_stats', (
                SELECT jsonb_build_object(
                    'xact_commit', xact_commit::bigint,
                    'xact_rollback', xact_rollback::bigint,
                    'blks_read', blks_read::bigint,
                    'blks_hit', blks_hit::bigint,
                    'cache_hit_ratio_pct', CASE
                        WHEN (blks_hit + blks_read) > 0
                        THEN round((blks_hit::numeric / (blks_hit + blks_read)::numeric) * 100, 2)
                        ELSE 100.0
                    END,
                    'deadlocks', deadlocks::bigint,
                    'temp_bytes', temp_bytes::bigint,
                    'tup_returned', tup_returned::bigint,
                    'tup_inserted', tup_inserted::bigint,
                    'tup_updated', tup_updated::bigint,
                    'tup_deleted', tup_deleted::bigint
                )
                FROM pg_stat_database
                WHERE datname = current_database()
            ),
            'extensions', COALESCE((
                SELECT jsonb_agg(jsonb_build_object('name', extname, 'version', extversion) ORDER BY extname)
                FROM pg_extension
            ), '[]'::jsonb)
        )",
    )
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    let tenant_metrics = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'company_id', $1,
            'stored_files_count', (SELECT count(*)::bigint FROM private_files WHERE company_id = $1),
            'stored_files_bytes', COALESCE((SELECT sum(byte_size)::bigint FROM private_files WHERE company_id = $1), 0),
            'audit_events_count', (SELECT count(*)::bigint FROM audit_events WHERE company_id = $1),
            'active_sessions_count', (
                SELECT count(*)::bigint
                FROM sessions s
                JOIN company_memberships m ON m.user_id = s.user_id
                WHERE m.company_id = $1 AND s.revoked_at IS NULL AND s.expires_at > now()
            )
        )",
    )
    .bind(context.company_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    let cfg = state.storage.config();
    let storage_cfg = json!({
        "driver": state.storage.driver_name(),
        "bucket": cfg.bucket,
        "region": cfg.region,
        "endpoint": cfg.endpoint,
        "force_path_style": cfg.force_path_style
    });

    let mail_cfg = state.mailer.config();
    let mailer_info = json!({
        "driver": state.mailer.driver_name(),
        "from_name": mail_cfg.from_name,
        "from_address": mail_cfg.from_address,
        "spooled_messages": paspapan_adapters::mailer::Mailer::spooled_messages_count(&mail_cfg.local_spool_dir)
    });

    Ok(Json(json!({
        "postgres": pg_telemetry,
        "sqlx_pool": {
            "size": state.pool.size(),
            "idle": state.pool.num_idle(),
            "active": state.pool.size().saturating_sub(state.pool.num_idle() as u32)
        },
        "storage": storage_cfg,
        "mailer": mailer_info,
        "tenant": tenant_metrics
    })))
}

pub async fn events_stream(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Response, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;

    let pulse = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'timestamp', to_char(now() AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"'),
            'unread_notifications', (
                SELECT count(*)::bigint
                FROM notifications
                WHERE company_id = $1 AND user_id = $2 AND read_at IS NULL
            ),
            'latest_notification', (
                SELECT jsonb_build_object(
                    'id', id,
                    'title', title,
                    'body', body,
                    'created_at', to_char(created_at AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"')
                )
                FROM notifications
                WHERE company_id = $1 AND user_id = $2 AND read_at IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            ),
            'pending_approvals', (
                SELECT count(*)::bigint
                FROM workforce_requests
                WHERE company_id = $1 AND status IN ('pending_manager', 'pending_admin')
            ),
            'checked_in_today', (
                SELECT count(*)::bigint
                FROM attendances a
                JOIN companies c ON c.id = a.company_id
                WHERE a.company_id = $1
                  AND a.attendance_date = (now() AT TIME ZONE c.timezone)::date
                  AND a.checked_in_at IS NOT NULL
            ),
            'pg_active_connections', (
                SELECT count(*)::int
                FROM pg_stat_activity
                WHERE datname = current_database() AND state = 'active'
            ),
            'latest_audit_at', (
                SELECT to_char(max(occurred_at) AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')
                FROM audit_events
                WHERE company_id = $1
            )
        )",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .fetch_one(&state.pool)
    .await
    .map_err(database_error)?;

    let payload = format!("retry: 4000\nevent: pulse\ndata: {}\n\n", pulse);
    let mut response = (StatusCode::OK, payload).into_response();
    response.headers_mut().insert(
        header::CONTENT_TYPE,
        HeaderValue::from_static("text/event-stream; charset=utf-8"),
    );
    response.headers_mut().insert(
        header::CACHE_CONTROL,
        HeaderValue::from_static("no-cache, no-store"),
    );
    Ok(response)
}
