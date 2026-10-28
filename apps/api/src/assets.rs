use super::*;
use serde::Deserialize;
use serde_json::{Value, json};

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct SaveAsset {
    id: Option<Uuid>,
    version: Option<i64>,
    asset_code: String,
    name: String,
    serial_number: String,
    condition: String,
    notes: String,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct AssignAsset {
    user_id: Uuid,
}

#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct ReturnAsset {
    note: String,
    condition: String,
}

fn valid_condition(value: &str) -> bool {
    matches!(value, "good" | "damaged" | "maintenance" | "retired")
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
    require_capability(&context, access::ASSETS_MANAGE)?;
    feed(&state, &context, true).await
}
async fn feed(
    state: &AppState,
    context: &AuthContext,
    admin: bool,
) -> Result<Json<Vec<Value>>, ApiError> {
    let rows = sqlx::query_scalar::<_, Value>(
        "SELECT jsonb_build_object(
            'id', a.id, 'asset_code', a.asset_code, 'name', a.name,
            'serial_number', a.serial_number, 'condition', a.condition, 'notes', a.notes,
            'version', a.version, 'assignment_id', current_assignment.id,
            'user_id', current_assignment.user_id, 'assigned_at', current_assignment.assigned_at::text,
            'acknowledged_at', current_assignment.acknowledged_at::text, 'assignee_name', u.full_name
         ) FROM assets a
         LEFT JOIN asset_assignments current_assignment ON current_assignment.company_id = a.company_id
             AND current_assignment.asset_id = a.id AND current_assignment.returned_at IS NULL
         LEFT JOIN users u ON u.id = current_assignment.user_id
         WHERE a.company_id = $1 AND ($3 OR current_assignment.user_id = $2)
         ORDER BY a.asset_code LIMIT 500",
    ).bind(context.company_id).bind(context.user_id).bind(admin).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}

pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<SaveAsset>,
) -> Result<Json<Value>, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ASSETS_MANAGE)?;
    if body.asset_code.trim().is_empty()
        || body.asset_code.len() > 80
        || body.name.trim().is_empty()
        || body.name.len() > 200
        || body.serial_number.len() > 200
        || body.notes.len() > 4000
        || !valid_condition(&body.condition)
    {
        return Err(ApiError::BadRequest("invalid asset details"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    let id = body.id.unwrap_or_else(Uuid::now_v7);
    // Serialize code uniqueness checks and asset edits within the tenant.
    sqlx::query("SELECT id FROM companies WHERE id = $1 FOR UPDATE")
        .bind(context.company_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    let duplicate: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM assets WHERE company_id = $1 AND asset_code = $2 AND id <> $3)")
        .bind(context.company_id).bind(body.asset_code.trim()).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if duplicate {
        return Err(ApiError::Conflict("asset code already exists"));
    }
    if body.id.is_some() {
        let affected = sqlx::query("UPDATE assets SET asset_code=$3, name=$4, serial_number=$5, condition=$6, notes=$7, version=version+1, updated_at=now() WHERE company_id=$1 AND id=$2 AND version=$8")
            .bind(context.company_id).bind(id).bind(body.asset_code.trim()).bind(body.name.trim()).bind(body.serial_number.trim()).bind(&body.condition).bind(body.notes.trim()).bind(body.version)
            .execute(&mut *tx).await.map_err(database_error)?.rows_affected();
        if affected == 0 {
            return Err(ApiError::Conflict("asset was changed; reload and retry"));
        }
    } else {
        sqlx::query("INSERT INTO assets(id, company_id, asset_code, name, serial_number, condition, notes) VALUES($1,$2,$3,$4,$5,$6,$7)")
            .bind(id).bind(context.company_id).bind(body.asset_code.trim()).bind(body.name.trim()).bind(body.serial_number.trim()).bind(&body.condition).bind(body.notes.trim())
            .execute(&mut *tx).await.map_err(database_error)?;
    }
    audit(&mut tx, &context, id, "asset.saved").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(Json(json!({"id": id})))
}

pub async fn assign(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<AssignAsset>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ASSETS_MANAGE)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    people::lock_company(&mut tx, context.company_id).await?;
    let condition: String =
        sqlx::query_scalar("SELECT condition FROM assets WHERE company_id=$1 AND id=$2 FOR UPDATE")
            .bind(context.company_id)
            .bind(id)
            .fetch_optional(&mut *tx)
            .await
            .map_err(database_error)?
            .ok_or(ApiError::BadRequest("asset not found"))?;
    if condition != "good" {
        return Err(ApiError::BadRequest(
            "only assets in good condition can be assigned",
        ));
    }
    let member: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM company_memberships m JOIN users u ON u.id=m.user_id WHERE m.company_id=$1 AND m.user_id=$2 AND m.employment_status='active' AND u.status='active')")
        .bind(context.company_id).bind(body.user_id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if !member {
        return Err(ApiError::BadRequest("active employee not found"));
    }
    let occupied: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM asset_assignments WHERE company_id=$1 AND asset_id=$2 AND returned_at IS NULL)")
        .bind(context.company_id).bind(id).fetch_one(&mut *tx).await.map_err(database_error)?;
    if occupied {
        return Err(ApiError::Conflict("asset is already assigned"));
    }
    sqlx::query("INSERT INTO asset_assignments(id, company_id, asset_id, user_id, assigned_by) VALUES($1,$2,$3,$4,$5)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(id).bind(body.user_id).bind(context.user_id).execute(&mut *tx).await.map_err(database_error)?;
    audit(&mut tx, &context, id, "asset.assigned").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn return_asset(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
    Json(body): Json<ReturnAsset>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::ASSETS_MANAGE)?;
    if !valid_condition(&body.condition) || body.note.len() > 4000 {
        return Err(ApiError::BadRequest("invalid return details"));
    }
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    sqlx::query("SELECT id FROM assets WHERE company_id=$1 AND id=$2 FOR UPDATE")
        .bind(context.company_id)
        .bind(id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    let affected = sqlx::query("UPDATE asset_assignments SET returned_at=now(), return_note=$3,return_condition=$4 WHERE company_id=$1 AND asset_id=$2 AND returned_at IS NULL")
        .bind(context.company_id).bind(id).bind(body.note.trim()).bind(&body.condition).execute(&mut *tx).await.map_err(database_error)?.rows_affected();
    if affected == 0 {
        return Err(ApiError::BadRequest("active assignment not found"));
    }
    sqlx::query("UPDATE assets SET condition=$3, version=version+1, updated_at=now() WHERE company_id=$1 AND id=$2")
        .bind(context.company_id).bind(id).bind(&body.condition).execute(&mut *tx).await.map_err(database_error)?;
    audit(&mut tx, &context, id, "asset.returned").await?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}

pub async fn acknowledge(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    let affected = sqlx::query("UPDATE asset_assignments SET acknowledged_at=COALESCE(acknowledged_at, now()) WHERE company_id=$1 AND asset_id=$2 AND user_id=$3 AND returned_at IS NULL")
        .bind(context.company_id).bind(id).bind(context.user_id).execute(&state.pool).await.map_err(database_error)?.rows_affected();
    if affected == 0 {
        return Err(ApiError::BadRequest("active assignment not found"));
    }
    Ok(StatusCode::NO_CONTENT)
}

async fn audit(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    context: &AuthContext,
    id: Uuid,
    event: &str,
) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id) VALUES($1,$2,$3,$4,'asset',$5)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(event).bind(id).execute(&mut **tx).await.map_err(database_error)?;
    Ok(())
}

pub async fn history(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Path(id): Path<Uuid>,
) -> Result<Json<Vec<Value>>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    let admin = context
        .capabilities
        .iter()
        .any(|p| p == access::ASSETS_MANAGE);
    let rows=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('id',a.id,'full_name',u.full_name,'assigned_at',a.assigned_at::text,'acknowledged_at',a.acknowledged_at::text,'returned_at',a.returned_at::text,'return_note',a.return_note,'return_condition',a.return_condition) FROM asset_assignments a JOIN users u ON u.id=a.user_id WHERE a.company_id=$1 AND a.asset_id=$2 AND ($4 OR a.user_id=$3) ORDER BY a.assigned_at DESC,a.id LIMIT 200")
        .bind(context.company_id).bind(id).bind(context.user_id).bind(admin).fetch_all(&state.pool).await.map_err(database_error)?;
    Ok(Json(rows))
}
