use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveProject {
    client_id: Option<Uuid>,
    branch_id: Option<Uuid>,
    manager_user_id: Option<Uuid>,
    name: String,
    #[serde(default)]
    description: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveTask {
    project_id: Uuid,
    assigned_to: Option<Uuid>,
    title: String,
    #[serde(default = "normal_priority")]
    priority: String,
    due_on: Option<String>,
    #[serde(default)]
    checklist: Vec<String>,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct TaskStatus {
    status: String,
    version: i64,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveClient {
    name: String,
    #[serde(default)]
    contact_name: String,
    #[serde(default)]
    contact_phone: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveBranch {
    name: String,
    kind: String,
    #[serde(default)]
    address: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveVisit {
    project_id: Uuid,
    task_id: Option<Uuid>,
    visited_at: String,
    latitude: Option<f64>,
    longitude: Option<f64>,
    accuracy_meters: Option<i32>,
    #[serde(default)]
    address: String,
    #[serde(default)]
    notes: String,
    file_id: Option<Uuid>,
}

fn normal_priority() -> String {
    "normal".to_owned()
}
fn valid_text(value: &str, maximum: usize) -> bool {
    !value.trim().is_empty() && value.chars().count() <= maximum
}
fn valid_timestamp(value: &str) -> bool {
    value.len() >= 20
        && value.len() <= 40
        && value.as_bytes().get(10) == Some(&b'T')
        && value.ends_with('Z')
        && value
            .bytes()
            .all(|byte| byte.is_ascii_digit() || matches!(byte, b'-' | b':' | b'.' | b'T' | b'Z'))
}

async fn member(
    value: Option<Uuid>,
    company: Uuid,
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
) -> Result<(), ApiError> {
    let Some(user) = value else {
        return Ok(());
    };
    let exists=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM company_memberships WHERE company_id=$1 AND user_id=$2 AND employment_status='active')").bind(company).bind(user).fetch_one(&mut **tx).await.map_err(database_error)?;
    if exists {
        Ok(())
    } else {
        Err(ApiError::BadRequest("active company member not found"))
    }
}

async fn reference(
    table: &'static str,
    id: Option<Uuid>,
    company: Uuid,
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
) -> Result<(), ApiError> {
    let Some(id) = id else {
        return Ok(());
    };
    let query = format!("SELECT EXISTS(SELECT 1 FROM {table} WHERE company_id=$1 AND id=$2)");
    let exists = sqlx::query_scalar::<_, bool>(&query)
        .bind(company)
        .bind(id)
        .fetch_one(&mut **tx)
        .await
        .map_err(database_error)?;
    if exists {
        Ok(())
    } else {
        Err(ApiError::BadRequest("operation reference not found"))
    }
}

pub async fn save_client(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveClient>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    if !valid_text(&body.name, 180)
        || body.contact_name.chars().count() > 160
        || body.contact_phone.chars().count() > 60
    {
        return Err(ApiError::BadRequest("invalid client details"));
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO operations_clients(id,company_id,name,contact_name,contact_phone) VALUES($1,$2,$3,$4,$5)").bind(id).bind(context.company_id).bind(body.name.trim()).bind(body.contact_name.trim()).bind(body.contact_phone.trim()).execute(&state.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn save_branch(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveBranch>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    if !valid_text(&body.name, 160)
        || !matches!(
            body.kind.as_str(),
            "branch" | "store" | "office" | "warehouse" | "site"
        )
        || body.address.chars().count() > 1000
    {
        return Err(ApiError::BadRequest("invalid branch details"));
    }
    let id = Uuid::now_v7();
    sqlx::query(
        "INSERT INTO operations_branches(id,company_id,name,kind,address) VALUES($1,$2,$3,$4,$5)",
    )
    .bind(id)
    .bind(context.company_id)
    .bind(body.name.trim())
    .bind(&body.kind)
    .bind(body.address.trim())
    .execute(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',p.id,'code',p.code,'name',p.name,'description',p.description,'status',p.status,'version',p.version,'client_name',c.name,'branch_name',b.name,'manager_name',u.full_name,'tasks',COALESCE((SELECT jsonb_agg(jsonb_build_object('id',t.id,'title',t.title,'priority',t.priority,'due_on',t.due_on::text,'status',t.status,'version',t.version,'assignee_name',au.full_name,'checklist',COALESCE((SELECT jsonb_agg(jsonb_build_object('id',i.id,'label',i.label,'completed',i.completed_at IS NOT NULL) ORDER BY i.position) FROM operations_task_checklist_items i WHERE i.task_id=t.id),'[]'::jsonb)) ORDER BY t.created_at) FROM operations_tasks t LEFT JOIN users au ON au.id=t.assigned_to WHERE t.company_id=p.company_id AND t.project_id=p.id),'[]'::jsonb)) FROM operations_projects p LEFT JOIN operations_clients c ON c.id=p.client_id LEFT JOIN operations_branches b ON b.id=p.branch_id LEFT JOIN users u ON u.id=p.manager_user_id WHERE p.company_id=$1 ORDER BY p.created_at DESC LIMIT 200").bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn save_project(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveProject>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    if body.name.trim().is_empty() || body.name.len() > 180 || body.description.len() > 1500 {
        return Err(ApiError::BadRequest("invalid project details"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    member(body.manager_user_id, context.company_id, &mut tx).await?;
    reference(
        "operations_clients",
        body.client_id,
        context.company_id,
        &mut tx,
    )
    .await?;
    reference(
        "operations_branches",
        body.branch_id,
        context.company_id,
        &mut tx,
    )
    .await?;
    let id = Uuid::now_v7();
    let code = format!("PRJ-{}", id.to_string()[..8].to_uppercase());
    sqlx::query("INSERT INTO operations_projects(id,company_id,client_id,branch_id,manager_user_id,code,name,description) VALUES($1,$2,$3,$4,$5,$6,$7,$8)").bind(id).bind(context.company_id).bind(body.client_id).bind(body.branch_id).bind(body.manager_user_id).bind(&code).bind(body.name.trim()).bind(body.description.trim()).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(
        &mut tx,
        &context,
        id,
        "operations.project_created",
        json!({"code":code}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":id,"code":code})))
}
pub async fn save_task(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveTask>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    if body.title.trim().is_empty()
        || body.title.len() > 180
        || !matches!(body.priority.as_str(), "low" | "normal" | "high")
        || body.checklist.len() > 30
        || body
            .checklist
            .iter()
            .any(|v| v.trim().is_empty() || v.len() > 300)
        || body.due_on.as_ref().is_some_and(|v| !is_date(v))
    {
        return Err(ApiError::BadRequest("invalid task details"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let project: bool = sqlx::query_scalar(
        "SELECT EXISTS(SELECT 1 FROM operations_projects WHERE company_id=$1 AND id=$2)",
    )
    .bind(context.company_id)
    .bind(body.project_id)
    .fetch_one(&mut *tx)
    .await
    .map_err(database_error)?;
    if !project {
        return Err(ApiError::BadRequest("project not found"));
    }
    member(body.assigned_to, context.company_id, &mut tx).await?;
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO operations_tasks(id,company_id,project_id,assigned_to,title,priority,due_on) VALUES($1,$2,$3,$4,$5,$6,$7::text::date)").bind(id).bind(context.company_id).bind(body.project_id).bind(body.assigned_to).bind(body.title.trim()).bind(&body.priority).bind(&body.due_on).execute(&mut *tx).await.map_err(database_error)?;
    for (position, label) in body.checklist.iter().enumerate() {
        sqlx::query("INSERT INTO operations_task_checklist_items(id,company_id,task_id,label,position) VALUES($1,$2,$3,$4,$5)").bind(Uuid::now_v7()).bind(context.company_id).bind(id).bind(label.trim()).bind(position as i32).execute(&mut *tx).await.map_err(database_error)?;
    }
    people::audit(
        &mut tx,
        &context,
        id,
        "operations.task_created",
        json!({"project_id":body.project_id}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn status(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<TaskStatus>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    if !matches!(body.status.as_str(), "todo" | "in_progress" | "done") {
        return Err(ApiError::BadRequest("invalid task status"));
    }
    let changed=sqlx::query("UPDATE operations_tasks SET status=$3,version=version+1,updated_at=now() WHERE company_id=$1 AND id=$2 AND version=$4").bind(context.company_id).bind(id).bind(&body.status).bind(body.version).execute(&state.pool).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::Conflict("task changed; reload before saving"));
    }
    Ok(StatusCode::NO_CONTENT)
}

pub async fn toggle_checklist(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path((task_id, item_id)): Path<(Uuid, Uuid)>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let changed = sqlx::query("UPDATE operations_task_checklist_items i SET completed_at=CASE WHEN completed_at IS NULL THEN now() ELSE NULL END FROM operations_tasks t WHERE i.id=$1 AND i.task_id=$2 AND t.id=i.task_id AND t.company_id=$3 AND i.company_id=$3")
        .bind(item_id).bind(task_id).bind(context.company_id).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::BadRequest("checklist item not found"));
    }
    people::audit(
        &mut tx,
        &context,
        task_id,
        "operations.task_checklist_toggled",
        json!({"item_id":item_id}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn save_visit(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveVisit>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    let coordinates_valid = match (body.latitude, body.longitude) {
        (None, None) => true,
        (Some(latitude), Some(longitude)) => {
            (-90.0..=90.0).contains(&latitude) && (-180.0..=180.0).contains(&longitude)
        }
        _ => false,
    };
    if !coordinates_valid
        || body.accuracy_meters.is_some_and(|value| value < 0)
        || body.address.chars().count() > 1000
        || body.notes.chars().count() > 3000
        || !valid_timestamp(&body.visited_at)
    {
        return Err(ApiError::BadRequest("invalid visit evidence"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    reference(
        "operations_projects",
        Some(body.project_id),
        context.company_id,
        &mut tx,
    )
    .await?;
    if let Some(task_id) = body.task_id {
        let valid = sqlx::query_scalar::<_, bool>("SELECT EXISTS(SELECT 1 FROM operations_tasks WHERE id=$1 AND project_id=$2 AND company_id=$3)")
            .bind(task_id).bind(body.project_id).bind(context.company_id).fetch_one(&mut *tx).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("task does not belong to project"));
        }
    }
    if let Some(file_id) = body.file_id {
        let valid = sqlx::query_scalar::<_, bool>("SELECT EXISTS(SELECT 1 FROM private_files WHERE id=$1 AND company_id=$2 AND uploaded_by=$3)")
            .bind(file_id).bind(context.company_id).bind(context.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("attachment not found"));
        }
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO operations_visit_evidence(id,company_id,project_id,task_id,user_id,visited_at,latitude,longitude,accuracy_meters,address,notes,file_id) VALUES($1,$2,$3,$4,$5,$6::timestamptz,$7,$8,$9,$10,$11,$12)")
        .bind(id).bind(context.company_id).bind(body.project_id).bind(body.task_id).bind(context.user_id).bind(&body.visited_at).bind(body.latitude).bind(body.longitude).bind(body.accuracy_meters).bind(body.address.trim()).bind(body.notes.trim()).bind(body.file_id).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(
        &mut tx,
        &context,
        id,
        "operations.visit_recorded",
        json!({"project_id":body.project_id,"task_id":body.task_id}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}

pub async fn list_visits(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::OPERATIONS_MANAGE)?;
    let rows = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object('id',v.id,'project_id',v.project_id,'project_name',p.name,
         'task_id',v.task_id,'visited_at',v.visited_at,'latitude',v.latitude,'longitude',v.longitude,
         'accuracy_meters',v.accuracy_meters,'address',v.address,'notes',v.notes,
         'user_name',u.full_name,'file_id',v.file_id,'file_name',f.file_name)
         FROM operations_visit_evidence v JOIN operations_projects p ON p.id=v.project_id
         JOIN users u ON u.id=v.user_id LEFT JOIN private_files f ON f.id=v.file_id
         WHERE v.company_id=$1 ORDER BY v.visited_at DESC LIMIT 200",
    ).bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
