# PRD & Task Tracking — Refactor & Cleansing (Low-Effort, High-Impact)

> Dibuat: 2026-06-22 · Branch: `feature/toko-pos-addon` · Status: **Fase 1 & 2 + T8 DIEKSEKUSI & terverifikasi; T9/T10/T-min/T15 deferred**
>
> Dokumen ini adalah hasil review sistem PasPapan untuk menemukan perbaikan **low-effort high-impact**. Setiap temuan sudah diverifikasi ulang (adversarial) terhadap kode aktual — bukan asumsi. Referensi `file:line` di bawah valid per tanggal pembuatan dokumen.

## 0. Status Eksekusi (update 2026-06-22)

Semua perubahan ada di **working tree** (belum di-commit). **Tidak ada regresi**: semua kegagalan test/gate terbukti *pre-existing* via `git stash` compare (band: dengan vs tanpa perubahan = identik).

**Selesai & terverifikasi:**
- ✅ T1 pint (CI style hijau), T2 `*.sql`/`*.dump` ignore, T4 dead code (trait + 4 blade + 2 static view + whitelist + import + komentar), T11 6× console.log, T12 `composer lint`/`lint:fix` + pre-commit pint gate, rmdir `Toko/`.
- ✅ T3 trait `App\Support\Concerns\ScopesCompaniesByActor` (5 service) + interface `App\Support\Contracts\ScopesCompanies`.
- ✅ T6 trait `App\Livewire\Concerns\ScopesCompanySelection` (5 komponen: `scopedCompanyId`/`defaultCompanyId`/`normalizeActiveTab`/`companyOptions`/`scopedCompanyIds`).
- ✅ T5 phpstan `paths` +`app/Actions`,`app/Queries`,`app/Jobs` + return type 5 relasi Eloquent (phpstan tetap 22 error = baseline, nol error baru).
- ✅ T7 i18n lock strings (Kasbon ×2 + Advanced Reporting ×4) + 2 key di kedua lang file (sinkron 4630/4630).
- ✅ T13 trait `App\Livewire\Concerns\ValidatesCompanyId` (15×) + `App\Support\MoneyInput` (currency) + `prepareProductAttributes()` (SKU/status).
- ✅ T14 return type (`ExtendedCarbon`, `BarcodeGenerator` ×2) + `DashboardComponent` debug micro.
- ✅ T8 trait `App\Models\Concerns\HasRolePermissions` (cluster RBAC 15 method dipindah verbatim dari `User`).

**Hasil gate:** `pint --test` PASS · `phpstan` 22 (=baseline) · test workspace/commercial/attendance-correction hijau · `check:enterprise-boundary` PASS · `bun run build` OK. (`check:ui`, `check:modern-stack`, 6 unit test, 1 operational-health test = **pre-existing fail**, tak terkait perubahan ini.)

**Deferred (sengaja — butuh PR fokus + verifikasi penuh, sesuai prinsip "1 PR per item Fase 3"):**
- ⏸️ **T9** `AttendanceCorrectionPage` → service: M-effort, behavior-sensitive (time-math absensi). Punya test guard kuat (`AttendanceCorrectionFlowTest`); kerjakan saat tooling test stabil.
- ⏸️ **T10** `UserForm` role-sync/manager-cycle → Action/Guard: otorisasi RBAC security-sensitive; `RbacRoleManagementTest` **di-skip** tanpa `TEST_ENTERPRISE_LICENSE_PRIVATE_KEY` di env ini → tidak dapat diverifikasi penuh sekarang.
- ⏸️ **T-min** map route-ability `User` → const: jalur redirect tanpa coverage + risiko transkripsi 53 baris; tambahkan Feature test dulu.
- ⏸️ **T15** terjemah 9 tooltip English di `toko/products.blade.php`: kosmetik, idealnya diverifikasi visual.

## 1. Ringkasan Eksekutif

