use super::*;
use serde::Deserialize;
use serde_json::Value;

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct CreateDocumentRequest {
    template_id: Option<Uuid>,
    title: String,
    reason: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct IssueDocument {
    status: String,
    file_id: Option<Uuid>,
    note: String,
    version: i64,
    signature_receipt: Option<String>,
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
    require_capability(&context, access::DOCUMENTS_MANAGE)?;
    feed(&state, &context, true).await
}
async fn feed(
    state: &AppState,
    context: &AuthContext,
    admin: bool,
) -> Result<Json<Vec<Value>>, ApiError> {
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',r.id,'user_id',r.user_id,'full_name',u.full_name,'title',r.title,'reason',r.reason,'status',r.status,'file_name',f.file_name,'download_url',CASE WHEN r.file_id IS NOT NULL THEN '/v1/files/'||r.file_id::text||'/download' END,'rendered_document',r.rendered_document,'signature_reference',r.signature_reference,'decision_note',r.decision_note,'version',r.version,'created_at',r.created_at::text) FROM hr_document_requests r JOIN users u ON u.id=r.user_id LEFT JOIN private_files f ON f.company_id=r.company_id AND f.id=r.file_id WHERE r.company_id=$1 AND ($3 OR r.user_id=$2) ORDER BY r.created_at DESC LIMIT 200")
        .bind(context.company_id).bind(context.user_id).bind(admin).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
pub async fn create(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<CreateDocumentRequest>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    if body.title.trim().is_empty()
        || body.title.len() > 200
        || body.reason.trim().is_empty()
        || body.reason.len() > 4000
    {
        return Err(ApiError::BadRequest("invalid document request"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO hr_document_requests(id,company_id,user_id,title,reason) VALUES($1,$2,$3,$4,$5)").bind(id).bind(context.company_id).bind(context.user_id).bind(body.title.trim()).bind(body.reason.trim()).execute(&mut *tx).await.map_err(database_error)?;
    if let Some(template) = body.template_id {
        let rendered = document_templates::render(&mut tx, &context, template).await?;
        sqlx::query("UPDATE hr_document_requests SET template_id=$3,rendered_document=$4 WHERE company_id=$1 AND id=$2").bind(context.company_id).bind(id).bind(template).bind(rendered).execute(&mut *tx).await.map_err(database_error)?;
    }
    audit(&mut tx, &context, id, "document.requested").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::CREATED)
}
pub async fn decide(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<IssueDocument>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::DOCUMENTS_MANAGE)?;
    if !matches!(body.status.as_str(), "issued" | "rejected")
        || (body.status == "issued") != body.file_id.is_some()
        || body.note.len() > 4000
        || body.status == "rejected" && body.note.trim().is_empty()
    {
        return Err(ApiError::BadRequest(
            "document file or rejection note is required",
        ));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    if let Some(file_id) = body.file_id {
        let valid:bool=sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM private_files WHERE company_id=$1 AND id=$2 AND uploaded_by=$3 AND content_type='application/pdf')")
            .bind(context.company_id).bind(file_id).bind(context.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("upload your issued document as a PDF"));
        }
        if let Some(receipt) = body.signature_receipt.as_deref() {
            let reference = providers::consume(
                &mut tx,
                &context,
                "document_signature",
                file_id,
                Some(receipt),
            )
            .await?;
            sqlx::query("UPDATE hr_document_requests SET signature_reference=$3 WHERE company_id=$1 AND id=$2").bind(context.company_id).bind(id).bind(reference).execute(&mut *tx).await.map_err(database_error)?;
        }
    }
    let recipient=sqlx::query_scalar::<_,Uuid>("UPDATE hr_document_requests SET status=$4,file_id=$5,decision_note=$6,issued_by=$3,version=version+1,updated_at=now() WHERE company_id=$1 AND id=$2 AND user_id<>$3 AND status='pending' AND version=$7 RETURNING user_id")
        .bind(context.company_id).bind(id).bind(context.user_id).bind(&body.status).bind(body.file_id).bind(body.note.trim()).bind(body.version).fetch_optional(&mut *tx).await.map_err(database_error)?.ok_or(ApiError::Conflict("document request is no longer pending; self issuance is prohibited"))?;
    sqlx::query("INSERT INTO notifications(id,company_id,user_id,title,body) VALUES($1,$2,$3,'Permintaan dokumen diperbarui','Lihat status dan dokumen di halaman surat HR.')")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(recipient).execute(&mut *tx).await.map_err(database_error)?;
    audit(&mut tx, &context, id, "document.decided").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
async fn audit(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
    event: &str,
) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id) VALUES($1,$2,$3,$4,'hr_document',$5)").bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(event).bind(id).execute(&mut **tx).await.map_err(database_error)?;
    Ok(())
}
