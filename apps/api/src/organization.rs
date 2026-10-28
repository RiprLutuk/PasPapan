use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveEntitlement {
    user_id: Uuid,
    leave_type_id: Uuid,
    year: i32,
    days: i32,
    carryover_days: Option<i32>,
    version: Option<i64>,
}

pub async fn entitlements(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ORGANIZATION_MANAGE)?;
    let rows = sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('user_id',e.user_id,'leave_type_id',e.leave_type_id,'full_name',u.full_name,'leave_type_name',r.name,'year',e.year,'days',e.days,'carryover_days',e.carryover_days,'version',e.version) FROM employee_leave_entitlements e JOIN users u ON u.id=e.user_id JOIN organization_reference_data r ON r.company_id=e.company_id AND r.id=e.leave_type_id WHERE e.company_id=$1 ORDER BY e.year DESC,u.full_name,r.name")
        .bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}

pub async fn save_entitlement(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveEntitlement>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ORGANIZATION_MANAGE)?;
    let carryover = body.carryover_days.unwrap_or(0);
    if !(2000..=2200).contains(&body.year)
        || !(0..=366).contains(&body.days)
        || !(0..=366).contains(&carryover)
        || body.days + carryover > 366
    {
        return Err(ApiError::BadRequest("invalid leave entitlement"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let valid:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM company_memberships m CROSS JOIN organization_reference_data r WHERE m.company_id=$1 AND m.user_id=$2 AND r.company_id=m.company_id AND r.id=$3 AND r.kind='leave_type' AND r.active)")
        .bind(context.company_id).bind(body.user_id).bind(body.leave_type_id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if !valid {
        return Err(ApiError::BadRequest(
            "employee or active leave type not found",
        ));
    }
    let reserved:i64=sqlx::query_scalar("SELECT COALESCE(sum(charged_leave_days),0)::bigint FROM workforce_requests WHERE company_id=$1 AND user_id=$2 AND leave_type_id=$3 AND extract(year FROM starts_on)::int=$4 AND status IN ('pending_manager','pending_admin','approved')").bind(context.company_id).bind(body.user_id).bind(body.leave_type_id).bind(body.year).fetch_one(&mut *tx).await.map_err(database_error)?;
    if reserved > i64::from(body.days + carryover) {
        return Err(ApiError::Conflict(
            "entitlement cannot be lower than reserved leave",
        ));
    }
    let changed=sqlx::query("INSERT INTO employee_leave_entitlements(company_id,user_id,leave_type_id,year,days,carryover_days) VALUES($1,$2,$3,$4,$5,$7) ON CONFLICT(company_id,user_id,leave_type_id,year) DO UPDATE SET days=EXCLUDED.days,carryover_days=EXCLUDED.carryover_days,version=employee_leave_entitlements.version+1 WHERE employee_leave_entitlements.version=$6")
        .bind(context.company_id).bind(body.user_id).bind(body.leave_type_id).bind(body.year).bind(body.days).bind(body.version).bind(carryover).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::Conflict(
            "entitlement was changed; reload and retry",
        ));
    }
    people::audit(
        &mut tx,
        &context,
        body.user_id,
        "leave.entitlement_saved",
        json!({"leave_type_id":body.leave_type_id,"year":body.year,"days":body.days,"carryover_days":carryover}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn leave_balances(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows=sqlx::query_scalar::<_,Value>(
        "SELECT jsonb_build_object('id',r.id,'name',r.name,'year',extract(year FROM CURRENT_DATE)::int,'days',COALESCE(e.days+e.carryover_days,r.annual_days),
            'reserved_days',COALESCE((SELECT sum(q.charged_leave_days) FROM workforce_requests q WHERE q.company_id=r.company_id AND q.user_id=$2 AND q.leave_type_id=r.id AND q.status IN ('pending_manager','pending_admin','approved') AND extract(year FROM q.starts_on)=extract(year FROM CURRENT_DATE)),0))
         FROM organization_reference_data r LEFT JOIN employee_leave_entitlements e ON e.company_id=r.company_id AND e.leave_type_id=r.id AND e.user_id=$2 AND e.year=extract(year FROM CURRENT_DATE)::int
         WHERE r.company_id=$1 AND r.kind='leave_type' AND r.active ORDER BY r.name",
    ).bind(context.company_id).bind(context.user_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveReference {
    id: Option<Uuid>,
    version: Option<i64>,
    kind: String,
    name: String,
    active: bool,
    holiday_date: Option<String>,
    annual_days: Option<i32>,
}

fn validate(body: &SaveReference) -> Result<(), ApiError> {
    if !matches!(
        body.kind.as_str(),
        "division" | "job_title" | "education" | "leave_type" | "holiday"
    ) || body.name.trim().is_empty()
        || body.name.len() > 100
        || (body.kind == "holiday") != body.holiday_date.is_some()
        || body
            .holiday_date
            .as_ref()
            .is_some_and(|date| !is_date(date))
        || (body.kind == "leave_type") != body.annual_days.is_some()
        || body
            .annual_days
            .is_some_and(|days| !(0..=366).contains(&days))
    {
        return Err(ApiError::BadRequest(
            "invalid organization reference details",
        ));
    }
    Ok(())
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
    require_capability(&context, access::ORGANIZATION_MANAGE)?;
    feed(&state, &context, true).await
}
async fn feed(
    state: &AppState,
    context: &AuthContext,
    admin: bool,
) -> Result<Json<Vec<Value>>, ApiError> {
    let rows = sqlx::query_scalar::<_, Value>("SELECT jsonb_build_object('id',id,'kind',kind,'name',name,'active',active,'holiday_date',holiday_date::text,'annual_days',annual_days,'version',version) FROM organization_reference_data WHERE company_id=$1 AND ($2 OR active) ORDER BY kind,holiday_date,name")
        .bind(context.company_id).bind(admin).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveReference>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ORGANIZATION_MANAGE)?;
    validate(&body)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    let old_name = sqlx::query_scalar::<_, String>(
        "SELECT name FROM organization_reference_data WHERE company_id=$1 AND id=$2 AND kind=$3",
    )
    .bind(context.company_id)
    .bind(id)
    .bind(&body.kind)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?;
    let duplicate: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM organization_reference_data WHERE company_id=$1 AND kind=$2 AND name=$3 AND id <> $4)")
        .bind(context.company_id).bind(&body.kind).bind(body.name.trim()).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if duplicate {
        return Err(ApiError::Conflict("reference name already exists"));
    }
    if body.id.is_some() {
        let affected = sqlx::query("UPDATE organization_reference_data SET name=$4,active=$5,holiday_date=$6::text::date,annual_days=$7,version=version+1,updated_at=now() WHERE company_id=$1 AND id=$2 AND kind=$3 AND version=$8")
            .bind(context.company_id).bind(id).bind(&body.kind).bind(body.name.trim()).bind(body.active).bind(&body.holiday_date).bind(body.annual_days).bind(body.version)
            .execute(&mut *tx).await.map_err(database_error)?.rows_affected();
        if affected == 0 {
            return Err(ApiError::Conflict(
                "reference was changed; reload and retry",
            ));
        }
    } else {
        sqlx::query("INSERT INTO organization_reference_data(id,company_id,kind,name,active,holiday_date,annual_days) VALUES($1,$2,$3,$4,$5,$6::text::date,$7)")
            .bind(id).bind(context.company_id).bind(&body.kind).bind(body.name.trim()).bind(body.active).bind(&body.holiday_date).bind(body.annual_days)
            .execute(&mut *tx).await.map_err(database_error)?;
    }
    if body.kind == "division" {
        sqlx::query("UPDATE employee_profiles SET team=$3,version=version+1,updated_at=now() WHERE company_id=$1 AND division_id=$2 AND team IS DISTINCT FROM $3").bind(context.company_id).bind(id).bind(body.name.trim()).execute(&mut *tx).await.map_err(database_error)?;
        if let Some(previous) = old_name {
            sqlx::query("UPDATE announcements SET audience_team=$3 WHERE company_id=$1 AND audience_team=$2").bind(context.company_id).bind(previous).bind(body.name.trim()).execute(&mut *tx).await.map_err(database_error)?;
        }
    } else if body.kind == "job_title" {
        sqlx::query("UPDATE employee_profiles SET job_title=$3,version=version+1,updated_at=now() WHERE company_id=$1 AND job_title_id=$2 AND job_title IS DISTINCT FROM $3").bind(context.company_id).bind(id).bind(body.name.trim()).execute(&mut *tx).await.map_err(database_error)?;
    }
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) VALUES($1,$2,$3,'organization.reference_saved','organization_reference',$4,$5)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(id).bind(json!({"kind":body.kind,"active":body.active}))
        .execute(&mut *tx).await.map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}

#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn reference_fields_follow_their_domain() {
        let mut body = SaveReference {
            id: None,
            version: None,
            kind: "division".into(),
            name: "Engineering".into(),
            active: true,
            holiday_date: None,
            annual_days: None,
        };
        assert!(validate(&body).is_ok());
        body.kind = "holiday".into();
        assert!(validate(&body).is_err());
        body.holiday_date = Some("2026-01-01".into());
        assert!(validate(&body).is_ok());
        body.kind = "leave_type".into();
        body.holiday_date = None;
        body.annual_days = Some(12);
        assert!(validate(&body).is_ok());
        body.annual_days = Some(-1);
        assert!(validate(&body).is_err());
    }
}