PasPapan adalah codebase yang **sudah matang dan disiplin** (self-score 85/100, lang `id`/`en` sinkron 4632 key, 0 `dd/dump`, 0 model `$guarded=[]`, enterprise boundary terjaga). Karena itu fokus review ini **bukan rewrite besar**, melainkan membersihkan utang kecil yang sudah mulai menumpuk — sejalan dengan prinsip `guides/feature-maturity.md` (perkuat yang ada, jangan tambah modul).

Temuan kunci yang paling bernilai:

1. **CI sedang MERAH.** `./vendor/bin/pint --test` gagal pada 7 file tracked, dan `composer phpstan` juga gagal (22 error pre-existing dari controller obfuscated Toko). Perbaikan pint = satu perintah.
2. **Risiko keamanan data preventif.** Tidak ada aturan ignore `*.sql`; dump DB berisi PII (`database_backup.sql`, 3 MB) pernah ter-commit dan hanya dihapus dari HEAD — bisa terulang.
3. **Duplikasi company-scope.** Helper RBAC & scope perusahaan disalin verbatim di 5–6 komponen/service. Salah satunya (`scopeCompanies()`) adalah invariant otorisasi yang berisiko *drift* antar salinan.
4. **Dead code aman dihapus.** Trait, view Blade, dan blok komentar yang sudah tidak direferensikan.

Total: **36 temuan terverifikasi**. 7 temuan kandidat **ditolak** karena tidak aman / berlebihan (lihat §7).

## 2. Aturan Main (jangan dilanggar saat eksekusi)

Berdasarkan `AGENTS.md`, `guides/architecture.md`, `guides/feature-maturity.md`:

- **Jangan ubah URL/route name** — menu, policy, test, bookmark bergantung padanya.
- **Jangan edit artifact obfuscated**: `app/Livewire/Admin/TokoPosAddon.php`, `app/Livewire/Finance/Concerns/ManagesCashAdvances.php`, dan semua file `eval(gzinflate(...))`. File `*.Source.php` tidak di-track (benar).
- **Logika berat tetap di** `app/Actions|Services|Queries|Support`, Livewire hanya UI state/validasi/authorization/dispatch.
- **Jaga sinkron** `lang/id.json` ⇄ `lang/en.json` (sama-sama 4632 key).
- **Setiap bug-fix/hardening idealnya punya test regression.** Boot tetap jalan tanpa `ENTERPRISE_OBFUSCATOR_KEY`.

## 3. Matriks Prioritas (Impact × Effort)

`S` = <1 jam · `M` = ±setengah hari · `L` = ≥1 hari

| # | Item | Impact | Effort | Fase |
|---|------|--------|--------|------|
| T1 | Jalankan `pint` → CI hijau (7 file) | **High** | S | 1 |
| T2 | Tambah ignore `*.sql`/`*.dump` (cegah leak PII) | **High** | S | 1 |
| T3 | Extract trait `scopeCompanies()` (invariant RBAC, 5 service) | **High** | S | 2 |
| T4 | Hapus dead code (trait, 4 Blade, view static, komentar) | Med | S | 1 |
| T5 | Perluas phpstan `paths`: `app/Actions` (+`Queries`,`Jobs` setelah return-type) | Med | S | 2 |
| T6 | Extract company-scope helpers Livewire (`scopedCompanyId`/`defaultCompanyId`/`normalizeActiveTab`) | Med | S | 2 |
| T7 | i18n: bungkus string lock Enterprise dengan `__()` + key lang | Med | S | 2 |
| T8 | Extract RBAC cluster `User` → trait `HasRolePermissions` | Med | S | 3 |
| T9 | `AttendanceCorrectionPage`: pindahkan time-math ke service | Med | M | 3 |
| T10 | `UserForm`: extract role-sync + manager-cycle ke Action/Guard | Med | M | 3 |
| T11 | Hapus 6 `console.log` debug di flow scan (production) | Low | S | 1 |
| T12 | Gate pint lokal (pre-commit / `composer lint`) | Low | S | 1 |
| T13 | Dedup validasi `company_id` (15×) + parsing currency + SKU/status | Low | S | 2 |
| T14 | Return type 3 method `app/Support` + micro-cleanup | Low | S | 2 |
| T15 | Terjemahkan 9 tooltip English di Toko `products.blade` | Low | S | 2 |
| — | Git history bloat (~190 MB) — **deferred**, butuh rewrite terkoordinasi | Low | L | backlog |

