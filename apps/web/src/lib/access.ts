import { legacyDestination } from './legacy-destinations.ts';

export const permissionLabels: Record<string, string> = {
    'admin.dashboard.view': 'Buka dashboard admin',
    'admin.employees.view': 'Lihat employee',
    'admin.employees.manage': 'Tambah & edit employee',
    'admin.employees.manage_status': 'Ubah status kerja employee',
    'admin.shifts.manage': 'Lihat & buat shift',
    'admin.barcodes.manage': 'Kelola QR lokasi absensi',
    'admin.schedules.manage': 'Tetapkan jadwal',
    'requests.approvals.view': 'Lihat inbox persetujuan',
    'requests.approvals.manager': 'Putuskan tahap manager',
    'requests.approvals.admin': 'Putuskan tahap admin',
    'requests.approvals.company': 'Jangkau pengajuan seluruh perusahaan',
    'admin.access.manage': 'Kelola hak akses (owner)',
    'admin.workspace_records.manage': 'Kelola modul operasional',
    'admin.files.manage': 'Kelola seluruh berkas perusahaan',
    'admin.attendances.view': 'Lihat presensi perusahaan',
    'admin.attendances.manage': 'Impor presensi perusahaan',
    'admin.audit.view': 'Lihat audit trail',
    'admin.payroll.view': 'Lihat payroll perusahaan',
    'admin.payroll.manage': 'Kelola payroll perusahaan',
    'admin.system.view': 'Lihat kesehatan sistem',
    'admin.announcements.manage': 'Kelola pengumuman',
    'admin.assets.manage': 'Kelola inventaris dan serah terima',
    'admin.organization.manage': 'Kelola master data organisasi',
    'admin.performance.manage': 'Kelola target dan penilaian KPI',
    'admin.documents.manage': 'Kelola penerbitan dokumen HR',
    'admin.checklists.manage': 'Kelola onboarding dan offboarding'
};

export const operationalLinks = [
    { href: '/admin/dashboard', label: 'Dashboard admin', group: 'Ringkasan', icon: 'home', permission: 'admin.dashboard.view' },
    { href: '/admin/employees', label: 'Employee', group: 'Tim', icon: 'people', permission: 'admin.employees.view' },
    { href: '/admin/attendance', label: 'Shift & jadwal', group: 'Tim', icon: 'calendar', permission: 'admin.shifts.manage' },
    { href: '/admin/barcodes', label: 'QR & lokasi absensi', group: 'Tim', icon: 'qr', permission: 'admin.barcodes.manage' },
    { href: '/app/approvals', label: 'Persetujuan', group: 'Tim', icon: 'check', permission: 'requests.approvals.view' },
    { href: '/admin/access', label: 'Hak akses', group: 'Keamanan', icon: 'people', permission: 'admin.access.manage' }
];

export function visibleOperations(capabilities: readonly string[] = []) {
    return operationalLinks.filter(link => capabilities.includes(link.permission));
}

export function requiredPermission(path: string, admin: boolean): string | null {
    path = path.split(/[?#]/)[0].replace(/\/+$/, '') || '/';
    const destination = legacyDestination(path)?.destination.split(/[?#]/)[0];
    if (destination && destination !== path) return requiredPermission(destination, destination.startsWith('/admin/'));
	if (path === '/admin/hr-checklists') return 'admin.checklists.manage';
	if (['/admin/hr-documents','/admin/document-requests'].includes(path)) return 'admin.documents.manage';
	if (['/admin/kpi','/admin/appraisals','/admin/settings/kpi'].includes(path)) return 'admin.performance.manage';
	if (['/admin/leave-entitlements','/admin/leaves/quotas','/admin/masterdata/leave-entitlements'].includes(path)) return 'admin.organization.manage';
	if (path === '/admin/organization' || path === '/admin/holidays' || ['/admin/masterdata/division', '/admin/masterdata/job-title', '/admin/masterdata/education', '/admin/masterdata/leave-types'].includes(path)) return 'admin.organization.manage';
	if (path === '/admin/assets') return 'admin.assets.manage';
	if (path === '/admin/announcements') return 'admin.announcements.manage';
	if (['/admin/leaves', '/admin/attendance-corrections', '/admin/shift-swaps', '/admin/cash-advances', '/admin/manage-kasbon'].includes(path)) return 'requests.approvals.view';
    if (path === '/admin/api-docs' || path.startsWith('/admin/api-docs/') || path === '/admin/integrations' || path.startsWith('/admin/integrations/')) return 'admin.access.manage';
	if (path === '/admin/attendances' || path === '/admin/import-export/attendances') return 'admin.attendances.view';
	if (path === '/admin/activity-logs') return 'admin.audit.view';
	if (path.startsWith('/admin/payrolls')) return 'admin.payroll.view';
    if (path === '/admin/system-info' || path === '/admin/operational-health' || path === '/admin/settings') return 'admin.system.view';
    if (path === '/admin/overtime' || path.startsWith('/admin/overtime/')) return 'requests.approvals.view';
    if (path === '/admin/reimbursements' || path.startsWith('/admin/reimbursements/')) return 'requests.approvals.view';
    if (path === '/admin/schedules' || path.startsWith('/admin/schedules/')) return 'admin.schedules.manage';
    if (path === '/admin/masterdata/shift' || path.startsWith('/admin/masterdata/shift/')) return 'admin.shifts.manage';
    if (path === '/admin/barcodes' || path.startsWith('/admin/barcodes/')) return 'admin.barcodes.manage';
    return operationalLinks.find(link => path === link.href || path.startsWith(link.href + '/'))?.permission
        ?? (admin ? 'admin.workspace_records.manage' : null);
}
