# PasPapan migration map

Laravel is read-only evidence. The production target is `apps/api` (Axum), `apps/worker`, and
`apps/web` (SvelteKit). A unit is done only when its Rust/Svelte workflow is implemented and the
corresponding API and web checks pass.

## Completed foundation

- Authentication, tenant sessions, RBAC, employee directory, attendance, shift scheduling,
  requests/approvals, leave, payroll, announcements, assets, organization, performance, HR
  documents/checklists, provider verification, mail, storage, and audit trail.
- Legacy URLs with a concrete rewrite destination redirect server-side to that destination.
- `admin/activity-logs` is a native Svelte workspace backed by `GET /v1/admin/activity-logs`.

## Open units

| Wave | Unit | Legacy evidence | State | Completion proof |
| --- | --- | --- | --- | --- |
| 1 | User import/export | `routes/web/admin/import-export.php` | done | Typed CSV preview, dry-run, atomic Rust import, audit/outbox, API + UI checks |
| 1 | Operational workspace | `app/Livewire/Admin/OperationalWorkspace.php` | in progress | Projects, tasks, checklists, client/location registry, field visits and private evidence are native; reporting integration remains |
| 1 | Collaboration | `app/Livewire/Admin/CollaborationWorkspace.php`, `app/Livewire/User/CollaborationInbox.php` | in progress | Tenant rooms, members, messages, private file reference, inbox/unread API and polling exist; SSE/WebSocket, meeting, mention and integration tests remain |
| 1 | Custom forms | `app/Livewire/Admin/CustomFormManager.php`, `app/Livewire/User/MyCustomForms.php` | in progress | Versioned schemas, editable templates, submissions, review, state lifecycle and task automation exist; verified file fields and user-facing schema editor remain |
| 2 | Accounting | `app/Livewire/Admin/AccountingWorkspace.php` | in progress | Tenant-scoped accounts, balanced manual journals, periods, reconciliation and native ledger workspace exist; financial reports/export remain |
| 2 | Reporting/export | `routes/web/admin/reports.php` | in progress | Native attendance CSV endpoint exists; payroll, leave, accounting CSV/PDF and scheduling remain |
| 3 | Toko POS | `app/Livewire/Admin/TokoPosAddon.Source.php` | in progress | Native product, stock, multi-item sale, refund and browser receipt rendering exist; add-on entitlement, tax/discount and durable PDF receipt remain |
| 3 | Commercial workspace | `app/Livewire/Admin/CommercialWorkspace.php` | in progress | Customer, quote/invoice/vendor-bill, line items, payment, ledger mapping and automatic journal bridge exist; document PDF and approval workflow remain |
| 3 | Device/offline API | `routes/api.php` | in progress | User-bound device registration and idempotent mutation cursor exist; signed device credentials, conflict policy and media queue remain |

## Explicitly out of scope until a unit is complete

- `workspace_records` is transitional only. It must not be presented as an equivalent accounting,
  collaboration, operations, form, commercial, or Toko POS implementation.
- No Laravel controller, Livewire component, or Laravel database write is used by the rewrite.

## Current checkpoint

The next batch closes the remaining workflow gaps: report/export/PDF scheduling, POS entitlement
and tax/discount, file-backed form fields, collaboration realtime/meeting, and device conflict/media sync.