## 4. Fase Eksekusi

### Fase 1 — Quick Wins & Cleansing (semua `S`, zero/low-risk, bisa 1 PR)
Tidak mengubah behavior. Target: CI hijau + tree bersih.
`T1, T2, T4, T11, T12` + dir kosong + WIP comment.

### Fase 2 — DRY & Tooling (semua `S`, behavior-preserving, jalankan test setelahnya)
Extract trait/helper + perluas static analysis + i18n.
`T3, T5, T6, T7, T13, T14, T15`.

### Fase 3 — Altitude Architecture (`S`–`M`, dilindungi test, 1 PR per item)
Pindahkan logika dari Livewire/Model ke Service/Action/Trait.
`T8, T9, T10` + T-min (route-ability map `User`).

---

## 5. Detail Task

### FASE 1

#### T1 — CI MERAH: `pint --test` gagal di 7 file → satu kali `pint` ✅ High / S
- **File:** `bootstrap/app.php`, `app/Livewire/Admin/CommercialWorkspace.php`, `app/Livewire/DemoEnterpriseToggle.php`, `app/Support/UserSessionManagementService.php`, `app/Support/CommercialWorkspaceService.php`, `app/Http/Middleware/TrackRedisSessions.php`, `app/Helpers/Editions.php`.
- **Bukti:** `./vendor/bin/pint --test` → `result: fail`. Fixer semua mekanis (whitespace, `ordered_imports`, `braces_position`, dll). `.github/workflows/laravel.yml` (step Pint) & `deploy.yml:105` menjalankan `pint --test` → kedua pipeline merah. `pint.json` sudah exclude `enterprise_build`, `secure_tools`, `*.Source.php` → artifact obfuscated tidak tersentuh.
- **Fix:** `./vendor/bin/pint` lalu commit. Sekaligus hapus WIP-comment basi di `CommercialWorkspace.php:170-174` (baris 175-177 adalah kode `(string)(float)` cast — **jangan** dihapus).
- **Acceptance:** `./vendor/bin/pint --test` → `PASS`; tidak ada perubahan logika di diff.

#### T2 — Tidak ada ignore `*.sql` → risiko commit dump PII ✅ High / S
- **File:** `.gitignore` (43 baris, tanpa rule `*.sql`/`*.dump`).
- **Bukti:** `database_backup.sql` (3.043.429 byte) pernah ter-commit (`23b896f2`), dihapus hanya dari HEAD (`eac1c0d7`) tapi masih di history. `git check-ignore database_backup.sql` → exit 1 (TIDAK di-ignore). 0 file `.sql` tracked; `.sql` di `storage/` sudah ter-ignore lokal; fixtures test dibuat runtime. HRIS = data karyawan/PII nyata.
- **Fix:** tambahkan ke `.gitignore`: `*.sql`, `*.dump`. (Purge blob 3 MB dari history = follow-up terpisah, bukan low-effort.)
- **Acceptance:** `git check-ignore database_backup.sql` → match; `git ls-files '*.sql'` tetap kosong.

