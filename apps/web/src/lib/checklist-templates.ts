/** Starter content is copied into the editor; only saved company templates have database IDs. */
export const checklistStarters = [
	{ id:'starter:onboarding', kind:'onboarding', title:'Onboarding karyawan baru', items:[
		'Lengkapi data dan dokumen karyawan.',
		'Konfirmasi kontrak dan kebijakan perusahaan.',
		'Siapkan akun kerja dan hak akses.',
		'Serah terima perangkat dan perlengkapan kerja.',
		'Perkenalkan tim, jadwal, dan prosedur presensi.',
		'Jadwalkan orientasi dan evaluasi awal.'
	]},
	{ id:'starter:offboarding', kind:'offboarding', title:'Offboarding karyawan', items:[
		'Konfirmasi tanggal terakhir bekerja.',
		'Selesaikan serah terima pekerjaan dan dokumen.',
		'Kembalikan seluruh aset dan perlengkapan perusahaan.',
		'Selesaikan kewajiban administrasi dan keuangan.',
		'Jadwalkan pencabutan akun dan hak akses.',
		'Lakukan wawancara keluar dan arsipkan dokumen.'
	]}
] as const;
