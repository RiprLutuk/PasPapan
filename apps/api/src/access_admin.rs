use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

pub async fn delete_role(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ACCESS_MANAGE)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    lock_company(&mut tx, context.company_id).await?;
    let before = sqlx::query_scalar::<_, Value>(
        "SELECT to_jsonb(r) FROM access_roles r WHERE company_id=$1 AND id=$2",
    )
    .bind(context.company_id)
    .bind(id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("role not found"))?;
    let assigned = sqlx::query_scalar::<_, bool>(
        "SELECT EXISTS(SELECT 1 FROM member_access WHERE company_id=$1 AND role_id=$2)",
    )
    .bind(context.company_id)
    .bind(id)
    .fetch_one(&mut *tx)
    .await
    .map_err(database_error)?;
    if assigned {
        return Err(ApiError::Conflict(
            "role is still assigned; reassign members first",
        ));
    }
    sqlx::query("DELETE FROM access_roles WHERE company_id=$1 AND id=$2")
        .bind(context.company_id)
        .bind(id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    audit(
        &mut tx,
        &context,
        id,
        "access.role.deleted",
        before,
        Value::Null,
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct RoleInput {
    id: Option<Uuid>,
    name: String,
    permissions: Vec<String>,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct MemberInput {
    role_id: Option<Uuid>,
    grants: Vec<String>,
    denies: Vec<String>,
}

fn validate_permissions(values: &[String]) -> Result<(), ApiError> {
    if values.len() > access::CATALOG.len()
        || values
            .iter()
            .any(|p| !access::CATALOG.contains(&p.as_str()))
    {
        return Err(ApiError::BadRequest("unknown or non-delegable permission"));
    }
    Ok(())
}

pub async fn overview(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ACCESS_MANAGE)?;
    let roles = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object('id',id,'name',name,'permissions',permissions) FROM access_roles WHERE company_id=$1 ORDER BY lower(name),id"
    ).bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    let rows = sqlx::query_as::<_, (Uuid, String, String, Option<Uuid>, Option<Vec<String>>, Vec<String>, Vec<String>)>(
        "SELECT m.user_id,u.full_name,m.role,a.role_id,r.permissions,COALESCE(a.grants,'{}'),COALESCE(a.denies,'{}')
        FROM company_memberships m JOIN users u ON u.id=m.user_id
        LEFT JOIN member_access a ON a.company_id=m.company_id AND a.user_id=m.user_id
        LEFT JOIN access_roles r ON r.company_id=a.company_id AND r.id=a.role_id
        WHERE m.company_id=$1 ORDER BY u.full_name,m.user_id"
    ).bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    let members: Vec<Value> = rows.into_iter().map(|(id,name,base,role_id,custom,grants,denies)| {
        let effective = access::effective(&base,custom.as_deref(),&grants,&denies);
        json!({"id":id,"name":name,"base_role":base,"role_id":role_id,"grants":grants,"denies":denies,"effective":effective,"protected":base=="owner"})
    }).collect();
    let events = sqlx::query_scalar::<_,Value>(
        "SELECT jsonb_build_object('id',e.id,'action',e.event_type,'actor',COALESCE(u.full_name,'Akun dihapus'),'subject_id',e.subject_id,'occurred_at',e.occurred_at,'changes',e.metadata)
        FROM audit_events e LEFT JOIN users u ON u.id=e.actor_id
        WHERE e.company_id=$1 AND e.event_type LIKE 'access.%' ORDER BY e.occurred_at DESC,e.id DESC LIMIT 50"
    ).bind(context.company_id).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"catalog":access::CATALOG,"roles":roles,"members":members,"events":events}),
    ))
}

// Serialize access changes per company. Audit and policy changes commit together.
async fn lock_company(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    company: Uuid,
) -> Result<(), ApiError> {
    sqlx::query("SELECT id FROM companies WHERE id=$1 FOR UPDATE")
        .bind(company)
        .execute(&mut **tx)
        .await
        .map_err(database_error)?;
    Ok(())
}

