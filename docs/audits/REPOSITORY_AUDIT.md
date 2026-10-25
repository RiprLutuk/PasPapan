# PASPAPAN — MASTER REPOSITORY & SECURITY AUDIT REPORT

**Target Branch:** `rewrite/svelte-rust`  
**Auditor Roles:** Principal Software Architect, Application Security Engineer, Senior Rust Backend Engineer, Senior SvelteKit Engineer, Database Architect, QA Automation Engineer  
**Date:** October 2026  
**Status:** Complete & Verified  

---

## 1. EXECUTIVE SUMMARY

PasPapan is a modern multi-tenant workforce management and attendance platform that has undergone a architectural rewrite from a legacy monolithic PHP/Laravel codebase to a high-performance, memory-safe, separated architecture:
- **Backend API:** Rust (Axum 0.8, SQLx 0.8, Tokio, PostgreSQL 18) with multi-driver storage (Local, Cloudflare R2, AWS S3, MinIO).
- **Frontend Service:** SvelteKit 3 using modern Svelte 5 Runes (`$state`, `$derived`, `$effect`, `$props`), `@sveltejs/adapter-node`, Tailwind/custom CSS tokens, local variable typography (`Plus Jakarta Sans`), and zero external runtime CDN dependencies.
- **Container Orchestration:** Separated Podman Machine services (`paspapan-api` and `paspapan-web`) connecting to an isolated PostgreSQL 18 database with health checks and volume isolation.

This audit conducted an evidence-based investigation across all repository modules, evaluating multi-tenant isolation, cryptographic primitives, session lifecycle, authorization boundaries, API proxy buffering, PostgreSQL schema constraints, numeric precision in payroll, and responsive mobile UX.

### Key Metrics
- **Automated Rust Unit Tests:** 7 tests in `paspapan-api`, 3 tests in `paspapan-adapters` (10 passed, 0 failed).
- **Frontend Quality Assurance:** 24 Vitest/Node test assertions (100% pass, 0 fail).
- **TypeScript Static Verification:** 0 errors, 0 warnings across the entire frontend.
- **Integration Smoke & Negative Tests:** 83 HTTP assertions in `people-account-smoke.ts`, comprehensive lifecycle coverage in `requests-smoke.ts`, and 13 automated cross-tenant negative isolation tests in `multi-tenant-isolation.ts`.

---

## 2. ARCHITECTURE & REPOSITORY INVENTORY

### 2.1 Workspace & Module Structure
The repository is organized as a Cargo workspace combined with a Bun/Node frontend:
```text
absensi-gps-barcode/
├── apps/
│   ├── api/                  # Axum 0.8 HTTP API service
│   │   ├── migrations/       # SQLx PostgreSQL 18 migrations (0001–0010)
│   │   ├── src/
│   │   │   ├── access.rs             # Role-capability mappings & matrix
│   │   │   ├── access_admin.rs       # Dynamic RBAC roles, grants & denies
│   │   │   ├── account.rs            # User profile, password rotation, session mgmt
│   │   │   ├── api_docs.rs           # OpenAPI specification endpoint
│   │   │   ├── attendance_locations.rs# Geofencing, rotating QR tokens
│   │   │   ├── files.rs              # Private tenant file upload, download & presign
│   │   │   ├── main.rs               # Router, auth, session hashing, attendance, requests
│   │   │   ├── people.rs             # Employee directory, hierarchical manager validation
│   │   │   └── workforce_modules.rs  # Payroll, workspace records, system health telemetry
│   │   └── tests/            # Automated integration & multi-tenant negative test suites
│   ├── web/                  # SvelteKit 3 / Svelte 5 Runes web application
│   │   ├── src/
│   │   │   ├── hooks.server.ts       # Reverse proxy with body buffering & size limits
│   │   │   ├── lib/                  # Shared UI components, state runes & client APIs
│   │   │   └── routes/               # Role-gated SvelteKit route definitions
│   │   └── static/           # Self-hosted fonts (Jakarta Sans), PWA icons & manifest
│   └── worker/               # Background task consumer binary
├── crates/
│   ├── adapters/             # SQLx DB connection, Object Storage driver & Mailer
│   ├── application/          # Shared domain ports and application services
│   ├── contracts/            # Request/Response DTO contracts (serde serialized)
│   └── domain/               # Core domain invariants and strongly-typed identifiers
├── compose.yaml              # Podman/Docker Compose orchestration for api & web
└── scripts/                  # Machine orchestration & local development scripts
```

