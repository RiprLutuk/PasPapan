# Security Checklist

## Enforced

- Admin routes use auth, verified, admin middleware, and explicit `can` middleware.
- Sensitive downloads route through policies instead of public storage URLs.
- Attendance, payroll, reimbursement, kasbon, assets, and HR checklist policies include company isolation guards.
- Attachment path access validates relative paths and rejects traversal/remote URLs.
- Upload rules use extension, MIME, size, and dangerous double-extension checks for common upload flows.
- Enterprise routes remain behind license gates and RBAC permissions.
- CI security scans run CodeQL for PHP/JavaScript, Semgrep, Gitleaks, and TruffleHog.
- Playwright smoke and APK attendance smoke cover the primary browser/mobile regression path.

## Required Manual Review Before Release

- Verify no `APP_DEBUG=true` in production.
- Verify `secure_tools/`, `.env`, build cache, backups, and generated artifacts are not committed.
- Verify OSS install paths do not require enterprise obfuscator secrets; private enterprise artifacts must be built salted and must decrypt only with `ENTERPRISE_OBFUSCATOR_KEY`.
- Verify local/public disk fallback is disabled for new private attachments where object storage is configured.
- Verify backup restore requires MFA/maintenance permission and a signed backup.

## Test Proof

- `tests/Feature/SecurityIsolationMatrixTest.php`
- `tests/Feature/AuthorizationPoliciesTest.php`
- `tests/Feature/BackupSecurityHardeningTest.php`
- `tests/Feature/SystemEndpointHardeningTest.php`
- `tests/Feature/MyPayslipsTest.php`
- `tests/e2e/main-smoke.spec.ts`
- `apps/web/tests/pwa-browser.mjs` (offline fallback dan larangan cache API/session)
- `scripts/page-screenshot-catalog.mjs`
