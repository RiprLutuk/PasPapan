# Berkontribusi ke PasPapan

English reference: [`README.en.md`](./README.en.md)

Terima kasih sudah berkontribusi ke PasPapan. Repository ini berisi aplikasi Laravel berorientasi produksi dengan operasi admin, alur kerja karyawan, fitur enterprise-gated, dan infrastruktur sensitif deployment seperti queue, backup terjadwal, dan attachment aman.

Panduan ini dibuat agar kontribusi tetap praktis, mudah direview, dan kompatibel dengan codebase saat ini.

## Sebelum Mulai

Silakan baca file berikut terlebih dahulu:

- [README.md](./README.md)
- [SECURITY.md](./SECURITY.md)
- [CODE_OF_CONDUCT.md](./CODE_OF_CONDUCT.md)

Kalau perubahan Anda menyentuh deployment, perilaku queue, operasi backup, lisensi, atau alur enterprise-gated, baca bagian terkait di `README.md` sebelum membuka PR.

## Ways to Contribute

You can contribute through:

- bug reports
- reproducible regression reports
- feature proposals
- documentation improvements
- test coverage improvements
- implementation pull requests

## Reporting Bugs

Before filing a bug:

1. Check existing issues and pull requests.
2. Reproduce the issue on the latest code you can access.
3. Capture the smallest reliable reproduction.

A good bug report should include:

- affected page, route, command, or module
- exact steps to reproduce
- expected result
- actual result
- screenshots or stack traces if available
- environment details if relevant:
  - PHP version
  - Laravel version
  - database driver
  - queue driver
  - browser and device details for UI issues

Do not open a public issue for security vulnerabilities. Use the process in [SECURITY.md](./SECURITY.md).

## Proposing Features

Feature proposals are most useful when they explain:

- the operational problem
- who is affected
- why the current workflow is insufficient
- the expected behavior
- any migration, deployment, or UX impact

For large changes, open an issue or discussion first before writing a large patch.

## Development Setup

### 1. Clone and install

```bash
git clone https://github.com/YOUR_USERNAME/PasPapan.git
cd PasPapan

composer install
bun install
cp .env.example .env
php artisan key:generate
```

### 2. Configure local environment

Update `.env` with a working MySQL or MariaDB connection.

At minimum:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### 3. Run setup commands

```bash
php artisan migrate
php artisan storage:link
```

If you need local bootstrap data:

```bash
php artisan migrate --seed
```

### 4. Run the app

```bash
php artisan serve
bun run dev
```

If your change touches jobs, notifications, or backup automation, also run a queue worker:

```bash
php artisan queue:work --queue=maintenance,default
```

## Branching and Pull Requests

Use a short, descriptive branch name, for example:

```bash
git checkout -b fix/system-maintenance-backup-state
git checkout -b feat/kpi-filter-improvements
git checkout -b docs/readme-deployment-refresh
```

When opening a pull request:

- keep scope tight
- explain the problem first
- summarize the implementation second
- list migrations, queue changes, or deployment steps explicitly
- mention any follow-up work that is intentionally deferred

## Coding Expectations

### General

- follow existing project patterns before introducing a new abstraction
- keep edits narrowly scoped to the task
- avoid unrelated refactors in feature or bug-fix PRs
- prefer readable code over clever code

### PHP and Laravel

- follow PSR-12 style
- keep Livewire state updates explicit and predictable
- validate request and component input where appropriate
- be careful with queue, cache, backup, and storage side effects

### Blade and Livewire

- reuse existing admin and form components where possible
- keep layouts stable as data changes
- avoid adding visual noise or excessive color without a strong reason
- test empty states and filtered states, not just happy paths

### Documentation

If your change affects setup, deployment, security, or runtime operations, update the docs in the same pull request.

Untuk perubahan dokumentasi besar, jaga agar file berikut tetap selaras:

- `README.md`
- `README.en.md`
- `CONTRIBUTING.md`
- `SECURITY.md`

## Database and Migration Rules

If you add or change schema:

- write a proper migration
- assume existing production data matters
- do not rely on seeding for required production structures
- test migration behavior on a non-empty database shape where possible

If your change depends on new tables or columns:

- call that out in the PR
- document any operational impact

## Testing Checklist

Run the narrowest relevant checks for your change.

Common commands:

```bash
php artisan test
./vendor/bin/pest
./vendor/bin/pint
bun run build
```

At minimum:

- bug fixes should include either regression tests or a clear reason a test is not practical
- UI changes should be checked in their empty, loading, and populated states
- queue or maintenance changes should be tested with the actual command or job path when practical

## Commit Messages

Conventional commit prefixes are preferred:

- `feat:` new feature
- `fix:` bug fix
- `docs:` documentation only
- `refactor:` internal restructuring
- `test:` tests
- `chore:` maintenance or dependency work

Examples:

```bash
git commit -m "fix: guard backup history when migration is missing"
git commit -m "docs: rewrite production deployment guidance"
git commit -m "feat: add scheduled maintenance backup workflow"
```

## Review Notes for High-Risk Changes

Call out these changes explicitly in your PR if they are present:

- migrations
- queue worker behavior
- scheduler behavior
- backup generation or restore logic
- secure file delivery
- authentication, authorization, or enterprise gating
- deployment scripts

## Questions

If you are unsure how to approach a change, open an issue or discussion before building a large patch.

Thanks for keeping contributions disciplined and production-aware.