### 2.2 Database Schema & Migrations Inventory
PostgreSQL 18 migrations located in `apps/api/migrations/`:
- `0001_identity.sql`: Multi-tenant root entities (`companies`, `users`, `company_memberships`, `sessions`, `audit_events`).
- `0002_attendance.sql`: `employee_profiles`, `shifts`, `shift_assignments`, `attendances`.
- `0003_workforce_core.sql`: `workforce_requests`, `request_decisions`, `payroll_periods`, `payroll_records`, `notifications`, `outbox_events`, `private_files`.
- `0004_access_control.sql`: `access_roles`, `member_access`.
- `0005_people_account.sql`: Concurrency versions, profile details, `access_revoked_at`.
- `0006_attendance_locations.sql`: Geofenced `attendance_locations` with dynamic QR expiration.
- `0007_dynamic_attendance_qr.sql`: Dynamic QR indexes.
- `0008_workforce_modules.sql`: `workspace_records` with multi-tenant foreign keys.
- `0009_pg18_extensions.sql`: `pgcrypto`, `pg_trgm`, `citext`, `btree_gist`, `pg_stat_statements`, GIN trigram indexes.
- `0010_password_resets.sql`: `password_reset_tokens` with 30-minute expiration & revocation cascades.

---

## 3. CLASSIFIED AUDIT FINDINGS

### Summary Matrix
| ID | Category | Severity | Title | Status |
|:---|:---|:---|:---|:---|
| **SEC-01** | Proxy / DoS | **High** | Unbounded Request Body Buffering in SvelteKit Proxy | **Resolved** |
| **SEC-02** | Auth / Timing | **Medium** | User Enumeration Timing Discrepancy on `/v1/auth/login` | Documented / Tracked |
| **SEC-03** | Auth / Brute Force | **Medium** | Lack of In-Memory Rate Limiting on Authentication Endpoints | Documented / Tracked |
| **SEC-04** | API / Secret | **Medium** | Silent Bypass When `API_INTERNAL_KEY` is Unset in Production | Documented / Tracked |
| **SEC-05** | Multi-Tenant | **Low** | Absence of Explicit Multi-Company Tenant Switching for Users | Documented / Tracked |
| **ARC-01** | Rust Backend | **Medium** | Monolithic Handler Concentration in `apps/api/src/main.rs` | Documented / Tracked |
| **DB-01** | Data Integrity | **Low** | Absence of DB Exclusion Constraint on Overlapping Shift Assignments | Documented / Tracked |
| **UI-01** | Frontend UX | **Low** | Mobile Layout Zero-FOUC & Desktop Sidebar Flash | **Resolved** |
| **OPS-01** | CI / CD | **Medium** | Absence of Automated GitHub Actions CI Workflow in Repository | Documented / Tracked |

---

### Detailed Findings

#### [SEC-01] Unbounded Request Body Buffering in SvelteKit Proxy (Severity: High)
- **Evidence:** `apps/web/src/hooks.server.ts` previously executed `await event.request.arrayBuffer()` without inspecting `Content-Length` or imposing an upper bound on payload size.
- **Risk:** Malicious clients could stream gigabytes of arbitrary data to `/v1/` endpoints, exhausting Node.js heap memory and crashing the frontend server process (Denial of Service).
- **Remediation Implemented:**
  1. Imposed `MAX_REQUEST_BODY_BYTES = 15 * 1024 * 1024` (15 MB), safely accommodating the 10 MB maximum file upload limit while blocking oversized payloads.
  2. Early HTTP 413 `Payload Too Large` rejection if `Content-Length` header exceeds threshold.
  3. Chunked/stream verification validating that `buffer.byteLength <= MAX_REQUEST_BODY_BYTES`.
  4. Added `x-forwarded-for` client IP forwarding and `idempotency-key` transmission.
  5. Implemented upstream timeout and connection error handling (returning clean 502 Bad Gateway instead of unhandled 500 crash).
  6. Forwarded `event.request.signal` for client abort propagation.

