use super::*;
use serde::Deserialize;
use serde_json::{Value, json};
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Product {
    sku: String,
    name: String,
    price_cents: i64,
    #[serde(default)]
    stock_qty: i32,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaleLine {
    product_id: Uuid,
    quantity: i32,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Sale {
    lines: Vec<SaleLine>,
    #[serde(default = "cash")]
    payment_method: String,
    #[serde(default)]
    discount_cents: i64,
    #[serde(default)]
    tax_cents: i64,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Refund {
    #[serde(default)]
    note: String,
}
fn cash() -> String {
    "cash".into()
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
    require_capability(&c, access::POS_MANAGE)?;
    Ok(c)
}
pub async fn list(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, false).await?;
    let products=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'sku',sku,'name',name,'price_cents',price_cents,'stock_qty',stock_qty,'is_active',is_active) FROM pos_products WHERE company_id=$1 ORDER BY name").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    let sales=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',id,'receipt_number',receipt_number,'status',status,'subtotal_cents',subtotal_cents,'discount_cents',discount_cents,'tax_cents',tax_cents,'total_cents',total_cents,'payment_method',payment_method,'created_at',created_at) FROM pos_sales WHERE company_id=$1 ORDER BY created_at DESC LIMIT 100").bind(c.company_id).fetch_all(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"products":products,"sales":sales})))
}
pub async fn product(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Product>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.sku.trim().is_empty() || b.name.trim().is_empty() || b.price_cents < 0 || b.stock_qty < 0 {
        return Err(ApiError::BadRequest("invalid product"));
    }
    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO pos_products(id,company_id,sku,name,price_cents,stock_qty) VALUES($1,$2,$3,$4,$5,$6)").bind(id).bind(c.company_id).bind(b.sku.trim()).bind(b.name.trim()).bind(b.price_cents).bind(b.stock_qty).execute(&s.pool).await.map_err(database_error)?;
    Ok(Json(json!({"id":id})))
}
pub async fn sale(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Json(b): Json<Sale>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.lines.is_empty()
        || b.lines.iter().any(|x| x.quantity <= 0)
        || b.discount_cents < 0
        || b.tax_cents < 0
    {
        return Err(ApiError::BadRequest("invalid sale"));
    }
    let mut tx = s.pool.begin().await.map_err(database_error)?;
    let mut lines = Vec::new();
    let mut total = 0i64;
    for x in b.lines {
        let row=sqlx::query_as::<_,(String,i64,i32)>("SELECT name,price_cents,stock_qty FROM pos_products WHERE id=$1 AND company_id=$2 AND is_active FOR UPDATE").bind(x.product_id).bind(c.company_id).fetch_optional(&mut *tx).await.map_err(database_error)?.ok_or(ApiError::BadRequest("product not found"))?;
        if row.2 < x.quantity {
            return Err(ApiError::BadRequest("insufficient stock"));
        }
        let line_total = row.1 * x.quantity as i64;
        total += line_total;
        lines.push((x.product_id, row.0, row.1, x.quantity, line_total));
    }
    if b.discount_cents > total {
        return Err(ApiError::BadRequest("discount exceeds subtotal"));
    }
    let subtotal = total;
    total = subtotal - b.discount_cents + b.tax_cents;
    let id = Uuid::now_v7();
    let receipt = format!("POS-{}", id.simple());
    sqlx::query("INSERT INTO pos_sales(id,company_id,receipt_number,subtotal_cents,discount_cents,tax_cents,total_cents,payment_method,cashier_id) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9)").bind(id).bind(c.company_id).bind(&receipt).bind(subtotal).bind(b.discount_cents).bind(b.tax_cents).bind(total).bind(b.payment_method.trim()).bind(c.user_id).execute(&mut *tx).await.map_err(database_error)?;
    for (product_id, name, price, quantity, line_total) in lines {
        sqlx::query("UPDATE pos_products SET stock_qty=stock_qty-$1 WHERE id=$2")
            .bind(quantity)
            .bind(product_id)
            .execute(&mut *tx)
            .await
            .map_err(database_error)?;
        sqlx::query("INSERT INTO pos_sale_lines(id,sale_id,product_id,product_name,quantity,unit_price_cents,total_cents) VALUES($1,$2,$3,$4,$5,$6,$7)").bind(Uuid::now_v7()).bind(id).bind(product_id).bind(name).bind(quantity).bind(price).bind(line_total).execute(&mut *tx).await.map_err(database_error)?;
    }
    tx.commit().await.map_err(database_error)?;
    Ok(Json(
        json!({"id":id,"receipt_number":receipt,"subtotal_cents":subtotal,"discount_cents":b.discount_cents,"tax_cents":b.tax_cents,"total_cents":total}),
    ))
}
pub async fn refund(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(b): Json<Refund>,
) -> Result<Json<Value>, ApiError> {
    let c = ctx(&s, &h, true).await?;
    if b.note.len() > 1000 {
        return Err(ApiError::BadRequest("refund note too long"));
    }
    let mut tx = s.pool.begin().await.map_err(database_error)?;
    let changed=sqlx::query("UPDATE pos_sales SET status='refunded',refunded_at=NOW(),refunded_by=$1,refund_note=$2 WHERE id=$3 AND company_id=$4 AND status='paid'").bind(c.user_id).bind(b.note.trim()).bind(id).bind(c.company_id).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if changed == 0 {
        return Err(ApiError::BadRequest("sale not found or already refunded"));
    }
    let lines = sqlx::query_as::<_, (Option<Uuid>, i32)>(
        "SELECT product_id,quantity FROM pos_sale_lines WHERE sale_id=$1",
    )
    .bind(id)
    .fetch_all(&mut *tx)
    .await
    .map_err(database_error)?;
    for (product, quantity) in lines {
        if let Some(product_id) = product {
            sqlx::query(
                "UPDATE pos_products SET stock_qty=stock_qty+$1 WHERE id=$2 AND company_id=$3",
            )
            .bind(quantity)
            .bind(product_id)
            .bind(c.company_id)
            .execute(&mut *tx)
            .await
            .map_err(database_error)?;
        }
    }
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"ok":true})))
}