#### T4 — Hapus dead code aman ✅ Med / S
Hapus hanya yang terbukti tidak direferensikan (grep semua sintaks):
- [ ] `app/Livewire/User/TeamApprovalsHistory.php:5` — `use App\Models\Attendance;` tak terpakai.
- [ ] `app/Http/Controllers/Admin/ImportExport/Concerns/HandlesServiceResponse.php` — trait orphan (+ dir `Concerns/` kosong) → hapus, lalu `composer dump-autoload`.
- [ ] 4 Blade orphan: `resources/views/components/sections/section-title.blade.php`, `branding/application-logo.blade.php`, `user/time-card.blade.php`, `feedback/badge.blade.php`. **JANGAN** hapus `shared/shift-selector` & `feedback/alert-messages` (keduanya dipakai via `@include`).
- [ ] `resources/views/static/pwa.blade.php` & `static/offline.blade.php` (route `/offline` melayani `public/offline.html`, dikonfirmasi `tests/Feature/ErrorPagesTest.php`) + hapus 2 entri whitelist basi `scripts/ui-rule-whitelist.php:191-192`.
- [ ] `resources/views/livewire/user/scan.blade.php:1955-1962` — blok `/* ... */` debug-log mati (tidak ada elemen `id="debug-log"`). Sisakan `logDebug()` baris 1954.
- **Decision-gated (jangan hapus tanpa keputusan produk):**
  - `addon-lock-modal.blade.php` — orphan tapi se-commit dengan add-on obfuscated; konfirmasi ke maintainer Toko apakah event `addon-lock` di-emit dari artifact.
  - `resources/views/policy.blade.php` + `terms.blade.php` + `branding/authentication-card-logo.blade.php` — unreachable karena `config/jetstream.php:62` (`termsAndPrivacyPolicy`) di-comment. Pilih: aktifkan fitur **atau** hapus 3 view.
- **Acceptance:** `php artisan test` hijau; `composer check:ui` hijau; `php artisan route:list` tidak berubah.

#### T11 — `console.log` debug nyampai ke device production ✅ Low / S
- **File:** `resources/views/livewire/user/scan.blade.php:1918,1954,2908`; `resources/js/services/native/barcode.js:106,118,129`.
- **Bukti:** 6 `console.log` `[CAM DEBUG]`/`[NATIVE CAM]` fire di happy-path (bukan catch). Tidak ada gate debug; `vite.config.js` tanpa `drop_console`; script inline tak diminify → tampil di console device. `console.warn/error` di catch block sengaja dibiarkan.
- **Fix:** hapus 6 baris itu **atau** gate dengan `const CAM_DEBUG = false`. Menghapus `scan.blade.php:1954` mematikan ~10 call `logDebug()` sekaligus.
- **Acceptance:** `bun run build` sukses; tidak ada `[CAM DEBUG]`/`[NATIVE CAM]` di bundle.

#### T12 — Tidak ada gate format lokal (drift sampai ke commit) ✅ Low / S
- **Bukti:** satu-satunya hook `.git/hooks/pre-commit` hanya menjalankan compiler enterprise; tidak ada husky/lefthook; tidak ada script `composer lint`.
- **Fix (pilih satu):**
  - A) Tambah step pint staged-PHP ke pre-commit — **wajib** filter `*.Source.php` & `app/Livewire/Admin/TokoPosAddon.php` (pint mengabaikan `pint.json` saat path eksplisit diberikan): `FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' | grep -v '\.Source\.php$' | grep -v 'TokoPosAddon\.php')`.
  - B) Tambah script `composer lint`/`lint:fix` (wrap pint) + dokumentasikan di `CONTRIBUTING.md`.
- **Acceptance:** edit lalu commit file ber-style buruk → terblok/terformat otomatis.

#### Dir kosong (housekeeping)
- `rmdir app/Livewire/Admin/Toko/` (kosong, untracked, tak direferensikan; kode Toko asli di `app/Http/Controllers/Admin/Toko/` & `app/Support/TokoPos*`). Catatan: git tak melacak dir kosong → hanya bersih lokal.

---

### FASE 2

#### T3 — `scopeCompanies()` RBAC disalin verbatim di 5 service ✅ High / S
- **File:** `app/Support/AccountingWorkspaceService.php:108-115`, `CommercialWorkspaceService.php:38-45`, `CollaborationWorkspaceService.php:34-41`, `OperationalWorkspaceService.php:28-35`, `CustomFormBuilderService.php:30-37`.
- **Bukti:** 5 body byte-identical: `if ($actor->isSuperadmin) { return $query; } return $query->whereKey($actor->company_id);`. Ini **satu-satunya sumber** aturan visibility company (superadmin lihat semua; lainnya scoped) — disalin 5×, rawan drift. Dipakai dari komponen workspace + `AccountingStatementsExport.php:25` + 1 internal call.
- **Fix:** buat `app/Support/Concerns/ScopesCompaniesByActor.php` (konvensi `Concerns/` sudah ada), pindahkan method apa adanya, `use` di 5 service, hapus 5 salinan. Signature publik tetap → **0 call-site berubah**.
- **Acceptance:** `php artisan test` (workspace + multi-company isolation) hijau; `composer phpstan` tidak menambah error.

