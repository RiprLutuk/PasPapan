use super::*;
use serde::Deserialize;
use serde_json::Value;
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct CreateChecklist {
    user_id: Uuid,
    title: String,
    kind: String,
    items: Vec<String>,
    template_id: Option<Uuid>,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct ToggleItem {
    completed: bool,
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
    require_capability(&context, access::CHECKLISTS_MANAGE)?;
    feed(&state, &context, true).await
}
async fn feed(
    state: &AppState,
    context: &AuthContext,
    admin: bool,
) -> Result<Json<Vec<Value>>, ApiError> {
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',c.id,'user_id',c.user_id,'full_name',u.full_name,'title',c.title,'kind',c.kind,'completed_at',c.completed_at::text,'items',COALESCE((SELECT jsonb_agg(jsonb_build_object('id',i.id,'label',i.label,'completed',i.completed_at IS NOT NULL) ORDER BY i.position) FROM hr_checklist_items i WHERE i.company_id=c.company_id AND i.checklist_id=c.id),'[]'::jsonb)) FROM hr_checklists c JOIN users u ON u.id=c.user_id WHERE c.company_id=$1 AND ($3 OR c.user_id=$2) ORDER BY c.created_at DESC LIMIT 200")
        .bind(context.company_id).bind(context.user_id).bind(admin).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn create(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(mut body): Json<CreateChecklist>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::CHECKLISTS_MANAGE)?;
    if let Some(template_id) = body.template_id {
        let (kind,items)=sqlx::query_as::<_,(String,Vec<String>)>("SELECT kind,items FROM hr_checklist_templates WHERE company_id=$1 AND id=$2 AND active")
            .bind(context.company_id).bind(template_id).fetch_optional(&state.pool).await.map_err(database_error)?.ok_or(ApiError::BadRequest("active checklist template not found"))?;
        body.kind = kind;
        body.items = items;
    }
    if body.title.trim().is_empty()
        || body.title.len() > 200
        || !matches!(body.kind.as_str(), "onboarding" | "offboarding")
        || body.items.is_empty()
        || body.items.len() > 30
        || body
            .items
            .iter()
            .any(|item| item.trim().is_empty() || item.len() > 300)
    {
        return Err(ApiError::BadRequest("invalid HR checklist"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let member: bool = sqlx::query_scalar(
        "SELECT EXISTS(SELECT 1 FROM company_memberships WHERE company_id=$1 AND user_id=$2)",
    )
    .bind(context.company_id)
    .bind(body.user_id)
    .fetch_one(&mut *tx)
    .await
    .map_err(database_error)?;
    if !member {
        return Err(ApiError::BadRequest("employee not found"));
    }
    let id = Uuid::now_v7();
    sqlx::query(
        "INSERT INTO hr_checklists(id,company_id,user_id,title,kind) VALUES($1,$2,$3,$4,$5)",
    )
    .bind(id)
    .bind(context.company_id)
    .bind(body.user_id)
    .bind(body.title.trim())
    .bind(&body.kind)
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;
    sqlx::query("UPDATE hr_checklists SET template_id=$3 WHERE company_id=$1 AND id=$2")
        .bind(context.company_id)
        .bind(id)
        .bind(body.template_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    for (index, label) in body.items.iter().enumerate() {
        sqlx::query("INSERT INTO hr_checklist_items(id,company_id,checklist_id,label,position) VALUES($1,$2,$3,$4,$5)").bind(Uuid::now_v7()).bind(context.company_id).bind(id).bind(label.trim()).bind(index as i32).execute(&mut *tx).await.map_err(database_error)?;
    }
    audit(&mut tx, &context, id, "checklist.created").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::CREATED)
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveTemplate {
    id: Option<Uuid>,
    version: Option<i64>,
    title: String,
    kind: String,
    items: Vec<String>,
    active: bool,
}
pub async fn templates(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::CHECKLISTS_MANAGE)?;
    Ok(Json(sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'title',title,'kind',kind,'items',items,'active',active,'version',version) FROM hr_checklist_templates WHERE company_id=$1 ORDER BY title,id")
        .bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?))
}
pub async fn save_template(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveTemplate>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::CHECKLISTS_MANAGE)?;
    if body.title.trim().is_empty()
        || body.title.len() > 200
        || !matches!(body.kind.as_str(), "onboarding" | "offboarding")
        || body.items.is_empty()
        || body.items.len() > 30
        || body
            .items
            .iter()
            .any(|label| label.trim().is_empty() || label.len() > 300)
    {
        return Err(ApiError::BadRequest("invalid checklist template"));
    }
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    if body.id.is_some() {
        let affected=sqlx::query("UPDATE hr_checklist_templates SET title=$3,kind=$4,items=$5,active=$6,version=version+1 WHERE company_id=$1 AND id=$2 AND version=$7")
        .bind(context.company_id).bind(id).bind(body.title.trim()).bind(&body.kind).bind(&body.items).bind(body.active).bind(body.version).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
        if affected != 1 {
            return Err(ApiError::Conflict("template changed; reload and retry"));
        }
    } else {
        sqlx::query("INSERT INTO hr_checklist_templates(id,company_id,title,kind,items,active) VALUES($1,$2,$3,$4,$5,$6)")
        .bind(id).bind(context.company_id).bind(body.title.trim()).bind(&body.kind).bind(&body.items).bind(body.active).execute(&mut *tx).await.map_err(database_error)?;
    }
    audit(&mut tx, &context, id, "checklist.template_saved").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
pub async fn toggle(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path((id, item_id)): Path<(Uuid, Uuid)>,
    Json(body): Json<ToggleItem>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let row=sqlx::query_as::<_,(Uuid,bool)>("SELECT user_id,completed_at IS NOT NULL FROM hr_checklists WHERE company_id=$1 AND id=$2 FOR UPDATE").bind(context.company_id).bind(id).fetch_optional(&mut *tx).await.map_err(database_error)?.ok_or(ApiError::BadRequest("checklist not found"))?;
    if row.0 != context.user_id
        && !context
            .capabilities
            .iter()
            .any(|p| p == access::CHECKLISTS_MANAGE)
    {
        return Err(ApiError::Forbidden);
    }
    if row.1 {
        return Err(ApiError::Conflict("completed checklists are locked"));
    }
    let changed=sqlx::query("UPDATE hr_checklist_items SET completed_at=CASE WHEN $4 THEN COALESCE(completed_at,now()) ELSE NULL END WHERE company_id=$1 AND checklist_id=$2 AND id=$3")
        .bind(context.company_id).bind(id).bind(item_id).bind(body.completed).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::BadRequest("checklist item not found"));
    }
    audit(&mut tx, &context, id, "checklist.item_updated").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
pub async fn complete(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::CHECKLISTS_MANAGE)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    // Use the company lock to serialize offboarding with asset assignments.
    people::lock_company(&mut tx, context.company_id).await?;
    let row=sqlx::query_as::<_,(Uuid,String)>("SELECT user_id,kind FROM hr_checklists WHERE company_id=$1 AND id=$2 AND completed_at IS NULL FOR UPDATE").bind(context.company_id).bind(id).fetch_optional(&mut *tx).await.map_err(database_error)?.ok_or(ApiError::Conflict("checklist is already closed or unavailable"))?;
    let pending:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM hr_checklist_items WHERE company_id=$1 AND checklist_id=$2 AND completed_at IS NULL)").bind(context.company_id).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if pending {
        return Err(ApiError::BadRequest("finish all checklist items first"));
    }
    if row.1 == "offboarding" {
        let assigned:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM asset_assignments WHERE company_id=$1 AND user_id=$2 AND returned_at IS NULL)").bind(context.company_id).bind(row.0).fetch_one(&mut *tx).await.map_err(database_error)?;
        if assigned {
            return Err(ApiError::Conflict(
                "return all assigned assets before completing offboarding",
            ));
        }
    }
    sqlx::query(
        "UPDATE hr_checklists SET completed_at=now(),completed_by=$3 WHERE company_id=$1 AND id=$2",
    )
    .bind(context.company_id)
    .bind(id)
    .bind(context.user_id)
    .execute(&mut *tx)
    .await
    .map_err(database_error)?;
    audit(&mut tx, &context, id, "checklist.completed").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
async fn audit(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
    event: &str,
) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id) VALUES($1,$2,$3,$4,'hr_checklist',$5)").bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(event).bind(id).execute(&mut **tx).await.map_err(database_error)?;
    Ok(())
}
