use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

pub async fn prepare(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    request: &CreateRequestRequest,
) -> Result<Value, ApiError> {
    let mut details = json!({});
    if request.kind == "attendance_correction" {
        let starts = request
            .corrected_check_in
            .as_deref()
            .ok_or(ApiError::BadRequest("corrected check-in time is required"))?;
        let ends = request
            .corrected_check_out
            .as_deref()
            .ok_or(ApiError::BadRequest("corrected check-out time is required"))?;
        let time_valid = |value: &str| {
            value.len() == 5
                && value.as_bytes()[2] == b':'
                && value[..2].parse::<u8>().is_ok_and(|h| h < 24)
                && value[3..].parse::<u8>().is_ok_and(|m| m < 60)
        };
        if request.starts_on != request.ends_on
            || !starts.is_ascii()
            || !ends.is_ascii()
            || !time_valid(starts)
            || !time_valid(ends)
            || ends <= starts
        {
            return Err(ApiError::BadRequest(
                "correction requires a single day and increasing HH:MM times",
            ));
        }
        let historical: bool = sqlx::query_scalar(
            "SELECT $2::date <= (now() AT TIME ZONE timezone)::date FROM companies WHERE id=$1",
        )
        .bind(context.company_id)
        .bind(&request.starts_on)
        .fetch_one(&mut **tx)
        .await
        .map_err(database_error)?;
        if !historical {
            return Err(ApiError::BadRequest("cannot correct future attendance"));
        }
        details = json!({"corrected_check_in":starts,"corrected_check_out":ends});
    }
    if request.kind == "shift_swap" {
        if request.starts_on != request.ends_on {
            return Err(ApiError::BadRequest(
                "shift swap requires a single work date",
            ));
        }
        let target = Uuid::parse_str(request.target_user_id.as_deref().unwrap_or(""))
            .map_err(|_| ApiError::BadRequest("invalid swap colleague"))?;
        people::lock_company(tx, context.company_id).await?;
        let shifts=sqlx::query_as::<_,(Uuid,Uuid)>("SELECT user_id,shift_id FROM shift_assignments WHERE company_id=$1 AND work_date=$2::date AND user_id IN ($3,$4) ORDER BY user_id FOR UPDATE")
            .bind(context.company_id).bind(&request.starts_on).bind(context.user_id).bind(target).fetch_all(&mut **tx).await.map_err(database_error)?;
        let source_shift = shifts
            .iter()
            .find(|row| row.0 == context.user_id)
            .map(|row| row.1)
            .ok_or(ApiError::BadRequest(
                "your shift is not assigned on this date",
            ))?;
        let target_shift = shifts
            .iter()
            .find(|row| row.0 == target)
            .map(|row| row.1)
            .ok_or(ApiError::BadRequest(
                "colleague shift is not assigned on this date",
            ))?;
        if source_shift == target_shift {
            return Err(ApiError::BadRequest(
                "both employees already have the same shift",
            ));
        }
        details = json!({"source_shift_id":source_shift,"target_shift_id":target_shift,"colleague_consented":false});
    }
    Ok(details)
}

pub async fn apply(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
) -> Result<(), ApiError> {
    let (user,kind,date,target,details)=sqlx::query_as::<_,(Uuid,String,String,Option<Uuid>,Value)>("SELECT user_id,kind,starts_on::text,target_user_id,metadata FROM workforce_requests WHERE company_id=$1 AND id=$2 FOR UPDATE")
        .bind(context.company_id).bind(id).fetch_one(&mut **tx).await.map_err(database_error)?;
    if kind == "attendance_correction" {
        let starts = details["corrected_check_in"]
            .as_str()
            .ok_or(ApiError::BadRequest(
                "correction details are missing; resubmit request",
            ))?;
        let ends = details["corrected_check_out"]
            .as_str()
            .ok_or(ApiError::BadRequest(
                "correction details are missing; resubmit request",
            ))?;
        sqlx::query("INSERT INTO attendances(id,company_id,user_id,attendance_date,checked_in_at,checked_out_at) SELECT $1,id,$2,$3::date,($3::date+$4::time) AT TIME ZONE timezone,($3::date+$5::time) AT TIME ZONE timezone FROM companies WHERE id=$6 ON CONFLICT(company_id,user_id,attendance_date) DO UPDATE SET checked_in_at=EXCLUDED.checked_in_at,checked_out_at=EXCLUDED.checked_out_at,updated_at=now()")
            .bind(Uuid::now_v7()).bind(user).bind(&date).bind(starts).bind(ends).bind(context.company_id).execute(&mut **tx).await.map_err(database_error)?;
    }
    if kind == "shift_swap" {
        if details["colleague_consented"] != true {
            return Err(ApiError::Conflict(
                "colleague must consent before approving the swap",
            ));
        }
        let target = target.ok_or(ApiError::BadRequest("swap colleague is missing"))?;
        let source_shift = Uuid::parse_str(details["source_shift_id"].as_str().unwrap_or(""))
            .map_err(|_| ApiError::BadRequest("shift snapshot is missing"))?;
        let target_shift = Uuid::parse_str(details["target_shift_id"].as_str().unwrap_or(""))
            .map_err(|_| ApiError::BadRequest("shift snapshot is missing"))?;
        let rows=sqlx::query_as::<_,(Uuid,Uuid)>("SELECT user_id,shift_id FROM shift_assignments WHERE company_id=$1 AND work_date=$2::date AND user_id IN($3,$4) ORDER BY user_id FOR UPDATE")
            .bind(context.company_id).bind(&date).bind(user).bind(target).fetch_all(&mut **tx).await.map_err(database_error)?;
        if rows.len() != 2
            || !rows.contains(&(user, source_shift))
            || !rows.contains(&(target, target_shift))
        {
            return Err(ApiError::Conflict(
                "schedule changed since the swap request; submit a new request",
            ));
        }
        sqlx::query("UPDATE shift_assignments SET shift_id=CASE WHEN user_id=$3 THEN $5 ELSE $6 END WHERE company_id=$1 AND work_date=$2::date AND user_id IN($3,$4)")
            .bind(context.company_id).bind(&date).bind(user).bind(target).bind(target_shift).bind(source_shift).execute(&mut **tx).await.map_err(database_error)?;
    }
    Ok(())
}

pub async fn invitations(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',r.id,'full_name',u.full_name,'date',r.starts_on::text,'reason',r.reason) FROM workforce_requests r JOIN users u ON u.id=r.user_id WHERE r.company_id=$1 AND r.target_user_id=$2 AND r.kind='shift_swap' AND r.status='pending_manager' AND COALESCE((r.metadata->>'colleague_consented')::bool,false)=false ORDER BY r.created_at")
        .bind(context.company_id).bind(context.user_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Consent {
    accepted: bool,
}
pub async fn consent(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<Consent>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let changed=sqlx::query("UPDATE workforce_requests SET metadata=jsonb_set(metadata,'{colleague_consented}',$4::jsonb),status=CASE WHEN $5 THEN status ELSE 'cancelled' END,updated_at=now() WHERE company_id=$1 AND id=$2 AND target_user_id=$3 AND kind='shift_swap' AND status='pending_manager' AND COALESCE((metadata->>'colleague_consented')::bool,false)=false")
        .bind(context.company_id).bind(id).bind(context.user_id).bind(json!(body.accepted)).bind(body.accepted).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed != 1 {
        return Err(ApiError::Conflict("swap invitation is no longer available"));
    }
    record_request_audit(&mut tx, &context, id, "request.swap_consent").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
