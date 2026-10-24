# Rewrite feature register

This register is the cutover contract. Every legacy capability requires a Rust + Svelte replacement before final cutover.

| Legacy area | Rewrite decision | Replacement |
| --- | --- | --- |
| Login, groups, roles, sessions | Keep, simplify | fixed owner/admin/manager/employee roles and server sessions |
| Employee directory, division/job title/education | Keep, simplify | employee profile, manager, employee code; free-text team/job fields later |
| Shift, schedule, barcode, GPS attendance | Keep, simplify | shift, dated assignment, GPS check-in/out; QR follows after location flow is stable |
| Leave, overtime, attendance correction | Keep, unify | one request record with kind and sequential manager/admin decision |
| Attendance reports/import/export | Keep, simplify | date filters plus CSV import/export |
| Payroll, payslip, reimbursement | Keep, simplify | monthly payroll record and read-only payslip; reimbursement becomes request kind |
| Notifications, activity logs | Keep | in-app inbox and immutable audit events/outbox |
| Files and generated employee documents | Keep, simplify | private object storage and signed download |
| Company/branch/settings | Keep, simplify | company settings and branch/location only |
| Face/liveness/anti-mock/offline engine | Keep | device-attendance evidence module with explicit privacy controls |
| Shift swap, WFH, HR tasks/checklists | Keep | request, task, and checklist modules |
| KPI/appraisal/assets/custom forms | Keep | people-performance, asset, and form modules |
| POS, CRM, operations, collaboration, accounting | Keep | commercial, operations, collaboration, and finance bounded contexts |
| Enterprise licensing, obfuscation, backup UI | Keep | operations/admin capability, separated from core product runtime |

## Completion gates

1. Every kept row has a Rust API, Svelte screen, role test, audit event, and migration/export plan.
2. No Laravel route is removed until its Svelte replacement and data migration gate pass.
3. No Svelte route calls Laravel. No Rust table shares the Laravel database.