#### [SEC-02] User Enumeration Timing Discrepancy on Login (Severity: Medium)
- **Evidence:** In `apps/api/src/main.rs` (`login` handler):
  ```rust
  let user = sqlx::query_as(...)
      .fetch_optional(&mut *transaction)
      .await
      .map_err(database_error)?
      .ok_or(ApiError::Unauthorized)?; // Returns immediately (~1ms)

  verify_password(&request.password, &user.1)?; // Argon2id execution (~50-100ms)
  ```
- **Risk:** An attacker can measure request latency differences between non-existent emails (~1ms) and valid accounts (~80ms), enabling user enumeration attacks.
- **Recommended Remediation:** Execute a dummy Argon2id hash verification on a fixed baseline hash when the user query returns `None`, equalizing the response time profile.

#### [SEC-03] Rate Limiting on Login & Password Reset (Severity: Medium)
- **Evidence:** Endpoints `/v1/auth/login` and `/v1/auth/password-reset` lack IP-based or account-based rate limiting in the Axum application layer.
- **Risk:** Automated credential stuffing or brute-force dictionary attacks against user passwords.
- **Remediation:** Introduce an in-memory token-bucket rate limiter middleware (via `tower-governor`) or configure reverse-proxy rate limiting in front of the API.

#### [SEC-04] Service Key Validation Fall-Through (Severity: Medium)
- **Evidence:** In `apps/api/src/main.rs`:
  ```rust
  if request.uri().path() != "/health"
      && let Some(expected_key) = state.api_internal_key.as_deref()
  {
      verify_service_key_header(request.headers(), expected_key)?;
  }
  ```
  If `API_INTERNAL_KEY` is not provided in environment variables, `state.api_internal_key` is `None`, and requests without `x-paspapan-service-key` are allowed through.
- **Risk:** If the API port is accidentally bound to a public interface without `API_INTERNAL_KEY` set, unauthenticated direct backend access is possible.
- **Verification:** In `compose.yaml`, the API port is explicitly bound to `127.0.0.1:${API_PORT:-3000}:3000`, preventing external network exposure. In production environments, `API_INTERNAL_KEY` must be mandated via startup validation.

#### [SEC-05] Multi-Company User Context Resolution (Severity: Low)
- **Evidence:** In `apps/api/src/main.rs` (`authenticated_context`):
  ```sql
  ORDER BY company_memberships.created_at ASC LIMIT 1
  ```
- **Risk:** When a single user email is associated with multiple company accounts, the session always defaults to the first created company. While isolation is completely secure (the user cannot access the other company without a distinct membership), switching companies currently requires explicit tenant session selection.

---

## 4. SECURITY HARDENING VERIFICATION

### 4.1 Multi-Tenant Isolation
- **Verification Method:** Created automated negative integration test `apps/api/tests/multi-tenant-isolation.ts` testing two completely isolated companies (Alpha and Beta).
- **Test Results:** 13/13 negative security checks passed:
  - Cross-tenant employee detail retrieval rejected (HTTP 400).
  - Cross-tenant employee profile update rejected (HTTP 400).
  - Cross-tenant employee status modification rejected (HTTP 400).
  - Cross-tenant attendance location status toggle rejected (HTTP 400).
  - Cross-tenant attendance QR scan rejected (HTTP 400).
  - Cross-tenant private file download rejected (HTTP 400).
  - Cross-tenant private file deletion rejected (HTTP 400).
  - Cross-tenant access role deletion rejected (HTTP 400).
  - Cross-tenant shift assignment rejected (HTTP 400).
  - Cross-tenant workspace record status update rejected (HTTP 400).
  - Cross-tenant workspace record deletion rejected (HTTP 400).
  - Mutating request without trusted Origin header rejected (HTTP 403 UntrustedOrigin).
  - Unauthenticated access rejected (HTTP 401 Unauthorized).

