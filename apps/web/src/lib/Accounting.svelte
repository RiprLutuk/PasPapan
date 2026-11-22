<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { AlertBanner, PageHeader, RefreshButton } from './ui';

	type Account = { id: string; code: string; name: string; type: string; normal_balance: string };
	type Period = { id: string; starts_on: string; ends_on: string; status: string };
	type Journal = { id: string; number: string; entry_date: string; status: string; reference: string; description: string };
	type Reconciliation = { id: string; account_code: string; statement_date: string; statement_balance_cents: number; ledger_balance_cents: number; difference_cents: number };
	type Balance = { code: string; name: string; type: string; normal_balance: string; balance_cents: number };
	type FinancialReport = { balances: Balance[]; totals: Record<string, number> };

	let accounts = $state<Account[]>([]), periods = $state<Period[]>([]), journals = $state<Journal[]>([]), reconciliations = $state<Reconciliation[]>([]), financial = $state<FinancialReport>({ balances: [], totals: {} });
	let code = $state(''), name = $state(''), kind = $state('asset'), normal = $state('debit');
	let starts = $state(''), ends = $state(''), reconcileAccount = $state(''), statementRupiah = $state<number | null>(null);
	let journalDate = $state(new Date().toISOString().slice(0, 10)), journalReference = $state(''), journalDescription = $state(''), debitAccount = $state(''), creditAccount = $state(''), journalRupiah = $state<number | null>(null);
	let error = $state(''), notice = $state(''), saving = $state(false), loading = $state(true);
	const money = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value / 100);
	const cents = (value: number | null) => Math.round(Number(value ?? 0) * 100);
	function message(cause: unknown) { return cause instanceof Error ? cause.message : 'Perubahan tidak dapat disimpan. Coba lagi.'; }
	function clearFeedback() { error = ''; notice = ''; }
	async function load() {
		loading = true; error = '';
		try {
			const [data, report] = await Promise.all([
				request<{ accounts: Account[]; periods: Period[]; journals: Journal[]; reconciliations: Reconciliation[] }>('/v1/admin/accounting'),
				request<FinancialReport>('/v1/admin/accounting/financial-report')
			]);
			accounts = data.accounts; periods = data.periods; journals = data.journals; reconciliations = data.reconciliations;
			financial = report;
			reconcileAccount ||= accounts[0]?.id ?? ''; debitAccount ||= accounts[0]?.id ?? ''; creditAccount ||= accounts[1]?.id ?? accounts[0]?.id ?? '';
		} catch (cause) { error = message(cause); } finally { loading = false; }
	}
	async function save(label: string, task: () => Promise<void>) {
		clearFeedback(); saving = true;
		try { await task(); notice = `${label} berhasil disimpan.`; await load(); }
		catch (cause) { error = message(cause); }
		finally { saving = false; }
	}
	function createAccount() { return save('Akun', async () => { await request('/v1/admin/accounting/accounts', { method: 'POST', body: JSON.stringify({ code, name, account_type: kind, normal_balance: normal }) }); code = ''; name = ''; }); }
	function createPeriod() { return save('Periode', async () => { await request('/v1/admin/accounting/periods', { method: 'POST', body: JSON.stringify({ starts_on: starts, ends_on: ends }) }); starts = ''; ends = ''; }); }
	function closePeriod(id: string) { return save('Periode', () => request(`/v1/admin/accounting/periods/${id}/close`, { method: 'POST' })); }
	function reconcile() { return save('Rekonsiliasi', () => request('/v1/admin/accounting/reconciliations', { method: 'POST', body: JSON.stringify({ account_id: reconcileAccount, statement_date: new Date().toISOString().slice(0, 10), statement_balance_cents: cents(statementRupiah) }) })); }
	function postJournal() { return save('Jurnal', async () => { if (!debitAccount || !creditAccount || debitAccount === creditAccount || cents(journalRupiah) <= 0) throw new Error('Pilih dua akun berbeda dan isi nominal jurnal.'); await request('/v1/admin/accounting', { method: 'POST', body: JSON.stringify({ entry_date: journalDate, reference: journalReference, description: journalDescription, lines: [{ account_id: debitAccount, debit_cents: cents(journalRupiah), credit_cents: 0 }, { account_id: creditAccount, debit_cents: 0, credit_cents: cents(journalRupiah) }] }) }); journalReference = ''; journalDescription = ''; journalRupiah = null; }); }
	onMount(load);
