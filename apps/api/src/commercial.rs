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
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct LedgerMapping {
    cash_account_id: Option<Uuid>,
    receivable_account_id: Option<Uuid>,
    payable_account_id: Option<Uuid>,
    revenue_account_id: Option<Uuid>,
    expense_account_id: Option<Uuid>,
}
fn method() -> String {
    "transfer".into()
}
#[allow(clippy::too_many_arguments)]
async fn post_journal(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    company_id: Uuid,
    user_id: Uuid,
    entry_date: &str,
    reference: &str,
    description: &str,
    source_type: &str,
    source_id: Uuid,
    debit: Uuid,
    credit: Uuid,
    amount: i64,
) -> Result<Uuid, ApiError> {
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO accounting_journals(id,company_id,number,entry_date,status,reference,description,source_type,source_id,created_by) VALUES($1,$2,$3,$4::date,'draft',$5,$6,$7,$8,$9)").bind(id).bind(company_id).bind(format!("JRN-{}",id.simple())).bind(entry_date).bind(reference).bind(description).bind(source_type).bind(source_id).bind(user_id).execute(&mut **tx).await.map_err(database_error)?;
    for (account, debit_cents, credit_cents) in [(debit, amount, 0), (credit, 0, amount)] {
        sqlx::query("INSERT INTO accounting_journal_lines(id,journal_id,account_id,debit_cents,credit_cents,memo) VALUES($1,$2,$3,$4,$5,$6)").bind(Uuid::now_v7()).bind(id).bind(account).bind(debit_cents).bind(credit_cents).bind(description).execute(&mut **tx).await.map_err(database_error)?;
    }
    sqlx::query("UPDATE accounting_journals SET status='posted',posted_at=NOW() WHERE id=$1")
        .bind(id)
        .execute(&mut **tx)
        .await
        .map_err(database_error)?;
    Ok(id)
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
    let mapping=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('cash_account_id',cash_account_id,'receivable_account_id',receivable_account_id,'payable_account_id',payable_account_id,'revenue_account_id',revenue_account_id,'expense_account_id',expense_account_id) FROM commercial_ledger_mappings WHERE company_id=$1").bind(c.company_id).fetch_optional(&s.pool).await.map_err(database_error)?;
    Ok(Json(
        json!({"customers":customers,"documents":documents,"ledger_mapping":mapping}),
    ))
}
pub async fn save_mapping(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<LedgerMapping>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    let ids = [
        b.cash_account_id,
        b.receivable_account_id,
        b.payable_account_id,
        b.revenue_account_id,
        b.expense_account_id,
    ]
    .into_iter()
    .flatten()
    .collect::<Vec<_>>();
    let count = sqlx::query_scalar::<_, i64>(
        "SELECT count(*) FROM accounting_accounts WHERE company_id=$1 AND id=ANY($2) AND is_active",
    )
    .bind(c.company_id)
    .bind(&ids)
    .fetch_one(&s.pool)
    .await
    .map_err(database_error)?;
    if count != ids.len() as i64 {
        return Err(ApiError::BadRequest("mapping account not found"));
    }
    sqlx::query("INSERT INTO commercial_ledger_mappings(company_id,cash_account_id,receivable_account_id,payable_account_id,revenue_account_id,expense_account_id,updated_by) VALUES($1,$2,$3,$4,$5,$6,$7) ON CONFLICT(company_id) DO UPDATE SET cash_account_id=EXCLUDED.cash_account_id,receivable_account_id=EXCLUDED.receivable_account_id,payable_account_id=EXCLUDED.payable_account_id,revenue_account_id=EXCLUDED.revenue_account_id,expense_account_id=EXCLUDED.expense_account_id,updated_by=EXCLUDED.updated_by,updated_at=NOW()").bind(c.company_id).bind(b.cash_account_id).bind(b.receivable_account_id).bind(b.payable_account_id).bind(b.revenue_account_id).bind(b.expense_account_id).bind(c.user_id).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"ok":true})))
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
    sqlx::query("INSERT INTO commercial_documents(id,company_id,kind,number,counterparty_id,status,issue_date,due_date,total_cents,notes,created_by) VALUES($1,$2,$3,$4,$5,'issued',$6::date,$7::date,$8,$9,$10)").bind(id).bind(c.company_id).bind(&b.kind).bind(&number).bind(b.counterparty_id).bind(&b.issue_date).bind(b.due_date).bind(total).bind(b.notes.trim()).bind(c.user_id).execute(&mut *tx).await.map_err(database_error)?;
    for x in b.lines {
        let line_total = (x.quantity * x.unit_price_cents as f64).round() as i64;
        sqlx::query("INSERT INTO commercial_document_lines(id,document_id,description,quantity,unit_price_cents,total_cents) VALUES($1,$2,$3,$4,$5,$6)").bind(Uuid::now_v7()).bind(id).bind(x.description.trim()).bind(x.quantity).bind(x.unit_price_cents).bind(line_total).execute(&mut *tx).await.map_err(database_error)?;
    }
    let mapping=sqlx::query_as::<_,(Option<Uuid>,Option<Uuid>,Option<Uuid>,Option<Uuid>,Option<Uuid>)>("SELECT cash_account_id,receivable_account_id,payable_account_id,revenue_account_id,expense_account_id FROM commercial_ledger_mappings WHERE company_id=$1").bind(c.company_id).fetch_optional(&mut *tx).await.map_err(database_error)?;
    let journal = match (b.kind.as_str(), mapping) {
        ("invoice", Some((_, Some(receivable), _, Some(revenue), _))) => Some(
            post_journal(
                &mut tx,
                c.company_id,
                c.user_id,
                &b.issue_date,
                &number,
                &format!("Invoice {number}"),
                "commercial_invoice",
                id,
                receivable,
                revenue,
                total,
            )
            .await?,
        ),
        ("vendor_bill", Some((_, _, Some(payable), _, Some(expense)))) => Some(
            post_journal(
                &mut tx,
                c.company_id,
                c.user_id,
                &b.issue_date,
                &number,
                &format!("Vendor bill {number}"),
                "commercial_vendor_bill",
                id,
                expense,
                payable,
                total,
            )
            .await?,
        ),
        _ => None,
    };
    if let Some(journal_id) = journal {
        sqlx::query("UPDATE commercial_documents SET journal_id=$1 WHERE id=$2")
            .bind(journal_id)
            .bind(id)
            .execute(&mut *tx)
            .await
            .map_err(database_error)?;
    }
    tx.commit().await.map_err(database_error)?;
    Ok(Json(
        json!({"id":id,"number":number,"total_cents":total,"journal_posted":journal.is_some()}),
    ))
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
    let mut tx = s.pool.begin().await.map_err(database_error)?;
    let document=sqlx::query_as::<_,(String,String)>("SELECT kind,issue_date::text FROM commercial_documents WHERE id=$1 AND company_id=$2 AND status IN ('issued','paid') FOR UPDATE").bind(id).bind(c.company_id).fetch_optional(&mut *tx).await.map_err(database_error)?.ok_or(ApiError::BadRequest("document not found"))?;
    let payment_id = Uuid::now_v7();
    sqlx::query("INSERT INTO commercial_payments(id,company_id,document_id,amount_cents,method,reference,created_by) VALUES($1,$2,$3,$4,$5,$6,$7)").bind(payment_id).bind(c.company_id).bind(id).bind(b.amount_cents).bind(b.method.trim()).bind(b.reference.trim()).bind(c.user_id).execute(&mut *tx).await.map_err(database_error)?;
    let mapping=sqlx::query_as::<_,(Option<Uuid>,Option<Uuid>,Option<Uuid>)>("SELECT cash_account_id,receivable_account_id,payable_account_id FROM commercial_ledger_mappings WHERE company_id=$1").bind(c.company_id).fetch_optional(&mut *tx).await.map_err(database_error)?;
    let journal = match (document.0.as_str(), mapping) {
        ("invoice", Some((Some(cash), Some(ar), _))) => Some(
            post_journal(
                &mut tx,
                c.company_id,
                c.user_id,
                &document.1,
                b.reference.trim(),
                "Invoice payment",
                "commercial_payment",
                payment_id,
                cash,
                ar,
                b.amount_cents,
            )
            .await?,
        ),
        ("vendor_bill", Some((Some(cash), _, Some(ap)))) => Some(
            post_journal(
                &mut tx,
                c.company_id,
                c.user_id,
                &document.1,
                b.reference.trim(),
                "Vendor payment",
                "commercial_payment",
                payment_id,
                ap,
                cash,
                b.amount_cents,
            )
            .await?,
        ),
        _ => None,
    };
    if let Some(journal_id) = journal {
        sqlx::query("UPDATE commercial_payments SET journal_id=$1 WHERE id=$2")
            .bind(journal_id)
            .bind(payment_id)
            .execute(&mut *tx)
            .await
            .map_err(database_error)?;
    }
    sqlx::query("UPDATE commercial_documents SET status=CASE WHEN COALESCE((SELECT sum(amount_cents) FROM commercial_payments WHERE document_id=$1),0)>=total_cents THEN 'paid' ELSE status END WHERE id=$1").bind(id).execute(&mut *tx).await.map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"ok":true,"journal_posted":journal.is_some()})))
}
