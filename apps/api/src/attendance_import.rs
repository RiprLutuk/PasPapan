use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Row {
    employee_code: String,
    date: String,
    check_in: String,
    check_out: Option<String>,
    #[serde(default)]
    crosses_midnight: bool,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Import {
    rows: Vec<Row>,
    dry_run: bool,
}
pub async fn import(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<Import>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ATTENDANCES_MANAGE)?;
    if body.rows.is_empty() || body.rows.len() > 200 {
        return Err(ApiError::BadRequest(
            "import between 1 and 200 attendance rows",
        ));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    for row in &body.rows {
        if row.employee_code.trim().is_empty()
            || !is_date(&row.date)
            || !is_time(&row.check_in)
            || row
                .check_out
                .as_ref()
                .is_some_and(|v| !is_time(v) || !row.crosses_midnight && v <= &row.check_in)
            || row.crosses_midnight && row.check_out.is_none()
        {
            return Err(ApiError::BadRequest("invalid attendance import row"));
        }
        let target = sqlx::query_scalar::<_, Uuid>(
            "SELECT user_id FROM employee_profiles WHERE company_id=$1 AND employee_code=$2",
        )
        .bind(context.company_id)
        .bind(row.employee_code.trim())
        .fetch_optional(&mut *tx)
        .await
        .map_err(database_error)?
        .ok_or(ApiError::BadRequest("import employee code not found"))?;
        let historical: bool = sqlx::query_scalar(
            "SELECT $2::date<=(now() AT TIME ZONE timezone)::date FROM companies WHERE id=$1",
        )
        .bind(context.company_id)
        .bind(&row.date)
        .fetch_one(&mut *tx)
        .await
        .map_err(database_error)?;
        if !historical {
            return Err(ApiError::BadRequest("cannot import future attendance"));
        }
        let count=sqlx::query("INSERT INTO attendances(id,company_id,user_id,attendance_date,checked_in_at,checked_out_at) SELECT $1,id,$2,$3::date,($3::date+$4::time) AT TIME ZONE timezone,CASE WHEN $5::text IS NOT NULL THEN ($3::date+$5::time+CASE WHEN $6 THEN interval '1 day' ELSE interval '0' END) AT TIME ZONE timezone END FROM companies WHERE id=$7 ON CONFLICT(company_id,user_id,attendance_date) DO NOTHING")
            .bind(Uuid::now_v7()).bind(target).bind(&row.date).bind(&row.check_in).bind(&row.check_out).bind(row.crosses_midnight).bind(context.company_id).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
        if count != 1 {
            return Err(ApiError::Conflict(
                "attendance already exists; import does not overwrite records",
            ));
        }
    }
    if body.dry_run {
        tx.rollback().await.map_err(database_error)?;
    } else {
        people::audit(
            &mut tx,
            &context,
            context.company_id,
            "attendance.imported",
            json!({"rows":body.rows.len()}),
        )
        .await?;
        tx.commit().await.map_err(database_error)?;
    }
    Ok(Json(json!({"rows":body.rows.len(),"dry_run":body.dry_run})))
}
