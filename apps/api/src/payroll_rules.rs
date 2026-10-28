use super::*;
use serde::{Deserialize, Serialize};
use serde_json::{Value, json};
mod math;

#[derive(Clone, Default, Deserialize, Serialize)]
#[serde(deny_unknown_fields)]
pub struct Rule {
    label: String,
    kind: String,
    rate_bps: i64,
    fixed_amount: i64,
    basis_cap: Option<i64>,
    method: Option<String>,
    basis: Option<String>,
    bands: Option<Vec<Band>>,
    category: Option<String>,
    taxable: Option<bool>,
}
#[derive(Clone, Deserialize, Serialize)]
#[serde(deny_unknown_fields)]
pub struct Band {
    up_to: Option<i64>,
    rate_bps: i64,
}
#[derive(Deserialize, Serialize)]
#[serde(deny_unknown_fields)]
pub struct Adjustment {
    label: String,
    kind: String,
    amount: i64,
    taxable: Option<bool>,
}
#[derive(Deserialize)]
#[serde(deny_unknown_fields)]
pub struct Policy {
    version: Option<i64>,
    rules: Vec<Rule>,
}
#[derive(Default, Deserialize, Serialize)]
#[serde(deny_unknown_fields)]
pub struct Input {
    pub policy_version: i64,
    pub base_salary: i64,
    pub overtime_minutes: i64,
    pub overtime_hourly_amount: i64,
    pub work_days: Option<i64>,
    pub paid_days: Option<i64>,
    pub include_thr: Option<bool>,
    pub months_worked: Option<i64>,
    pub tax_category: Option<String>,
    pub adjustments: Option<Vec<Adjustment>>,
}

fn validate(rules: &[Rule]) -> Result<(), ApiError> {
    if rules.len() > 30
        || rules.iter().any(|r| {
            r.label.trim().is_empty()
                || r.label.len() > 100
                || !matches!(r.kind.as_str(), "earning" | "deduction" | "employer")
                || !(0..=10000).contains(&r.rate_bps)
                || !(0..=999_999_999).contains(&r.fixed_amount)
                || r.basis_cap
                    .is_some_and(|v| !(1..=999_999_999_999).contains(&v))
        })
    {
        return Err(ApiError::BadRequest("invalid payroll policy rules"));
    }
    math::validate_rules(rules)
}

fn calculate(input: &Input, rules: &[Rule]) -> Result<(i64, i64, Value), ApiError> {
    math::calculate(input, rules)
}

pub async fn load(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
) -> Result<Json<Value>, ApiError> {
    let context = authenticated_context(&state.pool, &headers).await?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    let policy=sqlx::query_scalar::<_,Value>("SELECT jsonb_build_object('version',version,'rules',rules) FROM payroll_policies WHERE company_id=$1").bind(context.company_id).fetch_optional(&state.pool).await.map_err(database_error)?;
    Ok(Json(policy.unwrap_or(json!({"version":null,"rules":[]}))))
}
pub async fn save(
    State(state): State<AppState>,
    headers: axum::http::HeaderMap,
    Json(body): Json<Policy>,
) -> Result<StatusCode, ApiError> {
    require_trusted_origin(&headers, &state.app_origin)?;
    let context = authenticated_context(&state.pool, &headers).await?;
    integrations::require_owner(&context)?;
    require_capability(&context, access::PAYROLL_MANAGE)?;
    validate(&body.rules)?;
    let mut tx = state.pool.begin().await.map_err(database_error)?;
    sqlx::query("SELECT id FROM companies WHERE id=$1 FOR UPDATE")
        .bind(context.company_id)
        .execute(&mut *tx)
        .await
        .map_err(database_error)?;
    let version =
        sqlx::query_scalar::<_, i64>("SELECT version FROM payroll_policies WHERE company_id=$1")
            .bind(context.company_id)
            .fetch_optional(&mut *tx)
            .await
            .map_err(database_error)?;
    if version != body.version {
        return Err(ApiError::Conflict("payroll policy changed; reload"));
    }
    sqlx::query("INSERT INTO payroll_policies(company_id,rules) VALUES($1,$2) ON CONFLICT(company_id) DO UPDATE SET rules=EXCLUDED.rules,version=payroll_policies.version+1,updated_at=now()")
        .bind(context.company_id).bind(json!(body.rules)).execute(&mut *tx).await.map_err(database_error)?;
    sqlx::query("INSERT INTO audit_events(id,company_id,actor_id,event_type,subject_type,subject_id,metadata) VALUES($1,$2,$3,'payroll.policy_saved','company',$2,$4)")
        .bind(Uuid::now_v7()).bind(context.company_id).bind(context.user_id).bind(json!({"previous_version":version})).execute(&mut *tx).await.map_err(database_error)?;
    tx.commit().await.map_err(database_error)?;
    Ok(StatusCode::NO_CONTENT)
}
pub async fn snapshot(
    tx: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    company: Uuid,
    input: &Input,
) -> Result<(i64, i64, Value), ApiError> {
    let (version, rules) = sqlx::query_as::<_, (i64, Value)>(
        "SELECT version,rules FROM payroll_policies WHERE company_id=$1",
    )
    .bind(company)
    .fetch_optional(&mut **tx)
    .await
    .map_err(database_error)?
    .ok_or(ApiError::Conflict(
        "configure payroll policy before calculating",
    ))?;
    if version != input.policy_version {
        return Err(ApiError::Conflict("payroll policy changed; reload"));
    }
    calculate(
        input,
        &serde_json::from_value::<Vec<Rule>>(rules).map_err(|_| ApiError::Internal)?,
    )
}
#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn configurable_rules_cap_round_and_separate_employer_cost() {
        let input = Input {
            policy_version: 1,
            base_salary: 10_000_000,
            overtime_minutes: 90,
            overtime_hourly_amount: 50_000,
            ..Default::default()
        };
        let rules = vec![
            Rule {
                label: "Employee contribution".into(),
                kind: "deduction".into(),
                rate_bps: 200,
                fixed_amount: 0,
                basis_cap: Some(5_000_000),
                ..Default::default()
            },
            Rule {
                label: "Employer contribution".into(),
                kind: "employer".into(),
                rate_bps: 300,
                fixed_amount: 0,
                basis_cap: None,
                ..Default::default()
            },
        ];
        let (gross, deduction, snapshot) =
            calculate(&input, &rules).unwrap_or_else(|_| panic!("valid rules"));
        assert_eq!(gross, 10_075_000);
        assert_eq!(deduction, 100_000);
        let employer = snapshot["components"]
            .as_array()
            .unwrap()
            .iter()
            .find(|component| component["kind"] == "employer")
            .unwrap();
        assert_eq!(employer["amount"], 300_000);
        assert!(
            calculate(
                &Input {
                    base_salary: -1,
                    ..input
                },
                &rules
            )
            .is_err()
        );
    }
}
