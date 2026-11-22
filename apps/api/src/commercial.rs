use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Customer {
    name: String,
    #[serde(default)]
    email: String,
    #[serde(default)]
    phone: String,
    #[serde(default)]
    address: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Line {
    description: String,
    quantity: f64,
    unit_price_cents: i64,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Document {
    kind: String,
    counterparty_id: Option<Uuid>,
    issue_date: String,
    due_date: Option<String>,
    #[serde(default)]
    notes: String,
    lines: Vec<Line>,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Payment {
    amount_cents: i64,
    #[serde(default = "method")]
    method: String,
    #[serde(default)]
    reference: String,
}
fn method() -> String {
    "transfer".into()
}
async fn ctx(
    s: &AppState,
    h: &axum::http::HeaderMap,
    write: bool,
) -> Result<AuthContext, ApiError> {
    if write {
        require_trusted_origin(h, &s.app_origin)?;
    }
    let c = authenticated_context(&s.pool, h).await?;
    require_capability(&c, access::COMMERCIAL_MANAGE)?;
    Ok(c)
}
pub async fn list(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, false).await?;
    let customers=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'name',name,'email',email,'phone',phone) FROM commercial_customers WHERE company_id=$1 ORDER BY name").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    let documents=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',d.id,'kind',kind,'number',number,'status',status,'issue_date',issue_date,'due_date',due_date,'total_cents',total_cents,'counterparty',coalesce(c.name,'')) FROM commercial_documents d LEFT JOIN commercial_customers c ON c.id=d.counterparty_id WHERE d.company_id=$1 ORDER BY d.created_at DESC LIMIT 100").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"customers":customers,"documents":documents})))
}
pub async fn customer(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Customer>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.name.trim().is_empty() {
        return Err(ApiError::BadRequest("customer name required"));
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO commercial_customers(id,company_id,name,email,phone,address) VALUES($1,$2,$3,$4,$5,$6)").bind(id).bind(c.company_id).bind(b.name.trim()).bind(b.email.trim()).bind(b.phone.trim()).bind(b.address.trim()).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn document(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Document>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if !matches!(b.kind.as_str(), "quote" | "invoice" | "vendor_bill")
        || b.issue_date.len() != 10
        || b.lines.is_empty()
        || b.lines
            .iter()
            .any(|x| x.description.trim().is_empty() || x.quantity <= 0.0 || x.unit_price_cents < 0)
    {
        return Err(ApiError::BadRequest("invalid commercial document"));
    }
    let total = b
        .lines
        .iter()
        .map(|x| (x.quantity * x.unit_price_cents as f64).round() as i64)
        .sum::<i64>();
    let id = Uuid::now_v7();
    let number = format!("{}-{}", b.kind.to_uppercase(), id.simple());
    let mut tx = s.pool.begin().await.map_err(database_error)?;
    sqlx::query("INSERT INTO commercial_documents(id,company_id,kind,number,counterparty_id,status,issue_date,due_date,total_cents,notes,created_by) VALUES($1,$2,$3,$4,$5,'issued',$6::date,$7::date,$8,$9,$10)").bind(id).bind(c.company_id).bind(b.kind).bind(&number).bind(b.counterparty_id).bind(b.issue_date).bind(b.due_date).bind(total).bind(b.notes.trim()).bind(c.user_id).execute(&mut *tx).await.map_err(database_error)?;
    for x in b.lines {
        let line_total = (x.quantity * x.unit_price_cents as f64).round() as i64;
        sqlx::query("INSERT INTO commercial_document_lines(id,document_id,description,quantity,unit_price_cents,total_cents) VALUES($1,$2,$3,$4,$5,$6)").bind(Uuid::now_v7()).bind(id).bind(x.description.trim()).bind(x.quantity).bind(x.unit_price_cents).bind(line_total).execute(&mut *tx).await.map_err(database_error)?;
    }
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":id,"number":number,"total_cents":total})))
}
pub async fn payment(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(b): Json<Payment>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.amount_cents <= 0 {
        return Err(ApiError::BadRequest("invalid payment"));
    }
    let exists=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM commercial_documents WHERE id=$1 AND company_id=$2 AND status IN ('issued','paid'))").bind(id).bind(c.company_id).fetch_one(&s.pool).await.map_err(database_error)?;
    if !exists {
        return Err(ApiError::BadRequest("document not found"));
    }
    sqlx::query("INSERT INTO commercial_payments(id,company_id,document_id,amount_cents,method,reference,created_by) VALUES($1,$2,$3,$4,$5,$6,$7)").bind(Uuid::now_v7()).bind(c.company_id).bind(id).bind(b.amount_cents).bind(b.method.trim()).bind(b.reference.trim()).bind(c.user_id).execute(&s.pool).await.map_err(database_error)?;
    sqlx::query("UPDATE commercial_documents SET status=CASE WHEN COALESCE((SELECT sum(amount_cents) FROM commercial_payments WHERE document_id=$1),0)>=total_cents THEN 'paid' ELSE status END WHERE id=$1").bind(id).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"ok":true})))
}
