use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveAnnouncement {
    id: Option<Uuid>,
    version: Option<i64>,
    title: String,
    body: String,
    status: String,
    visible_from: Option<String>,
    audience_team: Option<String>,
}

fn validate(body: &SaveAnnouncement) -> Result<(), ApiError> {
    if body.title.trim().is_empty()
        || body.title.len() > 200
        || body.body.trim().is_empty()
        || body.body.len() > 20000
        || !matches!(body.status.as_str(), "draft" | "published" | "archived")
        || (body.id.is_some() && body.version.is_none_or(|version| version < 1))
    {
        return Err(ApiError::BadRequest("invalid announcement details"));
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
    require_capability(&context, access::ANNOUNCEMENTS_MANAGE)?;
    feed(&state, &context, true).await
}

async fn feed(
    state: &AppState,
    context: &AuthContext,
    admin: bool,
) -> Result<Json<Vec<Value>>, ApiError> {
    sqlx::query("SELECT dispatch_announcements($1)")
        .bind(context.company_id)
        .execute(&state.pool)
        .await
        .map_err(database_error)?;
    let rows = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'id', a.id, 'title', a.title, 'body', a.body, 'status', a.status,
            'version', a.version, 'author_name', u.full_name,
            'published_at', a.published_at::text, 'updated_at', a.updated_at::text,
            'visible_from',a.visible_from::text,'audience_team',a.audience_team,
            'read', EXISTS(SELECT 1 FROM announcement_reads ar WHERE ar.company_id = a.company_id AND ar.announcement_id = a.id AND ar.user_id = $2),
            'read_count', CASE WHEN $3 THEN (SELECT count(*) FROM announcement_reads ar WHERE ar.company_id = a.company_id AND ar.announcement_id = a.id) ELSE NULL END
         ) FROM announcements a
         JOIN users u ON u.id = a.author_id
         WHERE a.company_id = $1 AND ($3 OR (a.status = 'published' AND COALESCE(a.visible_from,a.published_at)<=now()
             AND (a.audience_team IS NULL OR EXISTS(SELECT 1 FROM employee_profiles p WHERE p.company_id=a.company_id AND p.user_id=$2 AND p.team=a.audience_team))))
         ORDER BY COALESCE(a.published_at, a.created_at) DESC, a.id DESC LIMIT 200",
    )
    .bind(context.company_id)
    .bind(context.user_id)
    .bind(admin)
    .fetch_all(&state.pool)
    .await
    .map_err(database_error)?;
    Ok(Json(rows))
}

pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveAnnouncement>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ANNOUNCEMENTS_MANAGE)?;
    validate(&body)?;
    if let Some(date) = body.visible_from.as_deref() {
        let valid: bool = sqlx::query_scalar("SELECT pg_input_is_valid($1,'timestamptz')")
            .bind(date)
            .fetch_one(&state.pool)
            .await
            .map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("invalid publication date"));
        }
    }
    if let Some(team) = body.audience_team.as_deref() {
        let valid:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM organization_reference_data WHERE company_id=$1 AND kind='division' AND active AND name=$2)").bind(context.company_id).bind(team).fetch_one(&state.pool).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("active audience division not found"));
        }
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    let previous = if body.id.is_some() {
        Some(sqlx::query_as::<_, (String, i64)>(
            "SELECT status, version FROM announcements WHERE company_id = $1 AND id = $2 FOR UPDATE",
        )
        .bind(context.company_id).bind(id).fetch_optional(&mut *tx).await.map_err(database_error)?
        .ok_or(ApiError::BadRequest("announcement not found"))?)
    } else {
        None
    };
    if previous
        .as_ref()
        .is_some_and(|(_, version)| Some(*version) != body.version)
    {
        return Err(ApiError::Conflict(
            "announcement was changed; reload and retry",
        ));
    }
    if previous.is_some() {
        sqlx::query(
            "UPDATE announcements SET title = $3, body = $4, status = $5,
             published_at = CASE WHEN $5 = 'published' THEN COALESCE(published_at, now()) ELSE published_at END,
             version = version + 1, updated_at = now() WHERE company_id = $1 AND id = $2",
        ).bind(context.company_id).bind(id).bind(body.title.trim()).bind(body.body.trim()).bind(&body.status)
        .execute(&mut *tx).await.map_err(database_error)?;
    } else {
        sqlx::query(
            "INSERT INTO announcements(id, company_id, author_id, title, body, status, published_at)
             VALUES($1,$2,$3,$4,$5,$6,CASE WHEN $6 = 'published' THEN now() ELSE NULL END)",
        ).bind(id).bind(context.company_id).bind(context.user_id).bind(body.title.trim()).bind(body.body.trim()).bind(&body.status)
        .execute(&mut *tx).await.map_err(database_error)?;
    }
    let reset = body.status == "published"
        && previous
            .as_ref()
            .is_some_and(|(status, _)| status != "published");
    sqlx::query("UPDATE announcements SET visible_from=$3::text::timestamptz,audience_team=$4,notification_sent_at=CASE WHEN $5 THEN NULL ELSE notification_sent_at END WHERE company_id=$1 AND id=$2")
        .bind(context.company_id).bind(id).bind(&body.visible_from).bind(&body.audience_team).bind(reset).execute(&mut *tx).await.map_err(database_error)?;
    sqlx::query("SELECT dispatch_announcements($1)")
        .bind(context.company_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    sqlx::query(
        "INSERT INTO audit_events(id, company_id, actor_id, event_type, subject_type, subject_id, metadata)
         VALUES($1,$2,$3,'announcement.saved','announcement',$4,$5)",
    ).bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(id).bind(json!({"status": body.status}))
    .execute(&mut *tx).await.map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id": id})))
}

pub async fn mark_read(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let exists: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM announcements a WHERE company_id = $1 AND id = $2 AND status = 'published' AND COALESCE(a.visible_from,a.published_at)<=now() AND (a.audience_team IS NULL OR EXISTS(SELECT 1 FROM employee_profiles p WHERE p.company_id=a.company_id AND p.user_id=$3 AND p.team=a.audience_team)))")
        .bind(context.company_id).bind(id).bind(context.user_id).fetch_one(&state.pool).await.map_err(database_error)?;
    if !exists {
        return Err(ApiError::BadRequest("announcement not found"));
    }
    sqlx::query("INSERT INTO announcement_reads(company_id, announcement_id, user_id) VALUES($1,$2,$3) ON CONFLICT DO NOTHING")
        .bind(context.company_id).bind(id).bind(context.user_id).execute(&state.pool).await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn announcement_requires_content_valid_status_and_edit_version() {
        let mut body = SaveAnnouncement {
            id: None,
            version: None,
            title: "Notice".into(),
            body: "Team update".into(),
            status: "draft".into(),
            visible_from: None,
            audience_team: None,
        };
        assert!(validate(&body).is_ok());
        body.status = "approved".into();
        assert!(validate(&body).is_err());
        body.status = "published".into();
        body.id = Some(Uuid::now_v7());
        assert!(validate(&body).is_err());
        body.version = Some(1);
        assert!(validate(&body).is_ok());
        body.body = " ".into();
        assert!(validate(&body).is_err());
    }
}