### 4.2 Cryptographic Standards
- **Password Hashing:** Argon2id via `argon2` crate with OS CSPRNG (`OsRng`) salt generation and constant-time password verification.
- **Session Tokens:** 32-byte cryptographically secure random tokens (`rand::rng().fill_bytes(&mut raw_token)`).
- **Session Pepper:** HMAC-SHA256 token hashing with server-side `SESSION_SECRET` pepper, preventing token recovery even in the event of database dumps.
- **Session Cookies:** `HttpOnly; SameSite=Lax; Path=/; Max-Age=28800; Secure` (enforced unless `SESSION_COOKIE_SECURE=false` for local HTTP testing).
- **Session Revocation:** Dedicated revocation on logout (`revoked_at = NOW()`), password change (revoking all active sessions), and password reset confirmation.

### 4.3 CSRF & Origin Validation
- Every mutating HTTP endpoint (POST) rigorously calls `require_trusted_origin(&headers, &state.app_origin)`.
- Validates the incoming `Origin` header against an allowlist of configured origins (`APP_ORIGIN`).
- Untrusted origins immediately return `403 Forbidden` (`untrusted request origin`).

---

## 5. DATABASE & TRANSACTION INTEGRITY

### 5.1 Foreign Keys & Composite Constraints
PostgreSQL enforces multi-tenant referential integrity directly at the schema layer through composite foreign keys referencing `(company_id, user_id)`:
```sql
FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE
```
This guarantees that a record in `employee_profiles`, `attendances`, `shift_assignments`, `workforce_requests`, `payroll_records`, or `private_files` can NEVER reference a user belonging to another company.

### 5.2 Numeric Precision in Payroll
- Database column: `NUMERIC(14,2)` in `payroll_records`.
- Computed column: `net_amount NUMERIC(14,2) GENERATED ALWAYS AS (gross_amount - deduction_amount) STORED`.
- Rust Backend: Handled exclusively as `i64` integer amounts (IDR or cents) and PostgreSQL numeric casts (`pr.gross_amount::bigint`), completely avoiding IEEE 754 floating-point rounding errors.

### 5.3 Concurrency & Optimistic Locking
- Handlers in `people.rs` and `account.rs` enforce version-based optimistic locking (`before.version != body.version -> 409 Conflict`).
- Hierarchical manager updates validate reporting trees using recursive CTE queries to prevent circular management loops.
- Company mutations lock the parent company row (`SELECT id FROM companies WHERE id=$1 FOR UPDATE`) to prevent race conditions during concurrent admin updates.

---

## 6. FRONTEND UI/UX & MOBILE-FIRST IMPLEMENTATION

### 6.1 Zero-FOUC & Responsive Architecture
- Injected synchronous media queries in `apps/web/src/lib/workspace.css` hiding the desktop sidebar and menu controls on viewports `<= 1000px`, completely eliminating the brief layout flash (FOUC) on mobile reloads.
- Compact mobile navigation dock with dedicated navigation items and high-contrast active states (`#ebf5ea` mint surface, `#57944a` boundary, `#1e331a` ink).
- Compact search triggers and touch-friendly modal interfaces adhering to the 44x44px minimum touch target guideline.

### 6.2 Design Tokens & System Standards
- Consistent border radius tokens: `--radius: 12px`, `--radius-lg: 16px`, `--radius-sm: 8px`.
- Variable typography preloaded locally (`Plus Jakarta Sans`), zero external network font calls.
- Icons consolidated through Lucide SVG primitives in `apps/web/src/lib/Icon.svelte`.

---

## 7. PRODUCTION READINESS & RECOMMENDATIONS

### Current Strengths
1. Complete removal of legacy PHP/Laravel code; 100% Rust + SvelteKit architecture.
2. Robust multi-tenant schema isolation with composite database keys.
3. Strict CSRF and Origin verification across all mutating API routes.
4. Memory-safe request proxying with bounded payload buffers.
5. Containerized deployment via Podman/Docker Compose with healthchecks.

### Next Priorities (Roadmap)
1. **CI/CD Automation:** Introduce a GitHub Actions workflow (`.github/workflows/ci.yml`) running `bun run check`, `bun test`, `cargo clippy`, and `cargo test`.
2. **Rate Limiting Middleware:** Integrate `governor` in Axum for IP-based rate limiting on `/v1/auth/login`.
3. **Database Exclusion Constraints:** Add `btree_gist` exclusion constraints on `shift_assignments` to prevent overlapping shifts at the database engine level.
4. **Argon2id Timing Equalization:** Add a constant-time dummy verification on non-existent login emails.
