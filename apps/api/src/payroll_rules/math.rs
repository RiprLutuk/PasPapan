use super::*;

pub fn validate_rules(rules: &[Rule]) -> Result<(), ApiError> {
    for rule in rules {
        let method = rule.method.as_deref().unwrap_or("flat");
        let basis = rule.basis.as_deref().unwrap_or("base_salary");
        if !matches!(method, "flat" | "tier" | "progressive")
            || !matches!(basis, "base_salary" | "gross" | "taxable")
            || rule.kind == "earning" && basis != "base_salary"
            || rule
                .category
                .as_ref()
                .is_some_and(|v| v.is_empty() || v.len() > 20)
        {
            return Err(ApiError::BadRequest(
                "invalid payroll rule method, basis or category",
            ));
        }
        let bands = rule.bands.as_deref().unwrap_or(&[]);
        if method == "flat" {
            if !bands.is_empty() {
                return Err(ApiError::BadRequest("flat rules cannot contain tax bands"));
            }
            continue;
        }
        if bands.is_empty() || bands.len() > 50 {
            return Err(ApiError::BadRequest("tiered rules require 1 to 50 bands"));
        }
        let mut lower = 0;
        for (index, band) in bands.iter().enumerate() {
            if !(0..=10000).contains(&band.rate_bps) {
                return Err(ApiError::BadRequest("invalid band rate"));
            }
            if let Some(upper) = band.up_to {
                if upper <= lower || upper > 999_999_999_999 || index == bands.len() - 1 {
                    return Err(ApiError::BadRequest(
                        "bands must increase and end without a limit",
                    ));
                }
                lower = upper;
            } else if index != bands.len() - 1 {
                return Err(ApiError::BadRequest("only the last band can be unlimited"));
            }
        }
    }
    Ok(())
}

fn amount(rule: &Rule, basis: i64) -> i64 {
    let basis = basis.min(rule.basis_cap.unwrap_or(basis));
    let method = rule.method.as_deref().unwrap_or("flat");
    let mut numerator = 0;
    let mut lower = 0;
    if method == "flat" {
        numerator = basis * rule.rate_bps;
    } else {
        for band in rule.bands.as_deref().unwrap_or(&[]) {
            let upper = band.up_to.unwrap_or(basis);
            if method == "tier" {
                if basis <= upper {
                    numerator = basis * band.rate_bps;
                    break;
                }
            } else {
                numerator += (basis.min(upper) - lower).max(0) * band.rate_bps;
            }
            lower = upper;
        }
    }
    (numerator + 5000) / 10000 + rule.fixed_amount
}