/// A server-rendered receipt is stable after the sale, unlike re-rendering a
/// browser popup from an abbreviated list response.
pub async fn receipt_pdf(
    State(s): State<AppState>,
    h: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<Response, ApiError> {
    let c = ctx(&s, &h, false).await?;
    let sale = sqlx::query_as::<_, (String, String, i64, i64, i64, i64, String, String)>(
        "SELECT receipt_number,status,subtotal_cents,discount_cents,tax_cents,total_cents,payment_method,created_at::text
         FROM pos_sales WHERE id=$1 AND company_id=$2",
    )
    .bind(id)
    .bind(c.company_id)
    .fetch_optional(&s.pool)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::BadRequest("sale not found"))?;
    let lines = sqlx::query_as::<_, (String, i32, i64, i64)>(
        "SELECT product_name,quantity,unit_price_cents,total_cents FROM pos_sale_lines WHERE sale_id=$1 ORDER BY id",
    )
    .bind(id)
    .fetch_all(&s.pool)
    .await
    .map_err(database_error)?;
    let mut content = vec![
        format!("Receipt: {}", sale.0),
        format!("Status: {} | Payment: {}", sale.1, sale.6),
        format!("Created: {}", sale.7),
        String::new(),
    ];
    content.extend(lines.into_iter().map(|(name, quantity, price, total)| {
        format!("{name} — {quantity} x Rp {price} = Rp {total}")
    }));
    content.extend([
        String::new(),
        format!("Subtotal: Rp {}", sale.2),
        format!("Discount: Rp {}", sale.3),
        format!("Tax: Rp {}", sale.4),
        format!("TOTAL: Rp {}", sale.5),
    ]);
    Ok(crate::reports::pdf_response(
        &format!("{}-receipt.pdf", sale.0),
        "PasPapan POS receipt",
        content,
    ))
}
