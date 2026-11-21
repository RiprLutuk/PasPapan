<script lang="ts">
	import { PageHeader, RefreshButton } from '../../../../lib/ui';
	import { request } from '../../../../lib/api';
	import { uiText as t } from '../../../../lib/i18n';

	type ImportRow = { full_name: string; email: string; employee_code: string; password: string };
	type ImportResult = { rows: number; dry_run: boolean };

	let source = $state('full_name,email,employee_code,password\n');
	let rows = $state<ImportRow[]>([]);
	let error = $state('');
	let notice = $state('');
	let busy = $state(false);

	function csv(input: string): string[][] {
		const values: string[][] = [[]];
		let field = '';
		let quoted = false;
		for (let index = 0; index < input.length; index += 1) {
			const char = input[index];
			if (char === '"') {
				if (quoted && input[index + 1] === '"') { field += '"'; index += 1; }
				else quoted = !quoted;
			} else if (char === ',' && !quoted) { values.at(-1)?.push(field); field = ''; }
			else if ((char === '\n' || char === '\r') && !quoted) {
				if (char === '\r' && input[index + 1] === '\n') index += 1;
				values.at(-1)?.push(field); field = '';
				if (values.at(-1)?.some((value) => value.trim())) values.push([]);
			} else field += char;
		}
		if (quoted) throw new Error(t('CSV memiliki tanda kutip yang belum ditutup.'));
		if (field || values.at(-1)?.length) values.at(-1)?.push(field);
		return values.filter((line) => line.some((value) => value.trim()));
	}

	function parse() {
		error = ''; notice = '';
		try {
			const [headers, ...records] = csv(source);
			const fields = ['full_name', 'email', 'employee_code', 'password'];
			if (!headers || fields.some((field) => !headers.includes(field))) throw new Error(t('Header CSV wajib: full_name,email,employee_code,password.'));
			if (!records.length || records.length > 200) throw new Error(t('CSV harus berisi 1 sampai 200 karyawan.'));
			rows = records.map((record, index) => {
				if (record.length !== headers.length) throw new Error(`${t('Kolom tidak cocok pada baris')} ${index + 2}.`);
				const row = Object.fromEntries(headers.map((header, position) => [header, record[position]?.trim() ?? '']));
				if (fields.some((field) => !row[field])) throw new Error(`${t('Nilai wajib kosong pada baris')} ${index + 2}.`);
				return row as ImportRow;
			});
		} catch (cause) { rows = []; error = cause instanceof Error ? cause.message : t('CSV tidak dapat dibaca.'); }
	}

	async function submit(dryRun: boolean) {
		parse();
		if (!rows.length || busy) return;
		busy = true; error = ''; notice = '';
		try {
			const result = await request<ImportResult>('/v1/admin/employees/import', { method: 'POST', body: JSON.stringify({ rows, dry_run: dryRun }) });
			notice = result.dry_run ? t('Validasi selesai. Tidak ada data yang disimpan.') : `${result.rows} ${t('karyawan berhasil diimpor. Email onboarding masuk ke antrean pengiriman.')}`;
			if (!result.dry_run) { source = 'full_name,email,employee_code,password\n'; rows = []; }
		} catch (cause) { error = cause instanceof Error ? cause.message : t('Impor gagal. Tidak ada data yang disimpan.'); }
		finally { busy = false; }
	}

	async function downloadExport() {
		busy = true; error = ''; notice = '';
		try {
			const response = await fetch('/v1/admin/employees/export', { credentials: 'include' });
			if (!response.ok) throw new Error((await response.json().catch(() => ({})) as { error?: string }).error ?? t('Ekspor gagal.'));
			const url = URL.createObjectURL(await response.blob());
			const anchor = document.createElement('a'); anchor.href = url; anchor.download = 'employees.csv'; anchor.click(); URL.revokeObjectURL(url);
		} catch (cause) { error = cause instanceof Error ? cause.message : t('Ekspor gagal.'); }
		finally { busy = false; }
	}
</script>

<svelte:head><title>{t('Import & export karyawan')} · PasPapan</title></svelte:head>

<main class="workspace-page import-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Import & export karyawan')} description={t('Impor akun karyawan secara atomik dari CSV. Validasi dulu sebelum menyimpan.')} backHref="/admin/employees">{#snippet actions()}<button class="quiet" disabled={busy} onclick={() => void downloadExport()}>{t('Ekspor CSV')}</button>{/snippet}</PageHeader>
	<section class="panel">
		<h2>{t('CSV karyawan')}</h2>
		<p>{t('Kolom wajib: full_name, email, employee_code, password. Kata sandi minimal 12 karakter. Batch dibatalkan seluruhnya bila satu baris tidak valid atau duplikat.')}</p>
		<form novalidate onsubmit={(event) => { event.preventDefault(); parse(); }}>
			<label>{t('Isi CSV')}<textarea bind:value={source} spellcheck="false" rows="12" aria-describedby="csv-help"></textarea></label>
			<p id="csv-help" class="muted">{t('Gunakan tanda kutip ganda untuk koma di dalam nilai. Jangan gunakan file produksi untuk pengujian.')}</p>
			<div class="actions"><button type="submit" class="quiet" disabled={busy}>{t('Pratinjau')}</button><button type="button" class="quiet" disabled={busy || !rows.length} onclick={() => void submit(true)}>{t('Validasi tanpa menyimpan')}</button><button type="button" disabled={busy || !rows.length} onclick={() => void submit(false)}>{busy ? t('Memproses…') : t('Import karyawan')}</button></div>
		</form>
		{#if error}<p class="alert" role="alert">{error} <RefreshButton onclick={parse} label={t('Coba lagi')} /></p>{/if}
		{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	</section>
	{#if rows.length}
		<section class="panel preview"><h2>{t('Pratinjau')} <span class="badge">{rows.length}</span></h2><div class="table-wrap"><table><thead><tr><th>{t('Nama')}</th><th>Email</th><th>{t('Kode')}</th></tr></thead><tbody>{#each rows as row, index (`${row.email}-${index}`)}<tr><td>{row.full_name}</td><td>{row.email}</td><td>{row.employee_code}</td></tr>{/each}</tbody></table></div></section>
	{/if}
</main>

<style>
	.import-page{display:grid;gap:12px;align-content:start;max-width:1000px}.panel{padding:16px;border-radius:var(--radius)}h2{margin:0;font-size:15px}p{font-size:12px;line-height:1.6;color:var(--muted)}form{display:grid;gap:10px;margin-top:14px}textarea{min-height:220px;font:12px/1.55 ui-monospace,SFMono-Regular,Menlo,monospace;resize:vertical}.muted{font-size:11px;margin:0}.actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}.alert,.notice{display:flex;align-items:center;gap:8px}.alert{color:var(--danger,#b42318)}.notice{color:var(--primary-strong)}.preview{display:grid;gap:12px}.preview h2{display:flex;align-items:center;gap:8px}.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:var(--radius-sm)}table{width:100%;border-collapse:collapse;text-align:start;font-size:12px}th{padding:10px 12px;background:var(--surface-alt);color:var(--muted);font-size:10px}td{padding:10px 12px;border-top:1px solid var(--line);overflow-wrap:anywhere}@media(max-width:640px){.actions>*{flex:1}.actions button:last-child{flex-basis:100%}}
</style>