pub fn calculate(input: &Input, rules: &[Rule]) -> Result<(i64, i64, Value), ApiError> {
    validate(rules)?;
    let work = input.work_days.unwrap_or(1);
    let paid = input.paid_days.unwrap_or(work);
    let months = input.months_worked.unwrap_or(0);
    if !(0..=999_999_999).contains(&input.base_salary)
        || !(0..=44640).contains(&input.overtime_minutes)
        || !(0..=999_999).contains(&input.overtime_hourly_amount)
        || !(1..=366).contains(&work)
        || !(0..=work).contains(&paid)
        || !(0..=12).contains(&months)
        || input.work_days.is_some() != input.paid_days.is_some()
        || input.include_thr == Some(true) && input.months_worked.is_none()
        || input
            .tax_category
            .as_ref()
            .is_some_and(|value| value.trim().is_empty() || value.len() > 20)
    {
        return Err(ApiError::BadRequest("invalid payroll calculation input"));
    }
    let category = input.tax_category.as_deref();
    let has_categories = rules.iter().any(|r| r.category.is_some());
    if has_categories
        && (category.is_none() || !rules.iter().any(|r| r.category.as_deref() == category))
    {
        return Err(ApiError::BadRequest(
            "choose a configured payroll tax category",
        ));
    }
    let eligible = |rule: &&Rule| rule.category.is_none() || rule.category.as_deref() == category;
    let base = (input.base_salary * paid + work / 2) / work;
    let overtime = (input.overtime_minutes * input.overtime_hourly_amount + 30) / 60;
    let thr = if input.include_thr.unwrap_or(false) {
        (input.base_salary * months + 6) / 12
    } else {
        0
    };
    let mut gross = base + overtime + thr;
    let mut deduction = 0;
    let mut exempt = 0;
    let mut components = vec![
        json!({"label":"Base salary","kind":"earning","amount":base}),
        json!({"label":"Overtime","kind":"earning","amount":overtime}),
        json!({"label":"THR","kind":"earning","amount":thr}),
    ];
    let adjustments = input.adjustments.as_deref().unwrap_or(&[]);
    if adjustments.len() > 30
        || adjustments.iter().any(|a| {
            a.label.trim().is_empty()
                || a.label.len() > 100
                || !matches!(a.kind.as_str(), "earning" | "deduction")
                || !(0..=999_999_999).contains(&a.amount)
        })
    {
        return Err(ApiError::BadRequest("invalid payroll adjustments"));
    }
    for adjustment in adjustments {
        if adjustment.kind == "earning" {
            gross += adjustment.amount;
            if adjustment.taxable == Some(false) {
                exempt += adjustment.amount;
            }
        } else {
            deduction += adjustment.amount;
        }
        components.push(json!({"label":adjustment.label,"kind":adjustment.kind,"amount":adjustment.amount,"taxable":adjustment.taxable.unwrap_or(true)}));
    }
    for rule in rules
        .iter()
        .filter(eligible)
        .filter(|r| r.kind == "earning")
    {
        let value = amount(rule, base);
        gross += value;
        if rule.taxable == Some(false) {
            exempt += value;
        }
        components.push(json!({"label":rule.label,"kind":rule.kind,"amount":value,"taxable":rule.taxable.unwrap_or(true)}));
    }
    let taxable = gross - exempt;
    for rule in rules
        .iter()
        .filter(eligible)
        .filter(|r| r.kind != "earning")
    {
        let basis = match rule.basis.as_deref().unwrap_or("base_salary") {
            "gross" => gross,
            "taxable" => taxable,
            _ => base,
        };
        let value = amount(rule, basis);
        if rule.kind == "deduction" {
            deduction += value;
        }
        components
            .push(json!({"label":rule.label,"kind":rule.kind,"amount":value,"basis_amount":basis}));
    }
    if deduction > gross {
        return Err(ApiError::BadRequest("deductions exceed earnings"));
    }
    Ok((
        gross,
        deduction,
        json!({"input":input,"rules":rules,"components":components,"taxable_income":taxable,"rounding":"nearest whole IDR, half up"}),
    ))
}

#[cfg(test)]
mod tests {
    use super::*;
    fn tier(method: &str) -> Rule {
        Rule {
            label: "Configured tax".into(),
            kind: "deduction".into(),
            method: Some(method.into()),
            basis: Some("taxable".into()),
            bands: Some(vec![
                Band {
                    up_to: Some(1000),
                    rate_bps: 0,
                },
                Band {
                    up_to: Some(2000),
                    rate_bps: 1000,
                },
                Band {
                    up_to: None,
                    rate_bps: 2000,
                },
            ]),
            ..Default::default()
        }
    }
    #[test]
    fn banded_rates_distinguish_whole_income_from_marginal_tax() {
        assert_eq!(amount(&tier("tier"), 2500), 500);
        assert_eq!(amount(&tier("progressive"), 2500), 200);
        assert_eq!(amount(&tier("tier"), 1000), 0);
        assert_eq!(amount(&tier("tier"), 2000), 200);
    }
    #[test]
    fn prorating_thr_and_exempt_allowances_keep_their_basis() {
        let input = Input {
            policy_version: 1,
            base_salary: 12000,
            work_days: Some(20),
            paid_days: Some(10),
            include_thr: Some(true),
            months_worked: Some(6),
            adjustments: Some(vec![Adjustment {
                label: "Exempt allowance".into(),
                kind: "earning".into(),
                amount: 1000,
                taxable: Some(false),
            }]),
            ..Default::default()
        };
        let (gross, deduction, snapshot) =
            calculate(&input, &[tier("progressive")]).unwrap_or_else(|_| panic!("valid payroll"));
        assert_eq!(gross, 13000);
        assert_eq!(snapshot["taxable_income"], 12000);
        assert_eq!(deduction, 2100);
    }
    #[test]
    fn invalid_bands_and_missing_categories_fail_closed() {
        let mut rule = tier("tier");
        rule.category = Some("A".into());
        let input = Input {
            base_salary: 2500,
            ..Default::default()
        };
        assert!(calculate(&input, &[rule.clone()]).is_err());
        rule.bands.as_mut().unwrap()[0].up_to = Some(3000);
        assert!(validate_rules(&[rule]).is_err());
        assert!(
            calculate(
                &Input {
                    tax_category: Some("x".repeat(21)),
                    ..Default::default()
                },
                &[]
            )
            .is_err()
        );
    }
}
