use super::*;
use axum::extract::Query;
use serde::Deserialize;
use serde_json::{Value, json};

const DETAIL: &str = "SELECT jsonb_build_object('id',u.id,'full_name',u.full_name,'email',u.email,'role',m.role,'employment_status',m.employment_status,'employee_code',p.employee_code,'manager_user_id',p.manager_user_id,'phone',p.phone,'address',p.address,'team',p.team,'job_title',p.job_title,'started_on',p.started_on,'version',p.version) FROM company_memberships m JOIN users u ON u.id=m.user_id JOIN employee_profiles p ON p.company_id=m.company_id AND p.user_id=m.user_id WHERE m.company_id=$1 AND m.user_id=$2";

pub(super) async fn lock_company(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    company: Uuid,
) -> Result<(), ApiError> {
    sqlx::query("SELECT id FROM companies WHERE id=$1 FOR NO KEY UPDATE")
        .bind(company)
        .execute(&mut **tx)
        .await
        .map_err(database_error)?;
    Ok(())
}

pub(super) async fn audit(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
    event: &str,
    metadata: Value,
) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) VALUES($1,$2,$3,$4,'user',$5,$6)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(event).bind(id).bind(metadata).execute(&mut **tx).await.map_err(database_error)?;
    Ok(())
}

pub(super) async fn validate_manager(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    company: Uuid,
    user: Uuid,
    manager: Option<Uuid>,
) -> Result<(), ApiError> {
    if let Some(manager) = manager {
        if manager == user {
            return Err(ApiError::BadRequest("employee cannot manage themselves"));
        }
        let exists = sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM company_memberships m JOIN users u ON u.id=m.user_id WHERE m.company_id=$1 AND m.user_id=$2 AND m.employment_status='active' AND u.status='active')")
            .bind(company).bind(manager).fetch_one(&mut **tx).await.map_err(database_error)?;
        if !exists {
            return Err(ApiError::BadRequest(
                "active manager not found in this company",
            ));
        }
        // UNION (not UNION ALL) also terminates if preexisting imported data contains a loop.
        let cycle = sqlx::query_scalar::<_,bool>("WITH RECURSIVE chain AS (SELECT user_id,manager_user_id FROM employee_profiles WHERE company_id=$1 AND user_id=$2 UNION SELECT p.user_id,p.manager_user_id FROM employee_profiles p JOIN chain c ON p.user_id=c.manager_user_id WHERE p.company_id=$1) SELECT EXISTS(SELECT 1 FROM chain WHERE user_id=$3)")
            .bind(company).bind(manager).bind(user).fetch_one(&mut **tx).await.map_err(database_error)?;
        if cycle {
            return Err(ApiError::BadRequest(
                "manager assignment creates a reporting cycle",
            ));
        }
    }
    Ok(())
}

#[derive(Deserialize)]
pub struct DirectoryQuery {
    #[serde(default)]
    role: String,
    #[serde(default)]
    q: String,
    #[serde(default)]
    status: String,
    page: Option<i64>,
}

pub async fn directory(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Query(query): Query<DirectoryQuery>,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::EMPLOYEES_VIEW)?;
    let page = query.page.unwrap_or(1);
    if !(1..=100000).contains(&page)
        || query.q.len() > 200
        || !["", "owner", "admin", "manager", "employee"].contains(&query.role.as_str())
        || !["", "active", "inactive", "resigned"].contains(&query.status.as_str())
    {
        return Err(ApiError::BadRequest("invalid employee filter"));
    }
    let filter = " FROM company_memberships m JOIN users u ON u.id=m.user_id JOIN employee_profiles p ON p.company_id=m.company_id AND p.user_id=m.user_id WHERE m.company_id=$1 AND ($2='' OR strpos(lower(u.full_name||' '||u.email||' '||p.employee_code),lower($2))>0) AND ($3='' OR m.employment_status=$3) AND ($4='' OR m.role=$4)";
    let total = sqlx::query_scalar::<_, i64>(&format!("SELECT count(*){filter}"))
        .bind(context.company_id)
        .bind(query.q.trim())
        .bind(&query.status)
        .bind(&query.role)
        .fetch_one(&state.pool)
        .await
        .map_err(database_error)?;
    let items = sqlx::query_scalar::<_,Value>(&format!("SELECT jsonb_build_object('id',u.id,'full_name',u.full_name,'email',u.email,'role',m.role,'employee_code',p.employee_code,'employment_status',m.employment_status,'team',p.team,'job_title',p.job_title){filter} ORDER BY lower(u.full_name),u.id LIMIT 50 OFFSET $5"))
        .bind(context.company_id).bind(query.q.trim()).bind(&query.status).bind(&query.role).bind((page-1)*50).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"items":items,"total":total,"page":page,"page_size":50}),
    ))
}

pub async fn detail(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::EMPLOYEES_VIEW)?;
    let value = sqlx::query_scalar::<_, Value>(DETAIL)
        .bind(context.company_id)
        .bind(id)
        .fetch_optional(&state.pool)
        .await
        .map_err(database_error)?
        .ok_or(ApiError::BadRequest("employee not found"))?;
    Ok(Json(value))
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct EditInput {
    full_name: String,
    employee_code: String,
    manager_user_id: Option<Uuid>,
    phone: String,
    address: String,
    team: String,
    job_title: String,
    started_on: Option<String>,
    version: i64,
}