#### T5 — phpstan hanya menganalisis ~19% `app/` ✅ Med / S
- **File:** `phpstan.neon.dist:6-16` (`paths` hanya `app/Console`, `app/Http`, `app/Services/Location` + config/migrations/seeders/routes).
- **Bukti:** `app/Support` (104 file), `app/Livewire` (118), `app/Models` (79), `app/Actions`, `app/Queries`, `app/Jobs` **tidak dianalisis**. `phpstan analyse app/Actions --level=0` → `[OK] No errors` → menambah `- app/Actions` = **0 error baru**. `app/Queries`+`app/Jobs` hanya memunculkan 8 false-positive `larastan.relationExistence` yang hilang dengan menambah return type ke 5 relasi: `User::division/jobTitle()→: BelongsTo`, `User::attendances()→: HasMany` (import sudah ada), `Attendance::shift()→: BelongsTo`, `Payroll::user()→: BelongsTo` (tambah 1 `use`).
- **Fix:** tambah `- app/Actions` sekarang; lalu tambah return type 5 relasi → tambah `- app/Queries`, `- app/Jobs`. **Jangan** naikkan level; perluas `paths` per-dir yang sudah bersih.
- **⚠️ Catatan blocker terpisah:** `composer phpstan` sudah **merah di branch ini** (22 error `class.notFound` dari controller `eval()` Toko di `routes/web/admin/operations.php` + 1 false-positive stub `Editions`). Ini **bukan** bagian cleansing — selesaikan via `scanFiles`/stub sebelum merge. Penambahan `paths` di atas tidak menambah error baru.
- **Acceptance:** `composer phpstan` tidak bertambah error setelah perluasan `paths`.

#### T6 — Helper company-scope Livewire disalin 5–6× ✅ Med / S
Satu trait baru di `app/Livewire/Concerns/` menutup beberapa duplikasi sekaligus:
- `scopedCompanyId(array,string): ?int` — byte-identical (MD5 `93b71f...`) di `AccountingWorkspace:370`, `CommercialWorkspace:520`, `CollaborationWorkspace:385`, `OperationalWorkspace:362`, `CustomFormManager:196`. Stateless → pindah apa adanya.
- `defaultCompanyId(): ?string` — identik kecuali properti service injected, di 5 komponen yang sama. Pakai abstract accessor / interface `scopeCompanies(Builder,User)` (signature seragam di semua service).
- `normalizeActiveTab()` — identik struktur di 6 komponen. **Gunakan `protected const DEFAULT_TAB`**, JANGAN `self::TABS[0]` (di `CommercialWorkspace`, default `products` ≠ `TABS[0]`=`pipeline`).
- Blok query "accessible companies" (`scopeCompanies(...)->pluck('id')` + `whereIn->orderBy('name')->get(['id','name'])`) identik di 4 `render()` → helper `companyOptions(array): Collection` (opsi minimal, 0 coupling).
- **Acceptance:** test workspace hijau; hidrasi Livewire normal (tab tidak reset).

#### T7 — String lock Enterprise tidak diterjemahkan ✅ Med / S
User Indonesia melihat teks Inggris di layar lock (sibling lain sudah `__()`).
- **Kasbon** (`app/Livewire/Admin/Finance/CashAdvanceManager.php:34-37`, `app/Livewire/User/Finance/TeamCashAdvanceManager.php:28-31`): bungkus `'title' => __('Kasbon Locked')` (key sudah ada `id.json:1588`), `'message' => __('Manage Kasbon is an Enterprise Feature. Please Upgrade.')` + tambah key ini ke **kedua** lang file (mis. id: `Kelola kasbon adalah fitur Enterprise.`). **Jangan** edit `ManagesCashAdvances` (artifact obfuscated).
- **Advanced Reporting** (`AttendanceController.php:30`, `ExportReportPdfController.php:18`, `ExportAttendancesController.php:18`, `ExportUsersController.php:18`, + `HandlesServiceResponse.php:11` yang dihapus di T4): bungkus `__('Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.')` + tambah key ke kedua lang file.
- **Acceptance:** jumlah key `id.json` == `en.json`; layar lock tampil sesuai locale.

