//! Capabilities for implemented rewrite endpoints, not legacy permission parity.
pub const EMPLOYEES_VIEW: &str = "admin.employees.view";
pub const EMPLOYEES_MANAGE: &str = "admin.employees.manage";
pub const EMPLOYEES_STATUS: &str = "admin.employees.manage_status";
pub const SHIFTS_MANAGE: &str = "admin.shifts.manage";
pub const SCHEDULES_MANAGE: &str = "admin.schedules.manage";
pub const APPROVALS_VIEW: &str = "requests.approvals.view";
pub const APPROVALS_MANAGER: &str = "requests.approvals.manager";
pub const APPROVALS_ADMIN: &str = "requests.approvals.admin";
pub const DASHBOARD_VIEW: &str = "admin.dashboard.view";
pub const ACCESS_MANAGE: &str = "admin.access.manage";
pub const BARCODES_MANAGE: &str = "admin.barcodes.manage";
pub const APPROVALS_COMPANY: &str = "requests.approvals.company";
pub const WORKSPACE_RECORDS_MANAGE: &str = "admin.workspace_records.manage";
pub const FILES_MANAGE: &str = "admin.files.manage";
pub const ATTENDANCES_VIEW: &str = "admin.attendances.view";
pub const ATTENDANCES_MANAGE: &str = "admin.attendances.manage";
pub const AUDIT_VIEW: &str = "admin.audit.view";
pub const PAYROLL_VIEW: &str = "admin.payroll.view";
pub const PAYROLL_MANAGE: &str = "admin.payroll.manage";
pub const SYSTEM_VIEW: &str = "admin.system.view";
pub const ANNOUNCEMENTS_MANAGE: &str = "admin.announcements.manage";
pub const ASSETS_MANAGE: &str = "admin.assets.manage";
pub const ORGANIZATION_MANAGE: &str = "admin.organization.manage";
pub const PERFORMANCE_MANAGE: &str = "admin.performance.manage";
pub const DOCUMENTS_MANAGE: &str = "admin.documents.manage";
pub const CHECKLISTS_MANAGE: &str = "admin.checklists.manage";
pub const CATALOG: &[&str] = &[
    DASHBOARD_VIEW,
    EMPLOYEES_VIEW,
    EMPLOYEES_MANAGE,
    EMPLOYEES_STATUS,
    SHIFTS_MANAGE,
    SCHEDULES_MANAGE,
    APPROVALS_VIEW,
    APPROVALS_MANAGER,
    APPROVALS_ADMIN,
    APPROVALS_COMPANY,
    BARCODES_MANAGE,
    WORKSPACE_RECORDS_MANAGE,
    FILES_MANAGE,
    ATTENDANCES_VIEW,
    ATTENDANCES_MANAGE,
    AUDIT_VIEW,
    PAYROLL_VIEW,
    PAYROLL_MANAGE,
    SYSTEM_VIEW,
    ANNOUNCEMENTS_MANAGE,
    ASSETS_MANAGE,
    ORGANIZATION_MANAGE,
    PERFORMANCE_MANAGE,
    DOCUMENTS_MANAGE,
    CHECKLISTS_MANAGE,
];

pub fn capabilities(role: &str) -> &'static [&'static str] {
    match role {
        "owner" | "admin" => &[
            DASHBOARD_VIEW,
            EMPLOYEES_VIEW,
            EMPLOYEES_MANAGE,
            EMPLOYEES_STATUS,
            SHIFTS_MANAGE,
            SCHEDULES_MANAGE,
            APPROVALS_VIEW,
            APPROVALS_MANAGER,
            APPROVALS_ADMIN,
            APPROVALS_COMPANY,
            BARCODES_MANAGE,
            WORKSPACE_RECORDS_MANAGE,
            FILES_MANAGE,
            ATTENDANCES_VIEW,
            ATTENDANCES_MANAGE,
            AUDIT_VIEW,
            PAYROLL_VIEW,
            PAYROLL_MANAGE,
            SYSTEM_VIEW,
            ANNOUNCEMENTS_MANAGE,
            ASSETS_MANAGE,
            ORGANIZATION_MANAGE,
            PERFORMANCE_MANAGE,
            DOCUMENTS_MANAGE,
            CHECKLISTS_MANAGE,
        ],
        "manager" => &[APPROVALS_VIEW, APPROVALS_MANAGER],
        _ => &[],
    }
}

#[cfg(test)]
pub fn allows(role: &str, capability: &str) -> bool {
    capabilities(role).contains(&capability)
}

pub fn effective(
    role: &str,
    custom: Option<&[String]>,
    grants: &[String],
    denies: &[String],
) -> Vec<String> {
    if role == "owner" {
        return CATALOG
            .iter()
            .copied()
            .chain([ACCESS_MANAGE])
            .map(str::to_owned)
            .collect();
    }
    let base: Vec<String> = custom
        .map(<[String]>::to_vec)
        .unwrap_or_else(|| capabilities(role).iter().map(|p| (*p).to_owned()).collect());
    let mut result: Vec<String> = base
        .into_iter()
        .chain(grants.iter().cloned())
        .filter(|p| CATALOG.contains(&p.as_str()) && !denies.contains(p))
        .collect();
    result.sort();
    result.dedup();
    result
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn custom_role_replaces_defaults_and_deny_wins() {
        let custom = vec![EMPLOYEES_VIEW.to_owned()];
        let grants = vec![
            EMPLOYEES_MANAGE.to_owned(),
            ACCESS_MANAGE.to_owned(),
            "*".to_owned(),
        ];
        let denies = vec![EMPLOYEES_MANAGE.to_owned()];
        assert_eq!(effective("admin", Some(&custom), &grants, &denies), custom);
        assert!(effective("admin", Some(&[]), &[], &[]).is_empty());
        assert!(
            effective("owner", Some(&[]), &[], &[ACCESS_MANAGE.to_owned()])
                .contains(&ACCESS_MANAGE.to_owned())
        );
    }

    #[test]
    fn employee_and_unknown_roles_have_no_privileged_capabilities() {
        for role in ["employee", "", "superuser", "unknown"] {
            assert!(capabilities(role).is_empty());
            assert!(!allows(role, EMPLOYEES_MANAGE));
        }
    }

    #[test]
    fn manager_cannot_administer_or_make_final_decisions() {
        assert!(allows("manager", APPROVALS_VIEW));
        assert!(allows("manager", APPROVALS_MANAGER));
        assert!(!allows("manager", APPROVALS_ADMIN));
        assert!(!allows("manager", EMPLOYEES_VIEW));
        assert!(!allows("manager", SHIFTS_MANAGE));
    }

    #[test]
    fn administrators_have_only_explicit_implemented_capabilities() {
        for role in ["owner", "admin"] {
            assert!(allows(role, EMPLOYEES_MANAGE));
            assert!(allows(role, BARCODES_MANAGE));
            assert!(allows(role, APPROVALS_ADMIN));
            assert!(allows(role, WORKSPACE_RECORDS_MANAGE));
            assert!(allows(role, FILES_MANAGE));
            assert!(allows(role, ATTENDANCES_VIEW));
            assert!(allows(role, AUDIT_VIEW));
            assert!(allows(role, PAYROLL_VIEW));
            assert!(allows(role, PAYROLL_MANAGE));
            assert!(allows(role, SYSTEM_VIEW));
            assert!(!allows(role, "*"));
        }
        assert!(!allows("manager", BARCODES_MANAGE));
        assert!(!allows("employee", BARCODES_MANAGE));
    }
}