async fn audit(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    subject: Uuid,
    kind: &str,
    before: Value,
    after: Value,
) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) VALUES($1,$2,$3,$4,'access',$5,$6)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(kind).bind(subject)
        .bind(json!({"before":before,"after":after})).execute(&mut **tx).await.map_err(database_error)?;
    Ok(())
}

pub async fn save_role(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<RoleInput>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ACCESS_MANAGE)?;
    validate_permissions(&body.permissions)?;
    let name = body.name.trim();
    if name.is_empty() || name.chars().count() > 80 {
        return Err(ApiError::BadRequest(
            "role name must contain 1–80 characters",
        ));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    lock_company(&mut tx, context.company_id).await?;
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    let before = sqlx::query_scalar::<_, Value>(
        "SELECT to_jsonb(r) FROM access_roles r WHERE company_id=$1 AND id=$2",
    )
    .bind(context.company_id)
    .bind(id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?;
    if body.id.is_some() && before.is_none() {
        return Err(ApiError::BadRequest("role not found"));
    }
    let duplicate = sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM access_roles WHERE company_id=$1 AND lower(name)=lower($2) AND id<>$3)")
        .bind(context.company_id).bind(name).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if duplicate {
        return Err(ApiError::Conflict("role name already exists"));
    }
    sqlx::query("INSERT INTO access_roles(company_id,id,name,permissions) VALUES($1,$2,$3,$4) ON CONFLICT(company_id,id) DO UPDATE SET name=EXCLUDED.name, permissions=EXCLUDED.permissions")
        .bind(context.company_id).bind(id).bind(name).bind(&body.permissions).execute(&mut *tx).await.map_err(database_error)?;
    let after = json!({"id":id,"name":name,"permissions":body.permissions});
    audit(
        &mut tx,
        &context,
        id,
        "access.role.saved",
        before.unwrap_or(Value::Null),
        after.clone(),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(after))
}

pub async fn save_member(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<MemberInput>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ACCESS_MANAGE)?;
    validate_permissions(&body.grants)?;
    validate_permissions(&body.denies)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    lock_company(&mut tx, context.company_id).await?;
    let base = sqlx::query_scalar::<_, String>(
        "SELECT role FROM company_memberships WHERE company_id=$1 AND user_id=$2 FOR UPDATE",
    )
    .bind(context.company_id)
    .bind(id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("member not found"))?;
    if base == "owner" {
        return Err(ApiError::BadRequest("owner access is protected"));
    }
    if let Some(role_id) = body.role_id {
        let exists = sqlx::query_scalar::<_, bool>(
            "SELECT EXISTS(SELECT 1 FROM access_roles WHERE company_id=$1 AND id=$2)",
        )
        .bind(context.company_id)
        .bind(role_id)
        .fetch_one(&mut *tx)
        .await
        .map_err(database_error)?;
        if !exists {
            return Err(ApiError::BadRequest("role not found"));
        }
    }
    let before = sqlx::query_scalar::<_, Value>(
        "SELECT to_jsonb(a) FROM member_access a WHERE company_id=$1 AND user_id=$2",
    )
    .bind(context.company_id)
    .bind(id)
    .fetch_optional(&mut *tx)
    .await
    .map_err(database_error)?;
    sqlx::query("INSERT INTO member_access(company_id,user_id,role_id,grants,denies) VALUES($1,$2,$3,$4,$5) ON CONFLICT(company_id,user_id) DO UPDATE SET role_id=EXCLUDED.role_id,grants=EXCLUDED.grants,denies=EXCLUDED.denies")
        .bind(context.company_id).bind(id).bind(body.role_id).bind(&body.grants).bind(&body.denies).execute(&mut *tx).await.map_err(database_error)?;
    audit(
        &mut tx,
        &context,
        id,
        "access.member.saved",
        before.unwrap_or(Value::Null),
        json!({"role_id":body.role_id,"grants":body.grants,"denies":body.denies}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
