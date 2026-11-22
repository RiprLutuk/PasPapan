use super::*;
use axum::extract::Path;
use serde::{Deserialize, Serialize};
use serde_json::{Value, json};
use std::collections::HashSet;
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Template {
    title: String,
    category: String,
    #[serde(default)]
    description: String,
    fields: Vec<Field>,
}
#[derive(Deserialize, Serialize)]
#[serde(deny_unknown_fields)]
pub struct Field {
    key: String,
    label: String,
    kind: String,
    #[serde(default)]
    required: bool,
    #[serde(default)]
    options: Vec<String>,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Submission {
    payload: Value,
}
async fn ctx(
    state: &AppState,
    headers: &axum::http::HeaderMap,
    write: bool,
    cap: &str,
) -> Result<AuthContext, ApiError> {
    if write {
        require_trusted_origin(headers, &state.app_origin)?;
    }
    let c = authenticated_context(&state.pool, headers).await?;
    require_capability(&c, cap)?;
    Ok(c)
}
pub async fn mine(
    State(s): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let c = authenticated_context(&s.pool, &headers).await?;
    let templates=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'title',title,'category',category,'description',description,'fields',fields) FROM custom_form_templates WHERE company_id=$1 AND is_active ORDER BY title").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    let submissions=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',x.id,'title',t.title,'status',x.status,'created_at',x.created_at,'payload',x.payload) FROM custom_form_submissions x JOIN custom_form_templates t ON t.id=x.template_id WHERE x.company_id=$1 AND x.submitted_by=$2 ORDER BY x.created_at DESC LIMIT 30").bind(c.company_id).bind(c.user_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"templates":templates,"submissions":submissions}),
    ))
}
pub async fn admin(
    State(s): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &headers, false, access::CUSTOM_FORMS_VIEW).await?;
    let templates=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',t.id,'title',t.title,'category',t.category,'description',t.description,'fields',t.fields,'is_active',t.is_active,'submission_count',(SELECT count(*) FROM custom_form_submissions x WHERE x.template_id=t.id)) FROM custom_form_templates t WHERE t.company_id=$1 ORDER BY t.created_at DESC").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    let submissions=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',x.id,'title',t.title,'employee',u.full_name,'status',x.status,'payload',x.payload,'created_at',x.created_at) FROM custom_form_submissions x JOIN custom_form_templates t ON t.id=x.template_id JOIN users u ON u.id=x.submitted_by WHERE x.company_id=$1 ORDER BY x.created_at DESC LIMIT 100").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"templates":templates,"submissions":submissions}),
    ))
}
pub async fn create(
    State(s): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(b): Json<Template>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &headers, true, access::CUSTOM_FORMS_MANAGE).await?;
    validate(&b)?;
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO custom_form_templates(id,company_id,title,category,description,fields,created_by) VALUES($1,$2,$3,$4,$5,$6,$7)").bind(id).bind(c.company_id).bind(b.title.trim()).bind(b.category.trim()).bind(b.description.trim()).bind(serde_json::to_value(b.fields).map_err(|_|ApiError::BadRequest("invalid fields"))?).bind(c.user_id).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn submit(
    State(s): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(b): Json<Submission>,
) -> Result<Json<Value>, ApiError> {
    let c = authenticated_context(&s.pool, &headers).await?;
    require_trusted_origin(&headers, &s.app_origin)?;
    let fields = sqlx::query_scalar::<_, Value>(
        "SELECT fields FROM custom_form_templates WHERE id=$1 AND company_id=$2 AND is_active",
    )
    .bind(id)
    .bind(c.company_id)
    .fetch_optional(&s.pool)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("form not found"))?;
    validate_payload(&fields, &b.payload)?;
    let sid = Uuid::now_v7();
    sqlx::query("INSERT INTO custom_form_submissions(id,company_id,template_id,submitted_by,payload) VALUES($1,$2,$3,$4,$5)").bind(sid).bind(c.company_id).bind(id).bind(c.user_id).bind(b.payload).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":sid})))
}
fn validate(b: &Template) -> Result<(), ApiError> {
    if b.title.trim().is_empty()
        || b.category.trim().is_empty()
        || b.fields.is_empty()
        || b.fields.len() > 30
    {
        return Err(ApiError::BadRequest("invalid form template"));
    }
    let mut keys = HashSet::new();
    for f in &b.fields {
        if f.key.is_empty()
            || f.key.len() > 80
            || f.label.trim().is_empty()
            || !matches!(
                f.kind.as_str(),
                "text" | "textarea" | "number" | "date" | "select"
            )
            || !keys.insert(&f.key)
            || (f.kind == "select" && f.options.is_empty())
        {
            return Err(ApiError::BadRequest("invalid form field"));
        }
    }
    Ok(())
}
fn validate_payload(fields: &Value, payload: &Value) -> Result<(), ApiError> {
    let obj = payload
        .as_object()
        .ok_or(ApiError::BadRequest("invalid form response"))?;
    for f in fields
        .as_array()
        .ok_or(ApiError::BadRequest("invalid form"))?
    {
        let key = f
            .get("key")
            .and_then(Value::as_str)
            .ok_or(ApiError::BadRequest("invalid form"))?;
        let value = obj.get(key);
        if f.get("required").and_then(Value::as_bool) == Some(true)
            && value.and_then(Value::as_str).is_none_or(str::is_empty)
        {
            return Err(ApiError::BadRequest("required form field missing"));
        }
        if let Some(value) = value.and_then(Value::as_str) {
            if value.len() > 5000 {
                return Err(ApiError::BadRequest("form response too long"));
            }
            if f.get("kind").and_then(Value::as_str) == Some("select")
                && !f
                    .get("options")
                    .and_then(Value::as_array)
                    .is_some_and(|o| o.iter().any(|x| x.as_str() == Some(value)))
            {
                return Err(ApiError::BadRequest("invalid form option"));
            }
        }
    }
    Ok(())
}
