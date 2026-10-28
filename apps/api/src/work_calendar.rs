use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Calendar {
    weekdays: Vec<i32>,
    exclude_holidays: bool,
    version: Option<i64>,
}
pub async fn load(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ORGANIZATION_MANAGE)?;
    let result=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('weekdays',weekdays,'exclude_holidays',exclude_holidays,'version',version) FROM company_work_calendars WHERE company_id=$1").bind(context.company_id).fetch_optional(&state.pool).await.map_err(database_error)?;
    Ok(Json(result.unwrap_or(
        json!({"weekdays":[1,2,3,4,5,6,7],"exclude_holidays":false,"version":null}),
    )))
}
pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(mut body): Json<Calendar>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ORGANIZATION_MANAGE)?;
    body.weekdays.sort();
    body.weekdays.dedup();
    if body.weekdays.is_empty() || body.weekdays.iter().any(|v| !(1..=7).contains(v)) {
        return Err(ApiError::BadRequest("choose valid working weekdays"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let version = sqlx::query_scalar::<_, i64>(
        "SELECT version FROM company_work_calendars WHERE company_id=$1",
    )
    .bind(context.company_id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?;
    if version != body.version {
        return Err(ApiError::Conflict("working calendar changed; reload"));
    }
    sqlx::query("INSERT INTO company_work_calendars(company_id,weekdays,exclude_holidays) VALUES($1,$2,$3) ON CONFLICT(company_id) DO UPDATE SET weekdays=EXCLUDED.weekdays,exclude_holidays=EXCLUDED.exclude_holidays,version=company_work_calendars.version+1")
        .bind(context.company_id).bind(&body.weekdays).bind(body.exclude_holidays).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(
        &mut tx,
        &context,
        context.company_id,
        "leave.calendar_saved",
        json!({"weekdays":body.weekdays,"exclude_holidays":body.exclude_holidays}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
