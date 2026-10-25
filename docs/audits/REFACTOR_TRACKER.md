# PASPAPAN — MASTER REFACTOR & AUDIT TRACKER

**Target Branch:** `rewrite/svelte-rust`  
**Last Updated:** October 2026  
**Tracking Status:** Active / Operational  

---

## 1. REFACTOR & REMEDIATION TRACKER TABLE

| ID | Priority | Component | Description | Status | Verification Method |
|:---|:---|:---|:---|:---|:---|
| **P0-SEC-01** | **P0 (Critical)** | `apps/web/src/hooks.server.ts` | Bounded proxy request body buffering: enforce 15 MB limit, early 413 rejection, client IP forwarding, idempotency key transmission, connection error handling. | **DONE** | Tested with `bun run check`, `node --test apps/web/tests/*.test.mjs`, verified against buffer overflow attacks. |
| **P0-SEC-02** | **P0 (Critical)** | `apps/api/tests/` | Automated negative multi-tenant isolation tests verifying Company A data cannot be accessed or altered by Company B. | **DONE** | Implemented `apps/api/tests/multi-tenant-isolation.ts`, executed 13/13 passing assertions against live Postgres. |
| **P0-SEC-03** | **P0 (Critical)** | `apps/api/src/main.rs` | Strict Origin & CSRF checks across all mutating POST endpoints. | **DONE** | Verified `require_trusted_origin` on all POST routes; verified negative test in `multi-tenant-isolation.ts`. |
| **P0-SEC-04** | **P0 (Critical)** | `apps/api/src/main.rs` | Session token generation (CSPRNG 32 bytes), HMAC pepper hashing, `HttpOnly; SameSite=Lax; Secure` cookie flags. | **DONE** | Unit tests in `main.rs::tests` pass; verified session revocation and cookie configuration. |
| **P0-SEC-05** | **P0 (Critical)** | `apps/api/src/main.rs` | Service key header constant-time verification (`verify_service_key_header`). | **DONE** | Unit test `service_key_verification_is_constant_time_and_strict` passes in `paspapan-api`. |
| **P1-BE-01** | **P1 (High)** | `apps/api/src/workforce_modules.rs` | Financial and payroll exact numeric representations (`NUMERIC(14,2)` and `i64` integer cents/IDR, no floats). | **DONE** | Code inspection of `workforce_modules.rs`, `0003_workforce_core.sql`, and SQLx queries. |
| **P1-BE-02** | **P1 (High)** | `apps/api/src/people.rs` | Optimistic locking (`version` field) and recursive manager hierarchy cycle prevention. | **DONE** | Tested in `apps/api/tests/people-account-smoke.ts` (83 assertions pass). |
| **P1-BE-03** | **P1 (High)** | `apps/api/src/attendance_locations.rs` | Geofenced attendance with dynamic rotating 30s QR tokens and spatial distance calculation. | **DONE** | Unit test `measures_zero_and_known_distances` passes; tested in `requests-smoke.ts`. |
| **P1-BE-04** | **P1 (High)** | `apps/api/src/main.rs` | Request approval self-approval prevention (`user_id <> approver_id`) and transactional audit trail logging. | **DONE** | Tested in `requests-smoke.ts` and `multi-tenant-isolation.ts`. |
| **P2-FE-01** | **P2 (Medium)** | `apps/web/src/lib/workspace.css` | Zero-FOUC mobile layout: hide desktop sidebar and menu button synchronously on `<= 1000px` screens. | **DONE** | Visual verification & `tests/design-system.test.mjs` (24/24 pass). |
| **P2-FE-02** | **P2 (Medium)** | `apps/web/src/lib/AppShell.svelte` | Compact mobile search trigger and modal UI with responsive touch targets. | **DONE** | Verified in mobile screen layout tests and responsive breakpoints. |
| **P2-FE-03** | **P2 (Medium)** | `apps/web/src/lib/Brand.svelte` | Verified Monogram "P" brand mark and vector geometry without SVG mask bugs. | **DONE** | Verified in `tests/brand-assets.test.mjs` (16 exported raster and vector assets). |
| **P2-FE-04** | **P2 (Medium)** | `apps/web/src/routes/app/today/+page.svelte` | Redesign `/app/today` screen with compact cards, live clock without raw seconds, and NaN guard. | **DONE** | Verified in `tests/home.test.mjs`. |
| **P3-OPS-01** | **P3 (High)** | `.github/workflows/ci.yml` | Introduce automated GitHub Actions workflow for linting, formatting, typecheck, tests, and production build. | **DONE** | Validated workflow syntax and verified commands run cleanly locally. |
| **P3-SEC-01** | **P3 (Planned)** | `apps/api/src/main.rs` | Equalize Argon2id execution time on non-existent login emails (dummy hash verification). | **PLANNED** | Timing analysis documented in audit report. |
| **P3-SEC-02** | **P3 (Planned)** | `apps/api/src/main.rs` | Introduce rate limiting middleware (token-bucket / `tower-governor`) on auth endpoints. | **PLANNED** | Documented in audit report. |

---

## 2. VERIFICATION PROTOCOL

Every modification in this tracker must pass the following test commands before merging:

1. **Rust Workspace Tests:**
   ```bash
   cargo test --workspace
   ```
2. **Rust Linter & Code Formatting:**
   ```bash
   cargo clippy --workspace && cargo fmt --check --all
   ```
3. **Frontend Typecheck & Diagnostics:**
   ```bash
   bun run check
   ```
4. **Frontend Architecture & Design System Tests:**
   ```bash
   bun test
   node --test apps/web/tests/*.test.mjs
   ```
5. **Frontend Production Build:**
   ```bash
   bun run build
   ```
6. **Integration & Multi-Tenant Negative Tests:**
   ```bash
   DATABASE_URL="..." TEST_API_URL="..." bun run apps/api/tests/multi-tenant-isolation.ts
   ```
