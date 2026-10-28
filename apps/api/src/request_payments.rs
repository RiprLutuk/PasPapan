use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct RecordPayment {
    kind: String,
    amount_cents: i64,
    reference: String,
}

pub async fn approved(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',q.id,'user_id',q.user_id,'full_name',u.full_name,'kind',q.kind,'amount_cents',q.amount_cents,'reason',q.reason) FROM workforce_requests q JOIN users u ON u.id=q.user_id WHERE q.company_id=$1 AND q.status='approved' AND q.kind IN ('cash_advance','reimbursement') ORDER BY q.created_at DESC LIMIT 200").bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}

pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let manages = context
        .capabilities
        .iter()
        .any(|v| v == access::PAYROLL_MANAGE);
    let valid:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM workforce_requests WHERE company_id=$1 AND id=$2 AND (user_id=$3 OR $4) AND kind IN ('cash_advance','reimbursement'))").bind(context.company_id).bind(id).bind(context.user_id).bind(manages).fetch_one(&state.pool).await.map_err(database_error)?;
    if !valid {
        return Err(ApiError::Forbidden);
    }
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'kind',kind,'amount_cents',amount_cents,'reference',reference,'recorded_at',recorded_at) FROM request_payments WHERE company_id=$1 AND request_id=$2 ORDER BY recorded_at,id").bind(context.company_id).bind(id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(json!(rows)))
}
pub async fn record(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<RecordPayment>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    if !matches!(body.kind.as_str(), "disbursement" | "repayment")
        || !(1..=99_999_999_999).contains(&body.amount_cents)
        || !(3..=200).contains(&body.reference.trim().len())
    {
        return Err(ApiError::BadRequest("invalid payment details"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let (owner,kind,amount)=sqlx::query_as::<_,(Uuid,String,i64)>("SELECT user_id,kind,amount_cents FROM workforce_requests WHERE company_id=$1 AND id=$2 AND status='approved' AND kind IN ('cash_advance','reimbursement') FOR UPDATE")
        .bind(context.company_id).bind(id).fetch_optional(&mut *tx).await.map_err(database_error)?.ok_or(ApiError::Conflict("approved financial request not found"))?;
    if owner == context.user_id {
        return Err(ApiError::Forbidden);
    }
    let (paid,repaid)=sqlx::query_as::<_,(i64,i64)>("SELECT COALESCE(sum(amount_cents) FILTER(WHERE kind='disbursement'),0)::bigint,COALESCE(sum(amount_cents) FILTER(WHERE kind='repayment'),0)::bigint FROM request_payments WHERE company_id=$1 AND request_id=$2")
        .bind(context.company_id).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if body.kind == "disbursement" && body.amount_cents > amount - paid
        || body.kind == "repayment" && (kind != "cash_advance" || body.amount_cents > paid - repaid)
    {
        return Err(ApiError::Conflict("payment exceeds the outstanding amount"));
    }
    let changed=sqlx::query("INSERT INTO request_payments(id,company_id,request_id,actor_id,kind,amount_cents,reference) VALUES($1,$2,$3,$4,$5,$6,$7) ON CONFLICT(company_id,reference) DO NOTHING")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(id).bind(context.user_id).bind(&body.kind).bind(body.amount_cents).bind(body.reference.trim()).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed != 1 {
        return Err(ApiError::Conflict("payment reference already recorded"));
    }
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) VALUES($1,$2,$3,'request.payment_recorded','workforce_request',$4,$5)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(id).bind(json!({"kind":body.kind,"amount_cents":body.amount_cents,"reference":body.reference})).execute(&mut *tx).await.map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::CREATED)
}
