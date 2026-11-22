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
| 1 | Operational workspace | `app/Livewire/Admin/OperationalWorkspace.php` | in progress | Projects, tasks, checklists, client/location registry, field visits and private evidence are native; cross-domain reporting remains incremental |
| 1 | Collaboration | `app/Livewire/Admin/CollaborationWorkspace.php`, `app/Livewire/User/CollaborationInbox.php` | in progress | Tenant rooms, members, private attachments, user unread/read inbox, SSE pulse refresh, meeting schedule/join links, and email-safe views exist; mention syntax, persistent broadcast/WebSocket, and browser E2E remain |
| 1 | Custom forms | `app/Livewire/Admin/CustomFormManager.php`, `app/Livewire/User/MyCustomForms.php` | done | Versioned editable schemas, review/state lifecycle, Operations task automation, and owned private upload fields are implemented in API and both admin/user UI |
| 2 | Accounting | `app/Livewire/Admin/AccountingWorkspace.php` | done | Tenant chart of accounts, balanced journals, period close, reconciliation, ledger CSV/PDF, and financial-balance summary UI are native |
| 2 | Reporting/export | `routes/web/admin/reports.php` | done | Attendance/payroll/leave/accounting CSV/PDF and recurring schedules are native; the worker attaches the requested export to scheduled email |
| 3 | Toko POS | `app/Livewire/Admin/TokoPosAddon.Source.php` | in progress | Products, stock, multi-item checkout, discount/tax, refund, and server-generated receipt PDF are native; enterprise add-on enforcement remains |
| 3 | Commercial workspace | `app/Livewire/Admin/CommercialWorkspace.php` | done | Customer, quote/invoice/vendor bill, payments, ledger bridge, approval note, and server-generated document PDF are native |
| 3 | Device/offline API | `routes/api.php` | in progress | Expiring device credential, atomic idempotency cursor, stale-cursor conflict response, and media queue exist; client-side durable offline queue remains |

## Explicitly out of scope until a unit is complete

- `workspace_records` is transitional only. It must not be presented as an equivalent accounting,
  collaboration, operations, form, commercial, or Toko POS implementation.
- No Laravel controller, Livewire component, or Laravel database write is used by the rewrite.

## Current checkpoint

The remaining implementation gaps are intentionally explicit: POS enterprise entitlement enforcement,
durable client-side offline queue, true broadcast realtime/mentions, browser E2E, and platform-backed
Android/iOS attestation. The last item requires registered mobile applications and vendor credentials;
it must not be simulated by a browser-provided flag.
