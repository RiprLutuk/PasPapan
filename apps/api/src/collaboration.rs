use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct CreateThread {
    title: String,
    kind: String,
    project_id: Option<Uuid>,
    member_ids: Vec<Uuid>,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct PostMessage {
    body: String,
    file_id: Option<Uuid>,
}

async fn context(
    state: &AppState,
    headers: &axum::http::HeaderMap,
    write: bool,
) -> Result<AuthContext, ApiError> {
    if write {
        require_trusted_origin(headers, &state.app_origin)?;
    }
    let context = authenticated_context(&state.pool, headers).await?;
    require_capability(&context, access::COLLABORATION_MANAGE)?;
    Ok(context)
}

pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = context(&state, &headers, false).await?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',t.id,'title',t.title,'kind',t.kind,'project_id',t.project_id,'created_at',t.created_at,'members',COALESCE((SELECT jsonb_agg(jsonb_build_object('id',u.id,'full_name',u.full_name,'role',m.role)) FROM collaboration_thread_members m JOIN users u ON u.id=m.user_id WHERE m.thread_id=t.id),'[]'::jsonb),'messages',COALESCE((SELECT jsonb_agg(message ORDER BY created_at) FROM (SELECT jsonb_build_object('id',x.id,'body',x.body,'created_at',x.created_at,'author_name',u.full_name,'file_id',x.file_id) AS message,x.created_at FROM collaboration_messages x JOIN users u ON u.id=x.author_id WHERE x.thread_id=t.id ORDER BY x.created_at DESC LIMIT 80) latest),'[]'::jsonb)) FROM collaboration_threads t WHERE t.company_id=$1 AND t.archived_at IS NULL ORDER BY t.updated_at DESC LIMIT 100").bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn mine(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',t.id,'title',t.title,'kind',t.kind,'updated_at',t.updated_at,'unread',m.last_read_at IS NULL OR t.updated_at>m.last_read_at,'messages',COALESCE((SELECT jsonb_agg(message ORDER BY created_at) FROM (SELECT jsonb_build_object('id',x.id,'body',x.body,'created_at',x.created_at,'author_name',u.full_name,'file_id',x.file_id) message,x.created_at FROM collaboration_messages x JOIN users u ON u.id=x.author_id WHERE x.thread_id=t.id ORDER BY x.created_at DESC LIMIT 50) latest),'[]'::jsonb)) FROM collaboration_thread_members m JOIN collaboration_threads t ON t.id=m.thread_id JOIN company_memberships cm ON cm.company_id=m.company_id AND cm.user_id=m.user_id WHERE m.company_id=$1 AND m.user_id=$2 AND cm.employment_status='active' AND t.archived_at IS NULL ORDER BY t.updated_at DESC LIMIT 100").bind(context.company_id).bind(context.user_id).fetch_all(&state.pool).await.map_err(database_error)?;
    sqlx::query("UPDATE collaboration_thread_members SET last_read_at=NOW() WHERE company_id=$1 AND user_id=$2").bind(context.company_id).bind(context.user_id).execute(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn create_thread(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<CreateThread>,
) -> Result<Json<Value>, ApiError> {
    let context = context(&state, &headers, true).await?;
    if body.title.trim().is_empty()
        || body.title.chars().count() > 180
        || !matches!(body.kind.as_str(), "group" | "project" | "personal")
        || body.member_ids.len() > 100
    {
        return Err(ApiError::BadRequest("invalid thread details"));
    }
    let mut members = body.member_ids;
    if !members.contains(&context.user_id) {
        members.push(context.user_id);
    }
    members.sort();
    members.dedup();
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    if let Some(project) = body.project_id {
        let valid = sqlx::query_scalar::<_, bool>(
            "SELECT EXISTS(SELECT 1 FROM operations_projects WHERE company_id=$1 AND id=$2)",
        )
        .bind(context.company_id)
        .bind(project)
        .fetch_one(&mut *tx)
        .await
        .map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("project not found"));
        }
    }
    for member in &members {
        let valid=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM company_memberships WHERE company_id=$1 AND user_id=$2 AND employment_status='active')").bind(context.company_id).bind(member).fetch_one(&mut *tx).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("thread member not found"));
        }
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO collaboration_threads(id,company_id,project_id,created_by,kind,title) VALUES($1,$2,$3,$4,$5,$6)").bind(id).bind(context.company_id).bind(body.project_id).bind(context.user_id).bind(&body.kind).bind(body.title.trim()).execute(&mut *tx).await.map_err(database_error)?;
    for member in members {
        sqlx::query("INSERT INTO collaboration_thread_members(thread_id,company_id,user_id,role,last_read_at) VALUES($1,$2,$3,$4,now())").bind(id).bind(context.company_id).bind(member).bind(if member==context.user_id{"owner"}else{"member"}).execute(&mut *tx).await.map_err(database_error)?;
    }
    people::audit(
        &mut tx,
        &context,
        id,
        "collaboration.thread_created",
        json!({"kind":body.kind}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn post_message(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<PostMessage>,
) -> Result<Json<Value>, ApiError> {
    let context = context(&state, &headers, true).await?;
    if body.body.trim().is_empty() || body.body.chars().count() > 5000 {
        return Err(ApiError::BadRequest("invalid message"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let member=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM collaboration_thread_members WHERE thread_id=$1 AND company_id=$2 AND user_id=$3)").bind(id).bind(context.company_id).bind(context.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if !member {
        return Err(ApiError::Forbidden);
    }
    if let Some(file) = body.file_id {
        let valid=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM private_files WHERE id=$1 AND company_id=$2 AND uploaded_by=$3)").bind(file).bind(context.company_id).bind(context.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("attachment not found"));
        }
    }
    let message = Uuid::now_v7();
    sqlx::query("INSERT INTO collaboration_messages(id,company_id,thread_id,author_id,body,file_id) VALUES($1,$2,$3,$4,$5,$6)").bind(message).bind(context.company_id).bind(id).bind(context.user_id).bind(body.body.trim()).bind(body.file_id).execute(&mut *tx).await.map_err(database_error)?;
    sqlx::query("UPDATE collaboration_threads SET updated_at=now() WHERE id=$1 AND company_id=$2")
        .bind(id)
        .bind(context.company_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    sqlx::query("UPDATE collaboration_thread_members SET last_read_at=now() WHERE thread_id=$1 AND user_id=$2").bind(id).bind(context.user_id).execute(&mut *tx).await.map_err(database_error)?;
    people::audit(
        &mut tx,
        &context,
        message,
        "collaboration.message_posted",
        json!({"thread_id":id}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":message})))
}
pub async fn post_my_message(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<PostMessage>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    if body.body.trim().is_empty() || body.body.chars().count() > 5000 {
        return Err(ApiError::BadRequest("invalid message"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let member=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM collaboration_thread_members WHERE thread_id=$1 AND company_id=$2 AND user_id=$3)").bind(id).bind(context.company_id).bind(context.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if !member {
        return Err(ApiError::Forbidden);
    }
    let message = Uuid::now_v7();
    sqlx::query("INSERT INTO collaboration_messages(id,company_id,thread_id,author_id,body,file_id) VALUES($1,$2,$3,$4,$5,$6)").bind(message).bind(context.company_id).bind(id).bind(context.user_id).bind(body.body.trim()).bind(body.file_id).execute(&mut *tx).await.map_err(database_error)?;
    sqlx::query("UPDATE collaboration_threads SET updated_at=NOW() WHERE id=$1")
        .bind(id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    sqlx::query("UPDATE collaboration_thread_members SET last_read_at=NOW() WHERE thread_id=$1 AND user_id=$2").bind(id).bind(context.user_id).execute(&mut *tx).await.map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":message})))
}