</script>

<main class="workspace-page accounting-page">
	<PageHeader title="Accounting" description="Kelola chart of accounts, jurnal berimbang, periode tutup buku, dan rekonsiliasi bank." backHref="/admin/dashboard">
		{#snippet actions()}<RefreshButton onclick={load} disabled={loading || saving} label="Muat ulang" />{/snippet}
	</PageHeader>
	{#if error}<AlertBanner title="Perubahan belum tersimpan" message={error} />{/if}
	{#if notice}<AlertBanner variant="success" message={notice} />{/if}

	<div class="accounting-summary" aria-busy={loading}>
		<div><span>Akun aktif</span><strong>{accounts.length}</strong></div><div><span>Jurnal terbaru</span><strong>{journals.length}</strong></div><div><span>Periode terbuka</span><strong>{periods.filter((item) => item.status === 'open').length}</strong></div>
	</div>

	<div class="grid two-up">
		<section class="panel"><h2>Chart of accounts</h2><p class="muted">Buat akun sebelum membuat jurnal atau mapping komersial.</p><form novalidate onsubmit={(event) => { event.preventDefault(); void createAccount(); }}><div class="fields two"><label>Kode akun<input bind:value={code} required maxlength="32" placeholder="Contoh: 1101" /></label><label>Nama akun<input bind:value={name} required maxlength="180" placeholder="Contoh: Kas utama" /></label></div><div class="fields two"><label>Tipe akun<select bind:value={kind}><option value="asset">Aset</option><option value="liability">Liabilitas</option><option value="equity">Ekuitas</option><option value="income">Pendapatan</option><option value="expense">Beban</option></select></label><label>Saldo normal<select bind:value={normal}><option value="debit">Debit</option><option value="credit">Kredit</option></select></label></div><button disabled={saving}>Simpan akun</button></form><div class="list">{#each accounts as item (item.id)}<article><strong>{item.code} · {item.name}</strong><small>{item.type} · saldo normal {item.normal_balance}</small></article>{:else}<p class="empty">Belum ada akun.</p>{/each}</div></section>
		<section class="panel"><h2>Jurnal manual</h2><p class="muted">Setiap jurnal wajib memiliki total debit dan kredit yang sama.</p><form novalidate onsubmit={(event) => { event.preventDefault(); void postJournal(); }}><div class="fields two"><label>Tanggal<input bind:value={journalDate} type="date" required /></label><label>Referensi<input bind:value={journalReference} maxlength="120" placeholder="INV-2026-001" /></label></div><label>Deskripsi<input bind:value={journalDescription} maxlength="1000" required placeholder="Keterangan transaksi" /></label><div class="fields two"><label>Akun debit<select bind:value={debitAccount}>{#each accounts as item}<option value={item.id}>{item.code} — {item.name}</option>{/each}</select></label><label>Akun kredit<select bind:value={creditAccount}>{#each accounts as item}<option value={item.id}>{item.code} — {item.name}</option>{/each}</select></label></div><label>Nominal (Rp)<input bind:value={journalRupiah} type="number" min="1" step="1" required placeholder="0" /></label><button disabled={saving || accounts.length < 2}>Posting jurnal</button></form></section>
	</div>

	<div class="grid two-up">
		<section class="panel"><h2>Periode buku</h2><form novalidate onsubmit={(event) => { event.preventDefault(); void createPeriod(); }}><div class="fields two"><label>Mulai<input bind:value={starts} type="date" required /></label><label>Selesai<input bind:value={ends} type="date" required /></label></div><button disabled={saving}>Buka periode</button></form><div class="list">{#each periods as item (item.id)}<article class="row"><span><strong>{item.starts_on} — {item.ends_on}</strong><small>{item.status === 'open' ? 'Terbuka untuk posting' : 'Terkunci'}</small></span>{#if item.status === 'open'}<button class="quiet compact" disabled={saving} onclick={() => void closePeriod(item.id)}>Tutup periode</button>{/if}</article>{:else}<p class="empty">Belum ada periode.</p>{/each}</div></section>
		<section class="panel"><h2>Rekonsiliasi bank</h2><p class="muted">Bandingkan saldo mutasi rekening dengan saldo ledger per hari ini.</p><form novalidate onsubmit={(event) => { event.preventDefault(); void reconcile(); }}><label>Akun bank<select bind:value={reconcileAccount}>{#each accounts as item}<option value={item.id}>{item.code} — {item.name}</option>{/each}</select></label><label>Saldo rekening (Rp)<input bind:value={statementRupiah} type="number" step="1" placeholder="0" /></label><button disabled={saving || !reconcileAccount}>Simpan rekonsiliasi</button></form><div class="list">{#each reconciliations as item (item.id)}<article><strong>{item.account_code} · {item.statement_date}</strong><small class:ok={item.difference_cents === 0}>Bank {money(item.statement_balance_cents)} · Ledger {money(item.ledger_balance_cents)} · Selisih {money(item.difference_cents)}</small></article>{:else}<p class="empty">Belum ada rekonsiliasi.</p>{/each}</div></section>
	</div>

	<section class="panel"><h2>Jurnal terbaru</h2><div class="journal-list">{#each journals as item (item.id)}<article><div><strong>{item.number}</strong><small>{item.entry_date} · {item.reference || 'Tanpa referensi'}</small></div><p>{item.description || 'Tanpa deskripsi'}</p><span class:posted={item.status === 'posted'}>{item.status}</span></article>{:else}<p class="empty">Belum ada jurnal yang diposting.</p>{/each}</div></section>

	<section class="panel"><h2>Ringkasan laporan keuangan</h2><p class="muted">Saldo akun dari seluruh jurnal yang sudah diposting. Nilai positif mengikuti saldo normal akun.</p><div class="accounting-summary financial-totals"><div><span>Aset</span><strong>{money(financial.totals.asset ?? 0)}</strong></div><div><span>Liabilitas</span><strong>{money(financial.totals.liability ?? 0)}</strong></div><div><span>Ekuitas</span><strong>{money(financial.totals.equity ?? 0)}</strong></div><div><span>Pendapatan</span><strong>{money(financial.totals.income ?? 0)}</strong></div><div><span>Beban</span><strong>{money(financial.totals.expense ?? 0)}</strong></div></div><div class="journal-list">{#each financial.balances as item (item.code)}<article class="row"><span><strong>{item.code} · {item.name}</strong><small>{item.type} · saldo normal {item.normal_balance}</small></span><strong>{money(item.balance_cents)}</strong></article>{:else}<p class="empty">Belum ada saldo akun untuk dilaporkan.</p>{/each}</div></section>
</main>

<style>
	.accounting-page{display:grid;gap:12px}.accounting-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.accounting-summary>div{display:grid;gap:2px;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--surface-alt)}.accounting-summary span,.muted,small{color:var(--muted);font-size:11px}.accounting-summary strong{font-size:18px}.financial-totals{grid-template-columns:repeat(5,minmax(0,1fr));margin:12px 0}.grid{display:grid;gap:12px}.two-up{grid-template-columns:repeat(2,minmax(0,1fr))}.panel{padding:14px;border-radius:var(--radius)}h2{margin:0 0 4px;font-size:14px}.muted{margin:0 0 10px;line-height:1.5}form{display:grid;gap:9px}.fields{display:grid;gap:8px}.fields.two{grid-template-columns:repeat(2,minmax(0,1fr))}label{display:grid;gap:4px;font-size:11px;font-weight:700}.list,.journal-list{display:grid;gap:0;margin-top:12px;border-top:1px solid var(--line)}article{padding:9px 0;border-bottom:1px solid var(--line)}.row,.journal-list article{display:flex;align-items:center;justify-content:space-between;gap:10px}.journal-list article p{margin:0;flex:1;color:var(--muted);font-size:12px}.journal-list span{padding:2px 6px;border-radius:var(--radius-pill);background:var(--surface-alt);font-size:10px;font-weight:700}.journal-list .posted,.ok{color:var(--primary-strong)}.empty{margin:12px 0 0;color:var(--muted);font-size:12px}.compact{min-height:30px!important;padding:4px 8px!important;font-size:11px!important}@media(max-width:760px){.two-up,.fields.two{grid-template-columns:1fr}.accounting-summary,.financial-totals{grid-template-columns:1fr 1fr}.journal-list article{align-items:flex-start;flex-direction:column}.journal-list article p{width:100%}}
</style>
