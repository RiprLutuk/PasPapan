<script lang="ts">
	import { onMount } from 'svelte';
	import Icon from '../../../lib/Icon.svelte';
	import { request } from '../../../lib/api';
	import { workSession } from '../../../lib/session';
	import { uiText as t } from '../../../lib/i18n';

	const session = workSession();

	type ApiDocMeta = {
		version: string;
		title: string;
		superadmin_only: boolean;
		required_capability: string;
		runtime: {
			app_origin: string;
			service_key_enabled: boolean;
			service_key_header: string;
			session_cookie_name: string;
			session_hmac_enabled: boolean;
			storage_driver: string;
			postgres_version: string;
		};
	};

	type EndpointSpec = {
		id: string;
		category: string;
		method: 'GET' | 'POST';
		path: string;
		summary: string;
		capability: string;
		headers: Record<string, string>;
		requestBody?: unknown;
		responseExample: unknown;
	};

	const endpoints: EndpointSpec[] = [
		{
			id: 'health',
			category: 'RBAC, Audit & PG 18',
			method: 'GET',
			path: '/health',
			summary: 'Liveness check server Rust API (bebas autentikasi untuk container healthcheck).',
			capability: 'Publik',
			headers: { Accept: 'application/json' },
			responseExample: { status: 'ok' }
		},
		{
			id: 'auth-register',
			category: 'Auth & Sesi',
			method: 'POST',
			path: '/v1/auth/register',
			summary: 'Daftarkan perusahaan baru beserta akun Superadmin (Owner) pertama dan otomatis membuat sesi.',
			capability: 'Publik (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				company_name: 'PT Nusantara Karya',
				company_slug: 'nusantara-karya',
				full_name: 'Budi Santoso',
				email: 'owner@nusantara.id',
				password: 'super-secure-password-123'
			},
			responseExample: { expires_in_seconds: 28800 }
		},
		{
			id: 'auth-login',
			category: 'Auth & Sesi',
			method: 'POST',
			path: '/v1/auth/login',
			summary: 'Autentikasi email & kata sandi (Argon2id) dan terbitkan cookie HttpOnly paspapan_session.',
			capability: 'Publik (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				email: 'owner@nusantara.id',
				password: 'super-secure-password-123'
			},
			responseExample: { expires_in_seconds: 28800 }
		},
		{
			id: 'auth-session',
			category: 'Auth & Sesi',
			method: 'GET',
			path: '/v1/auth/session',
			summary: 'Ambil profil pengguna aktif, perusahaan, peran, dan daftar capability efektif.',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: {
				capabilities: ['admin.access.manage', 'admin.dashboard.view', 'admin.employees.manage'],
				user: { id: '0195f000-0000-7000-8000-000000000001', full_name: 'Budi Santoso', email: 'owner@nusantara.id' },
				company: { id: '0195f000-0000-7000-8000-000000000002', name: 'PT Nusantara Karya', role: 'owner' }
			}
		},
		{
			id: 'auth-password-reset',
			category: 'Auth & Sesi',
			method: 'POST',
			path: '/v1/auth/password-reset',
			summary: 'Kirim permintaan bantuan pemulihan akses ke Owner/Admin perusahaan dan catat di outbox & audit log.',
			capability: 'Publik (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: { email: 'karyawan@nusantara.id' },
			responseExample: null
		},
		{
			id: 'auth-logout',
			category: 'Auth & Sesi',
			method: 'POST',
			path: '/v1/auth/logout',
			summary: 'Cabut sesi saat ini di database dan hapus cookie paspapan_session.',
			capability: 'Terautentikasi (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: null
		},
		{
			id: 'account-profile-get',
			category: 'Akun & Keamanan',
			method: 'GET',
			path: '/v1/account/profile',
			summary: 'Ambil detail profil pribadi beserta versi optimistic locking (version).',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: {
				full_name: 'Budi Santoso',
				email: 'owner@nusantara.id',
				phone: '081234567890',
				address: 'Jakarta Selatan',
				team: 'Engineering',
				job_title: 'Principal Architect',
				employee_code: 'OWNER-0195f000',
				version: 1
			}
		},
		{
			id: 'account-profile-post',
			category: 'Akun & Keamanan',
			method: 'POST',
			path: '/v1/account/profile',
			summary: 'Simpan pembaruan profil pribadi dengan proteksi optimistic concurrency (version).',
			capability: 'Terautentikasi (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				full_name: 'Budi Santoso',
				phone: '081234567890',
				address: 'Jl. Jenderal Sudirman No. 10, Jakarta',
				version: 1
			},
			responseExample: null
		},
		{
			id: 'account-password',
			category: 'Akun & Keamanan',
			method: 'POST',
			path: '/v1/account/password',
			summary: 'Ganti kata sandi akun, cabut seluruh sesi perangkat lain, dan rotasi token sesi aktif.',
			capability: 'Terautentikasi (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				current_password: 'super-secure-password-123',
				new_password: 'new-super-secure-password-456'
			},
			responseExample: { expires_in_seconds: 28800 }
		},
		{
			id: 'account-sessions',
			category: 'Akun & Keamanan',
			method: 'GET',
			path: '/v1/account/sessions',
			summary: 'Daftar seluruh sesi login aktif milik pengguna beserta penanda sesi saat ini (current).',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: [
				{
					id: '0195f000-0000-7000-8000-000000000010',
					created_at: '2026-10-25T03:00:00Z',
					expires_at: '2026-10-25T11:00:00Z',
					current: true
				}
			]
		},
		{
			id: 'attendance-today',
			category: 'Absensi & Dynamic QR',
			method: 'GET',
			path: '/v1/attendance/today',
			summary: 'Status presensi hari ini menurut zona waktu perusahaan beserta jadwal shift aktif.',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: {
				date: '2026-10-25',
				status: 'checked_in',
				check_in_time: '08:02',
				check_out_time: null,
				shift_name: 'Shift Pagi HQ',
				starts_at: '08:00:00',
				ends_at: '17:00:00'
			}
		},
		{
			id: 'attendance-checkin',
			category: 'Absensi & Dynamic QR',
			method: 'POST',
			path: '/v1/attendance/check-in',
			summary: 'Check-in presensi dengan validasi jarak geofence Haversine & token QR dinamis (30 detik).',
			capability: 'Terautentikasi (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				latitude: -6.2088,
				longitude: 106.8456,
				qr_token: '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'
			},
			responseExample: {
				date: '2026-10-25',
				status: 'checked_in',
				check_in_time: '08:02',
				check_out_time: null,
				shift_name: 'Shift Pagi HQ',
				starts_at: '08:00:00',
				ends_at: '17:00:00'
			}
		},
		{
			id: 'attendance-checkout',
			category: 'Absensi & Dynamic QR',
			method: 'POST',
			path: '/v1/attendance/check-out',
			summary: 'Check-out presensi dengan validasi koordinat GPS & token QR dinamis lokasi kerja.',
			capability: 'Terautentikasi (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				latitude: -6.2088,
				longitude: 106.8456,
				qr_token: '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'
			},
			responseExample: {
				date: '2026-10-25',
				status: 'checked_out',
				check_in_time: '08:02',
				check_out_time: '17:05',
				shift_name: 'Shift Pagi HQ',
				starts_at: '08:00:00',
				ends_at: '17:00:00'
			}
		},
		{
			id: 'attendance-locations-list',
			category: 'Absensi & Dynamic QR',
			method: 'GET',
			path: '/v1/admin/attendance-locations',
			summary: 'Daftar titik lokasi absensi perusahaan beserta token QR aktif dan radius meter.',
			capability: 'admin.barcodes.manage',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: [
				{
					id: '0195f000-0000-7000-8000-000000000020',
					name: 'Lobby Utama HQ Jakarta',
					qr_token: 'a1b2c3d4e5f60123456789abcdef0123456789abcdef0123456789abcdef0123',
					qr_expires_at: '2026-10-25T04:00:30Z',
					latitude: -6.2088,
					longitude: 106.8456,
					radius_meters: 100,
					active: true
				}
			]
		},
		{
			id: 'attendance-locations-create',
			category: 'Absensi & Dynamic QR',
			method: 'POST',
			path: '/v1/admin/attendance-locations',
			summary: 'Tambah titik lokasi presensi baru dengan koordinat GPS dan radius meter (10–5000m).',
			capability: 'admin.barcodes.manage (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				name: 'Kantor Cabang Bandung',
				latitude: -6.9175,
				longitude: 107.6191,
				radius_meters: 120
			},
			responseExample: {
				id: '0195f000-0000-7000-8000-000000000021',
				name: 'Kantor Cabang Bandung',
				qr_token: 'f1e2d3c4b5a60123456789abcdef0123456789abcdef0123456789abcdef0123',
				qr_expires_at: '2026-10-25T04:00:30Z',
				latitude: -6.9175,
				longitude: 107.6191,
				radius_meters: 120,
				active: true
			}
		},
		{
			id: 'attendance-locations-refresh',
			category: 'Absensi & Dynamic QR',
			method: 'POST',
			path: '/v1/admin/attendance-locations/refresh',
			summary: 'Rotasi token QR dinamis (30 detik) untuk seluruh lokasi aktif perusahaan.',
			capability: 'admin.barcodes.manage (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: []
		},
		{
			id: 'shifts-list',
			category: 'Shift & Jadwal',
			method: 'GET',
			path: '/v1/admin/shifts',
			summary: 'Daftar seluruh master shift kerja perusahaan.',
			capability: 'admin.shifts.manage',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: [
				{
					id: '0195f000-0000-7000-8000-000000000030',
					name: 'Shift Pagi Reguler',
					starts_at: '08:00:00',
					ends_at: '17:00:00',
					crosses_midnight: false
				}
			]
		},
		{
			id: 'shifts-create',
			category: 'Shift & Jadwal',
			method: 'POST',
			path: '/v1/admin/shifts',
			summary: 'Buat master shift kerja baru (mendukung shift lintas tengah malam / crosses_midnight).',
			capability: 'admin.shifts.manage (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				name: 'Shift Malam Operasional',
				starts_at: '22:00',
				ends_at: '06:00',
				crosses_midnight: true
			},
			responseExample: {
				id: '0195f000-0000-7000-8000-000000000031',
				name: 'Shift Malam Operasional',
				starts_at: '22:00:00',
				ends_at: '06:00:00',
				crosses_midnight: true
			}
		},
		{
			id: 'shift-assignments-assign',
			category: 'Shift & Jadwal',
			method: 'POST',
			path: '/v1/admin/shift-assignments',
			summary: 'Tetapkan atau perbarui penugasan shift karyawan pada tanggal tertentu (YYYY-MM-DD).',
			capability: 'admin.schedules.manage (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				user_id: '0195f000-0000-7000-8000-000000000001',
				shift_id: '0195f000-0000-7000-8000-000000000030',
				work_date: '2026-10-26'
			},
			responseExample: null
		},
		{
			id: 'schedule-me',
			category: 'Shift & Jadwal',
			method: 'GET',
			path: '/v1/schedule',
			summary: 'Daftar jadwal shift kerja milik pengguna yang sedang login.',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: [
				{
					date: '2026-10-25',
					name: 'Shift Pagi Reguler',
					starts_at: '08:00:00',
					ends_at: '17:00:00',
					crosses_midnight: false
				}
			]
		},
		{
			id: 'employees-directory',
			category: 'Karyawan & Hirarki',
			method: 'GET',
			path: '/v1/admin/employee-directory?q=&role=&status=&page=1',
			summary: 'Direktori karyawan dengan pencarian trigram (pg_trgm), filter peran/status, dan paginasi.',
			capability: 'admin.employees.view',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: {
				items: [],
				stats: { total: 1, active: 1, inactive: 0 }
			}
		},
		{
			id: 'employees-create',
			category: 'Karyawan & Hirarki',
			method: 'POST',
			path: '/v1/admin/employees',
			summary: 'Tambahkan anggota karyawan baru beserta kode NIP dan atasan langsung (manager_user_id).',
			capability: 'admin.employees.manage (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				full_name: 'Siti Rahmawati',
				email: 'siti@nusantara.id',
				password: 'initial-employee-password-123',
				employee_code: 'EMP-2026-001',
				manager_user_id: null
			},
			responseExample: {
				id: '0195f000-0000-7000-8000-000000000040',
				full_name: 'Siti Rahmawati',
				email: 'siti@nusantara.id',
				role: 'employee',
				employee_code: 'EMP-2026-001'
			}
		},
		{
			id: 'requests-list',
			category: 'Pengajuan & Approval',
			method: 'GET',
			path: '/v1/requests',
			summary: 'Daftar pengajuan cuti, lembur, koreksi absensi, reimbursement, WFH, kasbon, dan tukar shift milik pengguna.',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: []
		},
		{
			id: 'requests-create',
			category: 'Pengajuan & Approval',
			method: 'POST',
			path: '/v1/requests',
			summary: 'Kirim pengajuan baru (leave, overtime, attendance_correction, reimbursement, wfh, cash_advance, shift_swap).',
			capability: 'Terautentikasi (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				kind: 'leave',
				starts_on: '2026-11-01',
				ends_on: '2026-11-02',
				minutes: null,
				amount_cents: null,
				target_user_id: null,
				attachment_file_id: null,
				reason: 'Keperluan keluarga di luar kota'
			},
			responseExample: {
				id: '0195f000-0000-7000-8000-000000000050',
				kind: 'leave',
				starts_on: '2026-11-01',
				ends_on: '2026-11-02',
				minutes: null,
				amount_cents: null,
				target_user_id: null,
				target_user_name: null,
				attachment_file_id: null,
				attachment_file_name: null,
				attachment_download_url: null,
				reason: 'Keperluan keluarga di luar kota',
				status: 'pending_manager'
			}
		},
		{
			id: 'approvals-list',
			category: 'Pengajuan & Approval',
			method: 'GET',
			path: '/v1/approvals',
			summary: 'Kotak masuk persetujuan 2 tahap (Manager -> Admin/Owner) untuk bawahan atau perusahaan.',
			capability: 'requests.approvals.view',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: []
		},
		{
			id: 'payrolls-list',
			category: 'Payroll & Slip Gaji',
			method: 'GET',
			path: '/v1/payrolls',
			summary: 'Daftar periode penggajian dan rincian slip gaji (THP, gaji pokok, potongan).',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: { periods: [], records: [] }
		},
		{
			id: 'payrolls-save',
			category: 'Payroll & Slip Gaji',
			method: 'POST',
			path: '/v1/admin/payrolls',
			summary: 'Terbitkan atau perbarui perhitungan slip gaji karyawan pada periode tertentu.',
			capability: 'admin.dashboard.view (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				user_id: '0195f000-0000-7000-8000-000000000001',
				period_start: '2026-10-01',
				period_end: '2026-10-31',
				status: 'published',
				gross_amount: 12500000,
				deduction_amount: 500000,
				net_amount: 12000000
			},
			responseExample: { status: 'saved' }
		},
		{
			id: 'storage-config',
			category: 'Object Storage',
			method: 'GET',
			path: '/v1/storage/config',
			summary: 'Informasi driver Object Storage aktif (local, r2, s3, minio), bucket, dan region.',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: {
				driver: 'local',
				bucket: 'paspapan',
				region: 'auto',
				endpoint: 'https://account-id.r2.cloudflarestorage.com',
				force_path_style: true
			}
		},
		{
			id: 'files-list',
			category: 'Object Storage',
			method: 'GET',
			path: '/v1/files',
			summary: 'Daftar berkas privat perusahaan di Object Storage beserta URL unduhan / Presigned SigV4 URL.',
			capability: 'Terautentikasi',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: []
		},
		{
			id: 'files-upload',
			category: 'Object Storage',
			method: 'POST',
			path: '/v1/files',
			summary: 'Unggah berkas (Base64 payload maks 10 MB) ke driver Object Storage aktif (local/r2/s3/minio).',
			capability: 'Terautentikasi (Wajib Origin)',
			headers: {
				Origin: 'http://127.0.0.1:4173',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>',
				'Content-Type': 'application/json'
			},
			requestBody: {
				file_name: 'bukti-reimbursement.txt',
				content_type: 'text/plain',
				data_base64: 'data:text/plain;base64,U2xpcCBCdWt0aSBSZWltYnVyc2VtZW50IFBhc1BhcGFu'
			},
			responseExample: {
				id: '0195f000-0000-7000-8000-000000000060',
				file_name: 'bukti-reimbursement.txt',
				driver: 'local',
				download_url: '/v1/files/0195f000-0000-7000-8000-000000000060/download'
			}
		},
		{
			id: 'access-overview',
			category: 'RBAC, Audit & PG 18',
			method: 'GET',
			path: '/v1/admin/access',
			summary: 'Konfigurasi RBAC perusahaan: peran kustom, override grant/deny anggota, dan 50 audit akses.',
			capability: 'admin.access.manage (Superadmin)',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: { catalog: [], roles: [], members: [], audit: [] }
		},
		{
			id: 'system-health',
			category: 'RBAC, Audit & PG 18',
			method: 'GET',
			path: '/v1/admin/system-health',
			summary: 'Telemetri real-time PostgreSQL 18 (pg_stat_activity, pg_stat_database, pg_extension) & SQLx Pool.',
			capability: 'admin.dashboard.view',
			headers: {
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: {
				postgres: {
					server_version: '18.6 (Homebrew)',
					database_size_pretty: '9120 kB',
					activity: { total_connections: 5, active_queries: 1, idle_connections: 4 }
				},
				sqlx_pool: { size: 5, idle: 4, active: 1 }
			}
		},
		{
			id: 'events-stream',
			category: 'RBAC, Audit & PG 18',
			method: 'GET',
			path: '/v1/events/stream',
			summary: 'Server-Sent Events (text/event-stream) untuk sinkronisasi notifikasi & presensi real-time tanpa reload.',
			capability: 'Terautentikasi',
			headers: {
				Accept: 'text/event-stream',
				Cookie: 'paspapan_session=<64-hex-token>',
				'x-paspapan-service-key': '<API_INTERNAL_KEY>'
			},
			responseExample: 'retry: 4000\nevent: pulse\ndata: {"unread_notifications":0,"pending_approvals":0,"checked_in_today":1}'
		}
	];

	let meta = $state<ApiDocMeta | null>(null);
	let loading = $state(true);
	let error = $state('');
	let copyNotice = $state('');
	let activeGuideTab = $state<'headers' | 'flow' | 'postman'>('headers');
	let search = $state('');
	let categoryFilter = $state('Semua');
	let methodFilter = $state<'ALL' | 'GET' | 'POST'>('ALL');

	let selectedEndpoint = $state<EndpointSpec>(endpoints[3]);
	let customPath = $state(endpoints[3].path);
	let customBody = $state('');
	let hitting = $state(false);
	let hitStatus = $state<number | null>(null);
	let hitDurationMs = $state<number | null>(null);
	let hitResponseText = $state('');
	let hitResponseHeaders = $state<[string, string][]>([]);

	let categories = $derived(['Semua', ...new Set(endpoints.map((item) => item.category))]);
	let filteredEndpoints = $derived(
		endpoints.filter(
			(item) =>
				(categoryFilter === 'Semua' || item.category === categoryFilter) &&
				(methodFilter === 'ALL' || item.method === methodFilter) &&
				`${item.path} ${item.summary} ${item.category} ${item.capability}`
					.toLocaleLowerCase('id')
					.includes(search.trim().toLocaleLowerCase('id'))
		)
	);

	function selectEndpoint(item: EndpointSpec) {
		selectedEndpoint = item;
		customPath = item.path;
		customBody = item.requestBody ? JSON.stringify(item.requestBody, null, 2) : '';
		hitStatus = null;
		hitDurationMs = null;
		hitResponseText = '';
		hitResponseHeaders = [];
	}

	function buildCurlCommand(item: EndpointSpec): string {
		const origin = typeof window !== 'undefined' ? window.location.origin : 'http://127.0.0.1:4173';
		const lines = [`curl -i -X ${item.method} '${origin}${customPath}'`];
		lines.push(`  -H 'Origin: ${origin}'`);
		lines.push(`  -H 'x-paspapan-service-key: <API_INTERNAL_KEY>'`);
		lines.push(`  -H 'Cookie: paspapan_session=<SESSION_TOKEN>'`);
		if (item.method === 'POST' && customBody.trim()) {
			lines.push(`  -H 'Content-Type: application/json'`);
			lines.push(`  --data '${customBody.replace(/'/g, "'\\''")}'`);
		}
		return lines.join(' \\\n');
	}

	async function copyText(text: string, label: string) {
		try {
			await navigator.clipboard.writeText(text);
			copyNotice = `${t(label)} ${t('berhasil disalin ke clipboard.')}`;
			setTimeout(() => {
				copyNotice = '';
			}, 2500);
		} catch {
			copyNotice = t('Gagal menyalin ke clipboard.');
		}
	}

	async function runLiveHit() {
		hitting = true;
		hitStatus = null;
		hitDurationMs = null;
		hitResponseText = '';
		hitResponseHeaders = [];
		const started = performance.now();
		try {
			const init: RequestInit = {
				method: selectedEndpoint.method,
				credentials: 'include',
				headers: {
					Accept: 'application/json, text/event-stream',
					...(selectedEndpoint.method === 'POST' && customBody.trim()
						? { 'Content-Type': 'application/json' }
						: {})
				},
				body: selectedEndpoint.method === 'POST' && customBody.trim() ? customBody : undefined
			};
			const response = await fetch(customPath, init);
			hitDurationMs = Math.round(performance.now() - started);
			hitStatus = response.status;
			hitResponseHeaders = [...response.headers.entries()];
			const raw = await response.text();
			try {
				hitResponseText = JSON.stringify(JSON.parse(raw), null, 2);
			} catch {
				hitResponseText = raw || '(204 No Content / Empty Body)';
			}
		} catch (cause) {
			hitDurationMs = Math.round(performance.now() - started);
			hitStatus = 0;
			hitResponseText = cause instanceof Error ? cause.message : t('Gagal menghubungi endpoint.');
		} finally {
			hitting = false;
		}
	}

	function downloadJsonFile(filename: string, payload: unknown) {
		const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json;charset=utf-8' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = filename;
		a.click();
		URL.revokeObjectURL(url);
		copyNotice = `${t('Berkas')} ${filename} ${t('berhasil diunduh.')}`;
	}

	function downloadPostmanCollection() {
		const origin = typeof window !== 'undefined' ? window.location.origin : 'http://127.0.0.1:4173';
		const grouped = [...new Set(endpoints.map((e) => e.category))].map((category) => ({
			name: category,
			item: endpoints
				.filter((e) => e.category === category)
				.map((e) => ({
					name: `${e.method} ${e.path} — ${e.summary}`,
					event:
						e.id === 'auth-login' || e.id === 'auth-register'
							? [
									{
										listen: 'test',
										script: {
											type: 'text/javascript',
											exec: [
												'pm.test("Status code is 200", function () { pm.response.to.have.status(200); });',
												'const setCookie = pm.response.headers.get("Set-Cookie");',
												'if (setCookie) { console.log("Session cookie issued:", setCookie); }'
											]
										}
									}
								]
							: [],
					request: {
						method: e.method,
						header: [
							{ key: 'Origin', value: '{{appOrigin}}', type: 'text' },
							{ key: 'x-paspapan-service-key', value: '{{serviceKey}}', type: 'text' },
							...(e.method === 'POST'
								? [{ key: 'Content-Type', value: 'application/json', type: 'text' }]
								: [])
						],
						body: e.requestBody
							? {
									mode: 'raw',
									raw: JSON.stringify(e.requestBody, null, 2),
									options: { raw: { language: 'json' } }
								}
							: undefined,
						url: {
							raw: `{{baseUrl}}${e.path}`,
							host: ['{{baseUrl}}'],
							path: e.path.replace(/^\//, '').split('?')[0].split('/')
						},
						description: `${e.summary}\n\nRequired Capability: ${e.capability}`
					}
				}))
		}));

		const collection = {
			info: {
				_postman_id: 'a9001122-3344-5566-7788-99aabbccddee',
				name: 'PasPapan Workforce & HR API (v5.0.2)',
				description:
					'Koleksi resmi API PasPapan (SvelteKit 3 + Rust Axum + PostgreSQL 18). Mencakup Auth, Dynamic QR Attendance, Shifts, Employees, 2-Stage Approvals, Payroll, Multi-Driver Object Storage, RBAC, dan Telemetry PostgreSQL 18.',
				schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
			},
			variable: [
				{ key: 'baseUrl', value: origin, type: 'string' },
				{ key: 'directApiUrl', value: 'http://127.0.0.1:3000', type: 'string' },
				{ key: 'appOrigin', value: origin, type: 'string' },
				{ key: 'serviceKey', value: 'paspapan-local-internal-service-key-change-me', type: 'string' }
			],
			item: grouped
		};

		downloadJsonFile('paspapan-api.postman_collection.json', collection);
	}

	function downloadPostmanEnvironment() {
		const origin = typeof window !== 'undefined' ? window.location.origin : 'http://127.0.0.1:4173';
		const env = {
			id: 'b8001122-3344-5566-7788-99aabbccddee',
			name: 'PasPapan Local & Staging Environment',
			values: [
				{ key: 'baseUrl', value: origin, type: 'default', enabled: true },
				{ key: 'directApiUrl', value: 'http://127.0.0.1:3000', type: 'default', enabled: true },
				{ key: 'appOrigin', value: origin, type: 'default', enabled: true },
				{
					key: 'serviceKey',
					value: 'paspapan-local-internal-service-key-change-me',
					type: 'secret',
					enabled: true
				}
			],
			_postman_variable_scope: 'environment'
		};
		downloadJsonFile('paspapan-env.postman_environment.json', env);
	}

	function downloadOpenApiSpec() {
		const origin = typeof window !== 'undefined' ? window.location.origin : 'http://127.0.0.1:4173';
		const paths: Record<string, Record<string, unknown>> = {};
		for (const ep of endpoints) {
			const cleanPath = ep.path.split('?')[0];
			paths[cleanPath] ??= {};
			paths[cleanPath][ep.method.toLowerCase()] = {
				tags: [ep.category],
				summary: ep.summary,
				description: `Required Capability: **${ep.capability}**`,
				parameters: [
					{
						name: 'x-paspapan-service-key',
						in: 'header',
						required: cleanPath !== '/health',
						schema: { type: 'string' }
					},
					...(ep.method === 'POST'
						? [{ name: 'Origin', in: 'header', required: true, schema: { type: 'string', example: origin } }]
						: [])
				],
				...(ep.requestBody
					? {
							requestBody: {
								required: true,
								content: { 'application/json': { example: ep.requestBody } }
							}
						}
					: {}),
				responses: {
					'200': {
						description: 'Respons sukses',
						content: { 'application/json': { example: ep.responseExample } }
					}
				}
			};
		}
		const openapi = {
			openapi: '3.1.0',
			info: {
				title: 'PasPapan Workforce & HR Platform API',
				version: meta?.version ?? '5.0.2',
				description: 'Spesifikasi OpenAPI 3.1 untuk PasPapan Rust Axum API + PostgreSQL 18.'
			},
			servers: [
				{ url: origin, description: 'Same-Origin SvelteKit Proxy (Recommended)' },
				{ url: 'http://127.0.0.1:3000', description: 'Direct Rust Axum API (Requires x-paspapan-service-key)' }
			],
			paths
		};
		downloadJsonFile('paspapan-openapi-3.1.json', openapi);
	}

	onMount(async () => {
		loading = true;
		error = '';
		try {
			meta = await request<ApiDocMeta>('/v1/admin/api-docs');
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Gagal memuat metadata API Docs Superadmin.');
		} finally {
			loading = false;
		}
	});
</script>

<svelte:head><title>API Docs &amp; Postman · Superadmin PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page api-docs-page" tabindex="-1">
	<header class="page-heading">
		<div>
			<p class="eyebrow">{t('DOKUMENTASI INTERNAL SUPERADMIN')} (OWNER ONLY · `admin.access.manage`)</p>
			<h1>PasPapan API Center &amp; Live Playground</h1>
			<p>{t('Panduan arsitektur keamanan, referensi header wajib, pengujian endpoint interaktif, dan ekspor koleksi Postman/OpenAPI.')}</p>
		</div>
		<div class="export-actions">
			<button type="button" onclick={downloadPostmanCollection}>
				<Icon name="arrow" size={15} />{t('Unduh Postman Collection')}
			</button>
			<button type="button" class="quiet" onclick={downloadPostmanEnvironment}>
				{t('Unduh Postman Env')}
			</button>
			<button type="button" class="quiet" onclick={downloadOpenApiSpec}>
				OpenAPI 3.1 JSON
			</button>
		</div>
	</header>

	{#if error}<p role="alert">{error}</p>{/if}
	{#if copyNotice}<p class="notice" role="status">{copyNotice}</p>{/if}

	<section class="runtime-strip" aria-label={t('Status runtime keamanan API')}>
		<article class="runtime-card">
			<span>{t('Akses Dokumen')}</span>
			<strong>Superadmin ({session.current?.company.role ?? 'owner'})</strong>
		</article>
		<article class="runtime-card">
			<span>Service Key (`x-paspapan-service-key`)</span>
			<strong>{meta?.runtime.service_key_enabled ? t('Aktif (Constant-Time)') : t('Siaga')}</strong>
		</article>
		<article class="runtime-card">
			<span>Session Hash (`SESSION_SECRET`)</span>
			<strong>{meta?.runtime.session_hmac_enabled ? 'HMAC-SHA256 Pepper' : 'SHA-256'}</strong>
		</article>
		<article class="runtime-card">
			<span>Database &amp; Storage</span>
			<strong>PG {meta?.runtime.postgres_version ?? '18'} · {(meta?.runtime.storage_driver ?? 'local').toUpperCase()}</strong>
		</article>
	</section>

	<section class="panel guide-panel" aria-label={t('Panduan integrasi dan standar header')}>
		<div class="guide-tabs" role="tablist">
			<button type="button" class:active={activeGuideTab === 'headers'} class="quiet" onclick={() => (activeGuideTab = 'headers')}>
				1. {t('Standar Header & Keamanan')}
			</button>
			<button type="button" class:active={activeGuideTab === 'flow'} class="quiet" onclick={() => (activeGuideTab = 'flow')}>
				2. {t('Panduan Alur Integrasi')}
			</button>
			<button type="button" class:active={activeGuideTab === 'postman'} class="quiet" onclick={() => (activeGuideTab = 'postman')}>
				3. {t('Cara Pakai di Postman')}
			</button>
		</div>

		{#if activeGuideTab === 'headers'}
			<div class="guide-grid">
				<div class="guide-card">
					<p class="eyebrow">{t('LAPIS 1 · SERVICE-TO-SERVICE GUARD')}</p>
					<h3>Header `x-paspapan-service-key`</h3>
					<p>{t('Seluruh request ke Rust API wajib menyertakan service key yang cocok. Proxy SvelteKit menyisipkan header ini secara otomatis.')}</p>
					<code>x-paspapan-service-key: &lt;API_INTERNAL_KEY&gt;</code>
				</div>
				<div class="guide-card">
					<p class="eyebrow">{t('LAPIS 2 · CSRF & ORIGIN GUARD')}</p>
					<h3>{t('Header Origin pada Endpoint Mutasi (POST)')}</h3>
					<p>{t('Setiap endpoint POST memvalidasi Origin terhadap APP_ORIGIN. Origin yang tidak cocok ditolak dengan 403.')}</p>
					<code>Origin: http://127.0.0.1:4173</code>
				</div>
				<div class="guide-card">
					<p class="eyebrow">{t('LAPIS 3 · AUTENTIKASI SESI')}</p>
					<h3>Cookie `paspapan_session` (`HttpOnly`)</h3>
					<p>{t('Cookie diterbitkan saat login/register, disimpan sebagai hash HMAC-SHA256, dan selalu diperiksa terhadap status aktif karyawan.')}</p>
					<code>Cookie: paspapan_session=&lt;64-char-hex&gt;</code>
				</div>
			</div>
		{:else if activeGuideTab === 'flow'}
			<div class="guide-grid">
				<div class="guide-card">
					<p class="eyebrow">{t('LANGKAH 1 · LOGIN & SESI')}</p>
					<h3>{t('Panggil POST /v1/auth/login')}</h3>
					<p>{t('Kirim email dan password beserta Origin, simpan cookie sesi, lalu verifikasi capability melalui endpoint sesi.')}</p>
				</div>
				<div class="guide-card">
					<p class="eyebrow">{t('LANGKAH 2 · DYNAMIC QR & GEOFENCE')}</p>
					<h3>{t('Rotasi 30 Detik + Haversine Check-In')}</h3>
					<p>{t('Layar kantor merotasi token setiap 30 detik. Karyawan mengirim token hasil pindai bersama koordinat GPS saat check-in.')}</p>
				</div>
				<div class="guide-card">
					<p class="eyebrow">{t('LANGKAH 3 · OBJECT STORAGE & SSE')}</p>
					<h3>Presigned SigV4 &amp; Real-time Stream</h3>
					<p>{t('Unggah lampiran melalui endpoint file. Untuk pembaruan real-time tanpa polling, gunakan EventSource pada stream peristiwa.')}</p>
				</div>
			</div>
		{:else}
			<div class="guide-grid">
				<div class="guide-card">
					<p class="eyebrow">{t('LANGKAH 1 · IMPOR KOLEKSI')}</p>
					<h3>{t('Unduh koleksi dan environment Postman')}</h3>
					<p>{t('Impor kedua berkas JSON ke Postman, lalu aktifkan environment PasPapan Local & Staging.')}</p>
				</div>
				<div class="guide-card">
					<p class="eyebrow">{t('LANGKAH 2 · OTOMATISASI COOKIE')}</p>
					<h3>{t('Jalankan login pertama kali')}</h3>
					<p>{t('Cookie jar Postman menyimpan cookie sesi secara otomatis sehingga endpoint terautentikasi dapat langsung digunakan.')}</p>
				</div>
				<div class="guide-card">
					<p class="eyebrow">{t('LANGKAH 3 · API LANGSUNG VS PROXY')}</p>
					<h3>{t('Variabel baseUrl & serviceKey')}</h3>
					<p>{t('Gunakan baseUrl frontend untuk proxy SvelteKit, atau alamat API langsung dengan serviceKey yang sesuai.')}</p>
				</div>
			</div>
		{/if}
	</section>

	<div class="docs-workspace">
		<section class="panel catalog-panel" aria-label={t('Katalog endpoint API')}>
			<div class="catalog-filters">
				<input type="search" bind:value={search} placeholder={t('Cari path atau deskripsi endpoint…')} />
				<div class="filter-row">
					<select bind:value={categoryFilter} aria-label={t('Filter kategori')}>
						{#each categories as cat}<option value={cat}>{t(cat)}</option>{/each}
					</select>
					<select bind:value={methodFilter} aria-label={t('Filter HTTP method')}>
						<option value="ALL">{t('Semua Method')}</option>
						<option value="GET">GET</option>
						<option value="POST">POST</option>
					</select>
				</div>
			</div>

			<div class="endpoint-list">
				{#each filteredEndpoints as ep (ep.id)}
					<button
						type="button"
						class="endpoint-item"
						class:selected={selectedEndpoint.id === ep.id}
						onclick={() => selectEndpoint(ep)}
					>
						<span class="method-badge method-{ep.method.toLowerCase()}">{ep.method}</span>
						<span class="endpoint-copy">
							<code>{ep.path}</code>
							<small>{t(ep.summary)}</small>
						</span>
					</button>
				{/each}
			</div>
		</section>

		<section class="panel playground-panel" aria-label={t('Playground interaktif endpoint')}>
			<div class="playground-header">
				<div>
					<p class="eyebrow">{t(selectedEndpoint.category)} · Capability: {t(selectedEndpoint.capability)}</p>
					<h2>
						<span class="method-badge method-{selectedEndpoint.method.toLowerCase()}">{selectedEndpoint.method}</span>
						<code>{selectedEndpoint.path}</code>
					</h2>
					<p class="muted">{t(selectedEndpoint.summary)}</p>
				</div>
				<div class="playground-actions">
					<button type="button" class="quiet" onclick={() => copyText(buildCurlCommand(selectedEndpoint), 'Perintah cURL')}>
						{t('Salin cURL')}
					</button>
					<button type="button" onclick={runLiveHit} disabled={hitting}>
						{hitting ? t('Mengirim…') : t('Hit Endpoint Sekarang')}
					</button>
				</div>
			</div>

			<label class="playground-field">
				{t('Path / Query Target (menggunakan sesi browser Superadmin saat ini)')}
				<input type="text" bind:value={customPath} />
			</label>

			<div class="spec-columns">
				<div>
					<h3>{t('Contoh Header Wajib')}</h3>
					<pre class="code-box">{JSON.stringify(selectedEndpoint.headers, null, 2)}</pre>
				</div>
				<div>
					<h3>{t('Contoh cURL Siap Pakai')}</h3>
					<pre class="code-box">{buildCurlCommand(selectedEndpoint)}</pre>
				</div>
			</div>

			{#if selectedEndpoint.method === 'POST'}
				<label class="playground-field">
					{t('Request Body JSON (dapat diedit sebelum menjalankan endpoint)')}
					<textarea rows="7" bind:value={customBody} spellcheck="false"></textarea>
				</label>
			{/if}

			{#if hitStatus !== null}
				<div class="live-response">
					<div class="response-bar">
						<strong>{t('Hasil Live Hit:')}</strong>
						<span class="badge" class:status-error={hitStatus >= 400 || hitStatus === 0}>
							HTTP {hitStatus} · {hitDurationMs} ms
						</span>
					</div>
					<pre class="code-box response-box">{hitResponseText}</pre>
				</div>
			{:else}
				<div>
					<h3>{t('Contoh Respons Standar')}</h3>
					<pre class="code-box">{JSON.stringify(selectedEndpoint.responseExample, null, 2)}</pre>
				</div>
			{/if}
		</section>
	</div>
</main>

<style>
	.export-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
	.runtime-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
	.runtime-card { display: grid; gap: 2px; padding: 9px 12px; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); box-shadow: var(--shadow-soft); }
	.runtime-card span { font-size: 10.5px; font-weight: 650; color: var(--muted); }
	.runtime-card strong { font-size: 13px; font-weight: 800; color: var(--ink); }

	.guide-panel { margin-bottom: 12px; }
	.guide-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; border-bottom: 1px solid var(--line); padding-bottom: 10px; }
	.guide-tabs button.active { background: var(--primary-soft); border-color: var(--primary); color: var(--primary-strong); }
	.guide-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
	.guide-card { padding: 11px 12px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface-alt); display: grid; gap: 5px; align-content: start; }
	.guide-card h3 { margin: 0; font-size: 13.5px; font-weight: 750; }
	.guide-card p { font-size: 12px; color: var(--muted); line-height: 1.45; }
	.guide-card code { font-size: 11px; padding: 5px 7px; border-radius: var(--radius-sm); background: var(--surface); border: 1px solid var(--line); color: var(--primary-strong); overflow-x: auto; }

	.docs-workspace { display: grid; grid-template-columns: minmax(290px, 370px) minmax(0, 1fr); gap: 12px; align-items: start; }
	.catalog-filters { display: grid; gap: 7px; margin-bottom: 10px; }
	.filter-row { display: grid; grid-template-columns: 1fr 120px; gap: 6px; }
	.endpoint-list { display: grid; gap: 5px; max-height: 620px; overflow-y: auto; padding-right: 2px; }
	.endpoint-item { display: flex; align-items: flex-start; gap: 8px; width: 100%; padding: 8px 9px !important; border: 1px solid var(--line) !important; border-radius: var(--radius-sm) !important; background: var(--surface) !important; color: var(--ink) !important; text-align: start; box-shadow: none !important; }
	.endpoint-item:hover, .endpoint-item.selected { background: var(--primary-soft) !important; border-color: var(--primary) !important; }
	.method-badge { display: inline-grid; place-items: center; min-width: 44px; padding: 2px 6px; border-radius: var(--radius-sm); font-size: 10px; font-weight: 800; letter-spacing: .04em; flex-shrink: 0; }
	.method-get { background: #e0f2fe; color: #0369a1; }
	.method-post { background: #dcfce7; color: #15803d; }
	.endpoint-copy { display: grid; gap: 2px; min-width: 0; }
	.endpoint-copy code { font-size: 11.5px; font-weight: 750; color: var(--ink); overflow-wrap: anywhere; }
	.endpoint-copy small { font-size: 11px; color: var(--muted); font-weight: 500; line-height: 1.3; }

	.playground-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--line); margin-bottom: 12px; }
	.playground-header h2 { display: flex; align-items: center; gap: 8px; font-size: 15px; margin: 4px 0; overflow-wrap: anywhere; }
	.playground-actions { display: flex; gap: 6px; flex-shrink: 0; }
	.playground-field { margin: 10px 0; }
	.playground-field textarea { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
	.spec-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin: 10px 0; }
	h3 { font-size: 12px; font-weight: 750; color: var(--muted); margin: 0 0 6px; text-transform: uppercase; letter-spacing: .04em; }
	.code-box { margin: 0; padding: 10px 12px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: #141e18; color: #e4f0e8; font-size: 11.5px; line-height: 1.45; overflow-x: auto; white-space: pre-wrap; overflow-wrap: anywhere; }
	.live-response { margin-top: 12px; }
	.response-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
	.status-error { background: #ffe4e6 !important; color: #be123c !important; }

	@media (max-width: 980px) {
		.runtime-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.guide-grid, .docs-workspace, .spec-columns { grid-template-columns: 1fr; }
		.playground-header { flex-direction: column; }
	}
</style>
