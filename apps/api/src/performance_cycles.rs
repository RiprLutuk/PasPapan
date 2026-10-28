use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Cycle {
    id: Option<Uuid>,
    version: Option<i64>,
    name: String,
    starts_on: String,
    ends_on: String,
    status: String,
}
pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'name',name,'starts_on',starts_on::text,'ends_on',ends_on::text,'status',status,'version',version) FROM performance_cycles WHERE company_id=$1 ORDER BY ends_on DESC,id").bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<Cycle>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PERFORMANCE_MANAGE)?;
    if body.name.trim().is_empty()
        || body.name.len() > 100
        || !is_date(&body.starts_on)
        || !is_date(&body.ends_on)
        || body.ends_on < body.starts_on
        || !matches!(body.status.as_str(), "open" | "closed")
    {
        return Err(ApiError::BadRequest("invalid appraisal cycle"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    if body.id.is_some() {
        let ready:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM performance_goals WHERE company_id=$1 AND cycle_id=$2) AND NOT EXISTS(SELECT 1 FROM performance_goals WHERE company_id=$1 AND cycle_id=$2 AND status<>'approved')").bind(context.company_id).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
        if body.status != "closed" || !ready {
            return Err(ApiError::Conflict(
                "approve every cycle goal before closing",
            ));
        }
        let count=sqlx::query("UPDATE performance_cycles SET status='closed',version=version+1 WHERE company_id=$1 AND id=$2 AND version=$3 AND status='open'").bind(context.company_id).bind(id).bind(body.version).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
        if count != 1 {
            return Err(ApiError::Conflict("appraisal cycle changed or is closed"));
        }
    } else {
        if body.status != "open" {
            return Err(ApiError::BadRequest("create an open cycle first"));
        }
        let count=sqlx::query("INSERT INTO performance_cycles(id,company_id,name,starts_on,ends_on) VALUES($1,$2,$3,$4::date,$5::date) ON CONFLICT(company_id,name) DO NOTHING").bind(id).bind(context.company_id).bind(body.name.trim()).bind(&body.starts_on).bind(&body.ends_on).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
        if count != 1 {
            return Err(ApiError::Conflict("appraisal cycle name already exists"));
        }
    }
    people::audit(
        &mut tx,
        &context,
        id,
        "performance.cycle_saved",
        json!({"status":body.status}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
