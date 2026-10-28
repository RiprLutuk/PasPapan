use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Valuation {
    purchase_date: String,
    purchase_amount: i64,
    salvage_amount: i64,
    useful_life_months: i32,
    version: i64,
}
pub async fn load(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ASSETS_MANAGE)?;
    let row=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('version',version,'purchase_date',purchase_date::text,'purchase_amount',purchase_amount,'salvage_amount',salvage_amount,'useful_life_months',useful_life_months,'book_value',CASE WHEN purchase_date IS NOT NULL THEN greatest(salvage_amount,purchase_amount-floor((purchase_amount-salvage_amount)::numeric/useful_life_months*greatest(0,extract(year FROM age(CURRENT_DATE,purchase_date))*12+extract(month FROM age(CURRENT_DATE,purchase_date))))) END,'as_of',CURRENT_DATE::text) FROM assets WHERE company_id=$1 AND id=$2")
        .bind(context.company_id).bind(id).fetch_optional(&state.pool).await.map_err(database_error)?.ok_or(ApiError::Forbidden)?;
    Ok(Json(row))
}
pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<Valuation>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ASSETS_MANAGE)?;
    if !is_date(&body.purchase_date)
        || !(0..=999999999999).contains(&body.purchase_amount)
        || !(0..=body.purchase_amount).contains(&body.salvage_amount)
        || !(1..=1200).contains(&body.useful_life_months)
    {
        return Err(ApiError::BadRequest("invalid asset valuation"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let changed=sqlx::query("UPDATE assets SET purchase_date=$3::date,purchase_amount=$4,salvage_amount=$5,useful_life_months=$6,version=version+1,updated_at=now() WHERE company_id=$1 AND id=$2 AND version=$7")
        .bind(context.company_id).bind(id).bind(&body.purchase_date).bind(body.purchase_amount).bind(body.salvage_amount).bind(body.useful_life_months).bind(body.version).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed != 1 {
        return Err(ApiError::Conflict("asset valuation changed; reload"));
    }
    people::audit(&mut tx,&context,id,"asset.valuation_saved",json!({"purchase_amount":body.purchase_amount,"useful_life_months":body.useful_life_months})).await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