#### T13 — Duplikasi kecil lain ✅ Low / S
- Rule `['required','integer',Rule::exists('companies','id')]` disalin **15×** (`AccountingWorkspace`, `CollaborationWorkspace`, `OperationalWorkspace`, 4 `Forms/Commercial/*`, `CustomFormManager`) → trait `ValidatesCompanyId::companyIdRules()`.
- `normalizeCurrency()` di `CommercialWorkspace.php:331` dipanggil 11× sebelum `validate()`; twin berbeda `normalizeCurrencyAmount()` di `User/ReimbursementPage.php:159-168` → dua helper terpisah di `app/Support` (decimal vs digits-only — **jangan** disatukan).
- Blok SKU/status verbatim di `CommercialWorkspaceService.php:54-58` & `65-69` → `prepareProductAttributes()`. **JANGAN** gabung `calculateTotals`/`calculateVendorBillTotals` (beda algoritma + di-test via `ReflectionMethod`).

#### T14 — Konsistensi mekanis ✅ Low / S
- Return type 3 method `app/Support`: `ExtendedCarbon::closestFromDateArray()→: ?ExtendedCarbon`, `BarcodeGenerator::generateQrCodesZip()→: string`, `generateQrCode()→: \Intervention\Image\Interfaces\EncodedImageInterface` (intervention/image 4.0.4).
- `app/Livewire/Admin/DashboardComponent.php:85-89` (blok `auth.debug_log`): tangkap `$user = auth()->user();` sekali (cermin blok `Gate::denies` di baris 67).

#### T15 — Tooltip English di modul Toko (Indonesia-only) ✅ Low / S
- `resources/views/livewire/admin/toko/products.blade.php:52,168,455,459,462,466,469,562,566` — 9 literal `title`/`aria-label` English (`Import Data`, `Generate Barcode`, `Print Thermal (Roll)`, `Print A4 (Sticker)`, `Excel`). Terjemahkan ke Indonesia agar konsisten modul. **Jangan** tambah key lang (modul Toko sengaja pakai teks Indonesia sebagai key & tidak ada di JSON).

---

### FASE 3

#### T8 — Cluster RBAC di model `User` → trait ✅ Med / S
- **File:** `app/Models/User.php:431-599` (15 method: `hasPermission` wildcard-walk, `allowsAdminPermission`, `rolePermissionKeys`, dst). `User` = 857 baris.
- **Fix:** `app/Models/Concerns/HasRolePermissions.php`, pindah `431-599` apa adanya + tambah `use App\Models\Role;` & `use App\Support\RbacRegistry;` di trait. Nama method sama → ~20 caller (policy/gate/service) tak berubah.
- **⚠️ Test:** `RbacRoleManagementTest.php` di-skip tanpa `TEST_ENTERPRISE_LICENSE_PRIVATE_KEY` — verifikasi via policy/gate Feature test yang jalan, atau tinker.

#### T9 — `AttendanceCorrectionPage` memegang ~160 baris logika absensi ✅ Med / M
- **File:** `app/Livewire/User/AttendanceCorrectionPage.php` — `inferRequestType` (271-294), overnight roll-over `attendanceSnapshotDateTime` (343-374), default shift `defaultRequestedDateTime` (314-341), cross-field rules `validateRequestPayload` (213-269). `AttendanceCorrectionService::submit()` hanya menyimpan `request_type` yang sudah dihitung komponen.
- **Fix:** pindah inferensi + math ke `AttendanceCorrectionService` (mis. `buildRequestPayload(User,array): array`); komponen delegasi dari reactive hook & `render()`. Sekalian gabungkan 3 query `Attendance` duplikat (baris 198/215/307).
- **✅ Aman:** `AttendanceCorrectionFlowTest.php` (7 test) mengunci behavior. Effort `M` karena menyentuh 4 reactive hook + render + save.