pub async fn update(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<EditInput>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::EMPLOYEES_MANAGE)?;
    if body.full_name.trim().is_empty()
        || body.full_name.len() > 200
        || body.employee_code.trim().is_empty()
        || body.employee_code.len() > 80
        || body.phone.len() > 64
        || body.address.len() > 500
        || body.team.len() > 100
        || body.job_title.len() > 100
        || body.started_on.as_ref().is_some_and(|v| !is_date(v))
    {
        return Err(ApiError::BadRequest("invalid employee details"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    lock_company(&mut tx, context.company_id).await?;
    let before = sqlx::query_scalar::<_, Value>(DETAIL)
        .bind(context.company_id)
        .bind(id)
        .fetch_optional(&mut *tx)
        .await
        .map_err(database_error)?
        .ok_or(ApiError::BadRequest("employee not found"))?;
    if before["role"] == "owner" {
        return Err(ApiError::Forbidden);
    }
    if before["version"].as_i64() != Some(body.version) {
        return Err(ApiError::Conflict("employee changed; reload before saving"));
    }
    validate_manager(&mut tx, context.company_id, id, body.manager_user_id).await?;
    let name =
        sqlx::query_scalar::<_, String>("SELECT full_name FROM users WHERE id=$1 FOR UPDATE")
            .bind(id)
            .fetch_one(&mut *tx)
            .await
            .map_err(database_error)?;
    if name != body.full_name.trim() {
        let memberships = sqlx::query_scalar::<_, i64>(
            "SELECT count(*) FROM company_memberships WHERE user_id=$1",
        )
        .bind(id)
        .fetch_one(&mut *tx)
        .await
        .map_err(database_error)?;
        if memberships > 1 {
            return Err(ApiError::Conflict(
                "shared account identity must be changed by its owner",
            ));
        }
        sqlx::query("UPDATE users SET full_name=$2,updated_at=now() WHERE id=$1")
            .bind(id)
            .bind(body.full_name.trim())
            .execute(&mut *tx)
            .await
            .map_err(database_error)?;
    }
    let duplicate = sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM employee_profiles WHERE company_id=$1 AND employee_code=$2 AND user_id<>$3)").bind(context.company_id).bind(body.employee_code.trim()).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if duplicate {
        return Err(ApiError::Conflict("employee code already exists"));
    }
    sqlx::query("UPDATE employee_profiles SET employee_code=$3,manager_user_id=$4,phone=$5,address=$6,team=$7,job_title=$8,started_on=$9::text::date,version=version+1,updated_at=now() WHERE company_id=$1 AND user_id=$2")
        .bind(context.company_id).bind(id).bind(body.employee_code.trim()).bind(body.manager_user_id).bind(body.phone.trim()).bind(body.address.trim()).bind(body.team.trim()).bind(body.job_title.trim()).bind(&body.started_on).execute(&mut *tx).await.map_err(database_error)?;
    let after = sqlx::query_scalar::<_, Value>(DETAIL)
        .bind(context.company_id)
        .bind(id)
        .fetch_one(&mut *tx)
        .await
        .map_err(database_error)?;
    audit(
        &mut tx,
        &context,
        id,
        "employee.updated",
        json!({"before":before,"after":after}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct StatusInput {
    status: String,
    version: i64,
}

pub async fn status(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<StatusInput>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::EMPLOYEES_STATUS)?;
    if !["active", "inactive", "resigned"].contains(&body.status.as_str()) {
        return Err(ApiError::BadRequest("invalid employment status"));
    }
    if id == context.user_id {
        return Err(ApiError::BadRequest(
            "cannot change your own employment status",
        ));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    lock_company(&mut tx, context.company_id).await?;
    let before = sqlx::query_scalar::<_, Value>(DETAIL)
        .bind(context.company_id)
        .bind(id)
        .fetch_optional(&mut *tx)
        .await
        .map_err(database_error)?
        .ok_or(ApiError::BadRequest("employee not found"))?;
    if before["role"] == "owner" {
        return Err(ApiError::Forbidden);
    }
    if before["version"].as_i64() != Some(body.version) {
        return Err(ApiError::Conflict("employee changed; reload before saving"));
    }
    if before["employment_status"] == body.status {
        return Ok(StatusCode::NO_CONTENT);
    }
    sqlx::query("UPDATE company_memberships SET employment_status=$3,access_revoked_at=CASE WHEN $3<>'active' THEN clock_timestamp() ELSE access_revoked_at END,updated_at=now() WHERE company_id=$1 AND user_id=$2")
        .bind(context.company_id).bind(id).bind(&body.status).execute(&mut *tx).await.map_err(database_error)?;
    sqlx::query("UPDATE employee_profiles SET version=version+1,updated_at=now() WHERE company_id=$1 AND user_id=$2").bind(context.company_id).bind(id).execute(&mut *tx).await.map_err(database_error)?;
    audit(
        &mut tx,
        &context,
        id,
        "employee.status_changed",
        json!({"before":before["employment_status"],"after":body.status}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
