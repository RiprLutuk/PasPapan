use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Template {
    id: Option<Uuid>,
    version: Option<i64>,
    title: String,
    body: String,
    active: bool,
}
const VARIABLES: [&str; 6] = [
    "employee_name",
    "employee_code",
    "company_name",
    "date",
    "division",
    "job_title",
];
fn valid_template(text: &str) -> bool {
    let mut remaining = text;
    while let Some(start) = remaining.find("{{") {
        if remaining[..start].contains("}}") {
            return false;
        }
        let tail = &remaining[start + 2..];
        let Some(end) = tail.find("}}") else {
            return false;
        };
        if !VARIABLES.contains(&&tail[..end]) {
            return false;
        }
        remaining = &tail[end + 2..];
    }
    !remaining.contains("}}")
}
pub async fn list(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let admin = context
        .capabilities
        .iter()
        .any(|v| v == access::DOCUMENTS_MANAGE);
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'title',title,'body',CASE WHEN $2 THEN body ELSE NULL END,'active',active,'version',version) FROM hr_document_templates WHERE company_id=$1 AND ($2 OR active) ORDER BY title,id").bind(context.company_id).bind(admin).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<Template>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::DOCUMENTS_MANAGE)?;
    if body.title.trim().is_empty()
        || body.title.len() > 200
        || body.body.trim().is_empty()
        || body.body.len() > 20000
        || !valid_template(&body.body)
    {
        return Err(ApiError::BadRequest(
            "invalid document template or unsupported placeholder",
        ));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    if body.id.is_some() {
        let count=sqlx::query("UPDATE hr_document_templates SET title=$3,body=$4,active=$5,version=version+1 WHERE company_id=$1 AND id=$2 AND version=$6").bind(context.company_id).bind(id).bind(body.title.trim()).bind(body.body.trim()).bind(body.active).bind(body.version).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
        if count != 1 {
            return Err(ApiError::Conflict("document template changed; reload"));
        }
    } else {
        sqlx::query("INSERT INTO hr_document_templates(id,company_id,title,body,active) VALUES($1,$2,$3,$4,$5)").bind(id).bind(context.company_id).bind(body.title.trim()).bind(body.body.trim()).bind(body.active).execute(&mut *tx).await.map_err(database_error)?;
    }
    people::audit(
        &mut tx,
        &context,
        id,
        "document.template_saved",
        json!({"active":body.active}),
    )
    .await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
pub async fn render(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
) -> Result<Value, ApiError> {
    let (title,mut body,version)=sqlx::query_as::<_,(String,String,i64)>("SELECT title,body,version FROM hr_document_templates WHERE company_id=$1 AND id=$2 AND active FOR SHARE").bind(context.company_id).bind(id).fetch_optional(&mut **tx).await.map_err(database_error)?.ok_or(ApiError::BadRequest("active document template not found"))?;
    let (name,code,company,date,team,job)=sqlx::query_as::<_,(String,String,String,String,String,String)>("SELECT u.full_name,COALESCE(p.employee_code,''),c.name,(now() AT TIME ZONE c.timezone)::date::text,COALESCE(p.team,''),COALESCE(p.job_title,'') FROM companies c JOIN users u ON u.id=$2 LEFT JOIN employee_profiles p ON p.company_id=c.id AND p.user_id=u.id WHERE c.id=$1").bind(context.company_id).bind(context.user_id).fetch_one(&mut **tx).await.map_err(database_error)?;
    // Replace from the original template only: values cannot introduce new placeholders.
    let values = [name, code, company, date, team, job];
    let original = body.clone();
    body.clear();
    let mut tail = original.as_str();
    while let Some(start) = tail.find("{{") {
        body.push_str(&tail[..start]);
        let token = &tail[start + 2..];
        let end = token.find("}}").ok_or(ApiError::Internal)?;
        let index = VARIABLES
            .iter()
            .position(|v| *v == &token[..end])
            .ok_or(ApiError::Internal)?;
        body.push_str(&values[index]);
        tail = &token[end + 2..];
    }
    body.push_str(tail);
    Ok(
        json!({"title":title,"body":body,"template_id":id,"template_version":version,"generated_on":values[3]}),
    )
}
#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn templates_reject_unknown_or_unclosed_variables() {
        assert!(valid_template(
            "Hello {{employee_name}} at {{company_name}}"
        ));
        assert!(!valid_template("{{password}}"));
        assert!(!valid_template("{{employee_name"));
        assert!(!valid_template("}} broken"));
    }
}