#### T10 — `UserForm` memegang otorisasi role + manager-cycle ✅ Med / M
- **File:** `app/Livewire/Forms/UserForm.php` (468 baris, `Livewire\Form`) — `syncRoles` (392-435, gate `assignRoles` + guard superadmin + `roles()->sync()`), `normalizeRequestedRoleIds` (437-451), `synchronizeSubjectGroup` (453-467), `ensureManagerDoesNotCreateCycle` (350-377, graph-walk + visited-set), `authorizeEmploymentStatusChange` (312-335).
- **Fix:** `app/Actions/Users/SyncUserRoles.php` (kembalikan `role_ids`+`group` untuk di-assign balik — bukan copy verbatim karena baca+tulis state form) + `app/Support/ManagerCycleGuard.php` (tidak ada resolver manager-chain existing). **Sisakan** `normalizeSingleRoleSelection` (379-390, murni UI).
- **✅ Aman:** `RbacRoleManagementTest.php` (role-assign) + `EmployeeDeleteInteractionTest.php:116` (cycle) menjaga behavior.

#### T-min — Map route-ability 53-entri di `User` ✅ Low / S
- `app/Models/User.php:601-681` (`preferredAdminRouteName`) embed array 53 entri route→ability. Pindah ke `private const ADMIN_ROUTE_ABILITIES` (**bukan** file `config/*.php` kecuali tiap `Model::class` bareword di-FQN-kan). Tambah Feature test untuk path redirect ini (saat ini 0 coverage).

---

## 6. Perintah Verifikasi (jalankan setelah tiap fase)

```bash
./vendor/bin/pint --test          # T1 → harus PASS
composer phpstan                  # T5 → tidak ada error baru (lihat catatan blocker)
php artisan test                  # regression
composer check:ui                 # T4/T15
composer check:modern-stack
composer check:enterprise-boundary
bun run build                     # T11
```

## 7. Sengaja TIDAK Dikerjakan (ditolak saat verifikasi)

Agar tidak membuang effort pada hal yang tidak aman/berlebihan:

- **Split `AccountingWorkspaceService` (1305 baris)** — konstanta `DEFAULT_*_ACCOUNT` dipakai lintas seam read/write; split = bug atau dua sumber kebenaran kode akun (area high-risk).
- **`AdminDashboardQueryService`** — sudah ter-faktor rapi; satu-satunya split (`chartData`/`statDetail`) = `M` effort, 3 consumer, payoff rendah.
- **Konsolidasi org-chart accessor `User`** — `getSubordinatesAttribute` vs `ApprovalActorService` punya beda semantik (rank gate, scope company) & menopang 4 policy → security-sensitive, bukan low-effort.
- **Script `composer check` agregat** — sudah ada via `bun run evidence:review` (`scripts/collect-review-evidence.sh`).
- **Status string → enum/const** — model `Reimbursement`/`Overtime` tidak punya konstanta status; mayoritas lokasi tak punya target yang aman.
- **`paspapan-release.zip` (136 MB) & `git gc`** — local-only, 0 dampak repo (sudah ter-ignore `*.zip`).
- **Rewrite git history (~190 MB bloat)** — hanya efektif via `git filter-repo` + force-push terkoordinasi (8 branch, 19 tag) = `L` & berisiko. Low-effort-nya: cegah bloat baru via LFS untuk `*.bin`/splash/`*.webp`. → **backlog**, flag ke maintainer.

## 8. Catatan Eksekusi

- Disarankan **1 PR per fase** (Fase 1 boleh 1 PR gabungan karena semua zero-risk; Fase 3 sebaiknya 1 PR per item).
- Conventional commit (`refactor:`, `fix:`, `chore:`, `docs:`); scope sempit per `CONTRIBUTING.md`.
- Setiap PR sebutkan eksplisit jika menyentuh: RBAC/policy, secure file, migration, queue/scheduler (Fase 3 T8/T9/T10 menyentuh RBAC & altitude).
