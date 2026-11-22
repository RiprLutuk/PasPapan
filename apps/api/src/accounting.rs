use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Account {
    code: String,
    name: String,
    account_type: String,
    normal_balance: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct JournalLine {
    account_id: Uuid,
    debit_cents: i64,
    credit_cents: i64,
    #[serde(default)]
    memo: String,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Journal {
    entry_date: String,
    reference: String,
    description: String,
    lines: Vec<JournalLine>,
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
    require_capability(
        &c,
        if write {
            access::ACCOUNTING_MANAGE
        } else {
            access::ACCOUNTING_VIEW
        },
    )?;
    Ok(c)
}
pub async fn list(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, false).await?;
    let accounts=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'code',code,'name',name,'type',account_type,'normal_balance',normal_balance) FROM accounting_accounts WHERE company_id=$1 AND is_active ORDER BY code").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    let journals=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'number',number,'entry_date',entry_date,'status',status,'reference',reference,'description',description) FROM accounting_journals WHERE company_id=$1 ORDER BY entry_date DESC LIMIT 100").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"accounts":accounts,"journals":journals})))
}
pub async fn account(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Account>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.code.trim().is_empty()
        || b.name.trim().is_empty()
        || !matches!(
            b.account_type.as_str(),
            "asset" | "liability" | "equity" | "income" | "expense"
        )
        || !matches!(b.normal_balance.as_str(), "debit" | "credit")
    {
        return Err(ApiError::BadRequest("invalid account"));
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO accounting_accounts(id,company_id,code,name,account_type,normal_balance) VALUES($1,$2,$3,$4,$5,$6)").bind(id).bind(c.company_id).bind(b.code.trim()).bind(b.name.trim()).bind(b.account_type).bind(b.normal_balance).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn journal(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Journal>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    let debit: i64 = b.lines.iter().map(|x| x.debit_cents).sum();
    let credit: i64 = b.lines.iter().map(|x| x.credit_cents).sum();
    if b.lines.len() < 2
        || debit <= 0
        || debit != credit
        || b.entry_date.len() != 10
        || b.lines.iter().any(|x| {
            x.debit_cents < 0
                || x.credit_cents < 0
                || (x.debit_cents == 0) == (x.credit_cents == 0)
                || x.memo.len() > 500
        })
    {
        return Err(ApiError::BadRequest("invalid journal"));
    }
    let mut tx = s.pool.begin().await.map_err(database_error)?;
    let id = Uuid::now_v7();
    let number = format!("JRN-{}", id.simple());
    sqlx::query("INSERT INTO accounting_journals(id,company_id,number,entry_date,status,reference,description,created_by,posted_at) VALUES($1,$2,$3,$4::date,'posted',$5,$6,$7,NOW())").bind(id).bind(c.company_id).bind(number).bind(b.entry_date).bind(b.reference).bind(b.description).bind(c.user_id).execute(&mut *tx).await.map_err(database_error)?;
    for line in b.lines {
        let valid=sqlx::query_scalar::<_,bool>("SELECT EXISTS(SELECT 1 FROM accounting_accounts WHERE id=$1 AND company_id=$2 AND is_active)").bind(line.account_id).bind(c.company_id).fetch_one(&mut *tx).await.map_err(database_error)?;
        if !valid {
            return Err(ApiError::BadRequest("account not found"));
        }
        sqlx::query("INSERT INTO accounting_journal_lines(id,journal_id,account_id,debit_cents,credit_cents,memo) VALUES($1,$2,$3,$4,$5,$6)").bind(Uuid::now_v7()).bind(id).bind(line.account_id).bind(line.debit_cents).bind(line.credit_cents).bind(line.memo).execute(&mut *tx).await.map_err(database_error)?;
    }
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
