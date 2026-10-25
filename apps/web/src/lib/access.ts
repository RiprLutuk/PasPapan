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
    'admin.access.manage': 'Kelola hak akses (owner)'
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
    if (path === '/admin/api-docs' || path.startsWith('/admin/api-docs/')) return 'admin.access.manage';
    if (path === '/admin/schedules' || path.startsWith('/admin/schedules/')) return 'admin.schedules.manage';
    if (path === '/admin/masterdata/shift' || path.startsWith('/admin/masterdata/shift/')) return 'admin.shifts.manage';
    if (path === '/admin/barcodes' || path.startsWith('/admin/barcodes/')) return 'admin.barcodes.manage';
    return operationalLinks.find(link => path === link.href || path.startsWith(link.href + '/'))?.permission
        ?? (admin ? 'admin.dashboard.view' : null);
}
