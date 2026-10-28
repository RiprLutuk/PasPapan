use super::*;
use serde::Deserialize;
use serde_json::Value;

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct CreateGoal {
    cycle_id: Option<Uuid>,
    weight: Option<i32>,
    user_id: Uuid,
    title: String,
    unit: String,
    target: i64,
    due_on: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SubmitProgress {
    actual: i64,
    evidence: String,
    version: i64,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct ReviewGoal {
    decision: String,
    note: String,
    version: i64,
}

pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    feed(&state, &context, false).await
}
pub async fn admin_list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PERFORMANCE_MANAGE)?;
    feed(&state, &context, true).await
}
async fn feed(
    state: &AppState,
    context: &AuthContext,
    admin: bool,
) -> Result<Json<Vec<Value>>, ApiError> {
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',g.id,'user_id',g.user_id,'full_name',u.full_name,'title',g.title,'unit',g.unit,'target',g.target,'actual',g.actual,'due_on',g.due_on::text,'status',g.status,'evidence',g.evidence,'review_note',g.review_note,'version',g.version,'cycle_id',g.cycle_id,'weight',g.weight,'score',CASE WHEN g.status='approved' THEN least(100.0,g.actual*100.0/g.target) ELSE NULL END) FROM performance_goals g JOIN users u ON u.id=g.user_id WHERE g.company_id=$1 AND ($3 OR g.user_id=$2) ORDER BY g.due_on,g.id")
        .bind(context.company_id).bind(context.user_id).bind(admin).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn create(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<CreateGoal>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PERFORMANCE_MANAGE)?;
    if body.title.trim().is_empty()
        || body.title.len() > 200
        || body.unit.trim().is_empty()
        || body.unit.len() > 40
        || body.target <= 0
        || body.target > 1_000_000_000
        || !is_date(&body.due_on)
        || body.weight.is_some_and(|v| !(1..=1000).contains(&v))
    {
        return Err(ApiError::BadRequest("invalid performance goal"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    if let Some(cycle) = body.cycle_id {
        let valid:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM performance_cycles WHERE company_id=$1 AND id=$2 AND status='open' AND $3::date BETWEEN starts_on AND ends_on)").bind(context.company_id).bind(cycle).bind(&body.due_on).fetch_one(&mut *tx).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::Conflict(
                "choose an open cycle containing the goal deadline",
            ));
        }
    }
    let valid:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM company_memberships WHERE company_id=$1 AND user_id=$2 AND employment_status='active')").bind(context.company_id).bind(body.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if !valid {
        return Err(ApiError::BadRequest("active employee not found"));
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO performance_goals(id,company_id,user_id,title,unit,target,due_on) VALUES($1,$2,$3,$4,$5,$6,$7::date)")
        .bind(id).bind(context.company_id).bind(body.user_id).bind(body.title.trim()).bind(body.unit.trim()).bind(body.target).bind(&body.due_on).execute(&mut *tx).await.map_err(database_error)?;
    audit(&mut tx, &context, id, "performance.goal_created").await?;
    sqlx::query("UPDATE performance_goals SET cycle_id=$3,weight=$4 WHERE company_id=$1 AND id=$2")
        .bind(context.company_id)
        .bind(id)
        .bind(body.cycle_id)
        .bind(body.weight.unwrap_or(100))
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::CREATED)
}
pub async fn submit(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<SubmitProgress>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    if body.actual < 0
        || body.actual > 1_000_000_000
        || body.evidence.trim().is_empty()
        || body.evidence.len() > 4000
    {
        return Err(ApiError::BadRequest("progress evidence is required"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let changed=sqlx::query("UPDATE performance_goals SET actual=$4,evidence=$5,status='submitted',version=version+1,updated_at=now() WHERE company_id=$1 AND id=$2 AND user_id=$3 AND version=$6 AND status IN ('active','needs_revision')")
        .bind(context.company_id).bind(id).bind(context.user_id).bind(body.actual).bind(body.evidence.trim()).bind(body.version).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::Conflict(
            "only your active goals can be submitted; reload and retry",
        ));
    }
    audit(&mut tx, &context, id, "performance.submitted").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
pub async fn review(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<ReviewGoal>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PERFORMANCE_MANAGE)?;
    if !matches!(body.decision.as_str(), "approved" | "needs_revision")
        || body.note.len() > 4000
        || body.decision == "needs_revision" && body.note.trim().is_empty()
    {
        return Err(ApiError::BadRequest("invalid performance review"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let changed=sqlx::query("UPDATE performance_goals SET status=$4,review_note=$5,reviewed_by=$3,version=version+1,updated_at=now() WHERE company_id=$1 AND id=$2 AND user_id<>$3 AND version=$6 AND status='submitted'")
        .bind(context.company_id).bind(id).bind(context.user_id).bind(&body.decision).bind(body.note.trim()).bind(body.version).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::Conflict(
            "goal is not awaiting your review; self review is prohibited",
        ));
    }
    audit(&mut tx, &context, id, "performance.reviewed").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
async fn audit(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
    event: &str,
) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id) VALUES($1,$2,$3,$4,'performance_goal',$5)").bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(event).bind(id).execute(&mut **tx).await.map_err(database_error)?;
    Ok(())
}
