# Local rewrite status

This is an implementation checkpoint, not a claim of full parity.

## Implemented

- Authentication, current-session lookup, logout with session revocation, GPS check-in/out.
- Admin employee listing/creation with direct-manager assignment, shift creation and dated assignments.
- Request submission and personal history for the four request kinds.
- Approval inbox and sequential manager/admin decisions. Managers are restricted
  to direct reports; owners/admins can act as fallback. Self-approval is forbidden.
- Request creation audit; decision, audit and requester notification commit atomically.
- Personal schedule, attendance history and notification read actions.
- Svelte routes: login, today, admin attendance, requests, approvals, schedule,
  history and notifications.
- Date validation rejects invalid calendar dates and reversed request ranges.
- Compact Svelte workspace: shared desktop sidebar, mobile bottom navigation,
  role-aware team menus, session loading/retry/expired states and restricted-page UI.
- Home adopts the existing Laravel attendance timeline, shift strip and quick-access
  layout. Existing primary palette (#44733a / #e2f0df) is reused across login and CMS.
- Employee search, request search/status filtering, dense list rows and shared form tokens.
- Browser/PWA delivery: manifest, proper-size launcher icons, production service
  worker and a public offline page. API/session responses and authenticated HTML
  are never cached by the rewrite worker; offline mutations are not supported.
- Shared brand assets: editable SVG master in `assets/brand`, generated web/PWA,
  legacy email/PDF aliases, favicon and share-card exports. See the brand README.

## Verification

API integration smoke uses the real local PostgreSQL database with isolated fixtures:

```sh
set -a; source .env.rewrite; set +a
cargo run -p paspapan-api --bin migrate
API_BIND=127.0.0.1:4301 cargo run -p paspapan-api --bin paspapan-api
# Another terminal, with the same environment:
bun apps/api/tests/requests-smoke.ts
```

The script checks approval ordering, manager scope, self-approval, tenant isolation,
CSRF, invalid dates, audit counts and notification ownership, and removes its fixtures.

Browser behavior smoke uses API fixtures (not a substitute for the integration test):

```sh
cd apps/web
bun run dev --port 4273
# From the repository root in another terminal, using root Playwright dependency:
bunx playwright install chromium
node apps/web/tests/requests-browser.mjs
node apps/web/tests/workspace-browser.mjs
```

PWA checks need a production build (the worker is deliberately disabled during HMR):

```sh
cd apps/web
bun run build
bun run preview --host 127.0.0.1 --port 4274
# Another terminal from the repository root:
node apps/web/tests/pwa-browser.mjs
bun run brand:check
```

Workspace browser coverage uses deterministic API fixtures: GPS check-in/out,
employee filtering, shift creation/assignment, role-specific navigation, session
errors/retry, login/logout and eight authenticated routes at widths 320/390/768/1440.
Screenshots are generated in a temporary directory printed by the script.
These browser checks do not claim live database end-to-end coverage.

UI reference sources: `resources/views/pages/home.blade.php`,
`resources/views/components/user/home-actions-card.blade.php`,
`resources/views/components/user/app-bottom-navigation.blade.php`,
`resources/views/auth/login.blade.php` and `resources/css/app.css`.
Only implemented destinations are shown: scan, tasks and profile still need parity,
so the current mobile dock uses history, requests and notifications in their place.

## Not implemented / incomplete

- Employee editing/deactivation, password recovery and login throttling.
- Remaining legacy UI parity: barcode scan, tasks/profile screens, dark mode,
  language switching, offline transaction sync and the broader admin modules below.
- Request attachment handling, reimbursement amounts, leave balances and approved
  request effects on attendance/payroll. Current decisions only update request state.
- Payroll, private file delivery and outbox processing: tables only.
- Full legacy route parity: commercial/POS, accounting, collaboration, custom forms,
  performance/assets, enterprise, reports and operational administration.
- Laravel data ETL, reconciliation, deployment packaging and cutover.
- Collection pagination beyond the current bounded recent lists.
- Multi-company selection and overnight attendance rules.

## Retired on this branch

The user authorized removing the Capacitor delivery path. Removed Android/iOS
projects, shell web directory, native plugins/bridges, build/screenshot/smoke scripts
and native CI jobs. Browser geolocation and both PWAs remain. The native-only
`/__e2e-document-upload` Laravel test helper was also removed; the hardened E2E login
helper stays because existing browser tests still use it.

The demo fixture script named `prepare-apk-screenshots-demo.php` is intentionally
retained: despite its old name, desktop screenshots and browser CI consume it.

No core Laravel module has yet passed the complete parity + data-migration gates.
Do not delete auth, requests, payroll, barcode, files or their shared models merely
because a subset exists in Rust. Removal of fully migrated modules is authorized,
but passing the current smoke tests alone is not full cutover evidence.

The user also removed `.github` and `.agents` separately and confirmed that removal.
Local verification below must not be interpreted as evidence of an active CI pipeline.

## Cleanup / branding verification

- Laravel legacy frontend build and Svelte check/production build.
- 37 focused PHP regressions: stack guard, wrapper retirement, endpoint hardening,
  upload markup and device attendance/media authorization.
- `node --test tests/browser-location.test.mjs` for retained browser geolocation.
- Brand asset tests verify SVG consistency, favicon/PNG dimensions and legacy aliases.
- Workspace/request browser fixtures and production PWA offline/cache checks.
