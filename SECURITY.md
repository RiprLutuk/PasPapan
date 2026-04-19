# Security Policy

PasPapan handles operational HR and workforce data, including attendance records, employee information, reimbursements, payroll-related workflows, attachments, and administrative controls. Security issues should be reported responsibly and handled privately until triage is complete.

## Supported Versions

Security support is focused on the latest maintained code line.

| Version Scope | Status |
| --- | --- |
| latest `main` / latest maintained release | Supported |
| older unmaintained snapshots | Not supported |

If you are running a fork or a heavily modified private deployment, validate fixes against your own changes before assuming compatibility.

## Reporting a Vulnerability

Do not open a public GitHub issue for a security vulnerability.

### Contact

Report vulnerabilities privately to:

- Email: `rizqy.pra85@gmail.com`
- Subject format: `[SECURITY] PasPapan - short summary`

### Include in the report

Please include as much of the following as possible:

- affected module, route, command, or workflow
- vulnerability type
- exact reproduction steps
- proof of concept or minimal exploit path
- impact assessment
- environment assumptions
- suggested mitigation, if you have one

Useful examples:

- authorization bypass
- data exposure
- insecure direct object reference
- file access bypass
- queue or scheduler abuse path
- backup download or restore misuse
- unsafe attachment access
- secret leakage

## Response Expectations

Target response windows:

- acknowledgment: within 48 hours
- initial triage: within 5 business days
- remediation timing: depends on severity, blast radius, and release coordination

These timelines are targets, not guarantees.

## Coordinated Disclosure

Please give us reasonable time to:

- confirm the issue
- assess severity
- prepare a fix
- validate regression risk
- coordinate release notes where appropriate

If you want public credit after resolution, mention that in your report. If you prefer to remain anonymous, state that too.

## Scope Guidance

High-priority areas in this repository include:

- authentication and role authorization
- admin-only actions
- enterprise license gating
- secure attachment delivery
- attendance photo access
- payroll and payslip privacy
- backup generation, download, retention, and restore flows
- queue and scheduler behavior
- file upload validation
- hidden debug or test-only routes

## What Not to Do

When testing a vulnerability:

- do not access or modify data that is not yours unless strictly necessary to prove the issue
- do not perform destructive testing on production systems
- do not mass-enumerate records
- do not publicly disclose the issue before coordination
- do not exfiltrate secrets, attachments, or personal data

Keep testing minimal and evidence-based.

## Security Hardening for Operators

For production deployments, at minimum:

- set `APP_ENV=production`
- set `APP_DEBUG=false`
- use HTTPS
- keep `APP_KEY` secret and unique per installation
- rotate credentials for mail, database, and admin accounts
- point the web root to Laravel `public/`
- ensure `storage/` and `bootstrap/cache/` are writable but not publicly exposed
- run queue workers under a controlled service manager
- install cron for `php artisan schedule:run`
- keep Composer dependencies updated
- audit package vulnerabilities regularly

Recommended commands:

```bash
composer audit
php artisan queue:restart
php artisan optimize:clear
```

## Sensitive Operational Areas

This project includes maintenance and backup capabilities. Operators should take extra care with:

- `maintenance:scheduled-backups`
- retained backup files in storage
- restore workflows from SQL backups
- queue workers processing the `maintenance` queue

A production environment should never expose backup artifacts through the public web root.

## Demo and Bootstrap Accounts

This codebase contains demo and bootstrap account behavior in migrations and seeders for evaluation workflows. Before public launch:

- audit all existing users
- remove demo users that should not exist
- rotate passwords
- avoid blind use of `--seed` in production

## Dependency and Release Hygiene

Security posture improves if you:

- keep PHP updated
- keep Laravel and Livewire updated
- audit frontend and backend dependencies
- verify deployment scripts before running them on production
- review hard-reset update flows like `update.sh` before adoption

## Questions

If you are unsure whether an issue is security-relevant, report it privately first. It is better to triage a cautious report than to accidentally disclose a real vulnerability in public.
