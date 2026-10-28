<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { PageHeader, RefreshButton } from './ui';
	import { workSession } from './session';
	import Icon from './Icon.svelte';
	import PayrollPolicy from './PayrollPolicy.svelte';
	import FinancePayments from './FinancePayments.svelte';
	import PayrollBank from './PayrollBank.svelte';
	import PayrollExtras,{type Extras} from './PayrollExtras.svelte';
	import { localeTag, uiText as t } from './i18n';
	let { admin = false }: { admin?: boolean } = $props();
	type Period = { id: string; period_start: string; period_end: string; status: string; version: number };
	type Record = { id: string; period_id: string; user_id: string; full_name: string; gross_amount: number; deduction_amount: number; net_amount: number; period_status: string; calculation?:{input?:Extras&{base_salary:number;overtime_minutes:number;overtime_hourly_amount:number};components:{label:string;kind:string;amount:number}[]}|null };
	type Member = { id: string; full_name: string };
	const session = workSession();
	let canManage = $derived(admin && (session.current?.capabilities.includes('admin.payroll.manage') ?? false));
	let periods = $state<Period[]>([]), records = $state<Record[]>([]), members = $state<Member[]>([]);
	let periodId = $state(''), userId = $state(''), gross = $state(0), deduction = $state(0);
	let extras=$state<Extras>({work_days:null,paid_days:null,include_thr:false,months_worked:null,tax_category:null,adjustments:[]});
	let taxCategories=$state<string[]>([]);
	let policyVersion=$state<number|null>(null),automatic=$state(false),overtimeMinutes=$state(0),overtimeHourly=$state(0);
	let starts = $state(''), ends = $state(''), createOpen = $state(false);
	let loading = $state(true), busy = $state(false), error = $state(''), notice = $state('');
	let selected = $derived(periods.find(period => period.id === periodId));
	let visible = $derived(records.filter(record => record.period_id === periodId));
	let total = $derived(visible.reduce((sum, record) => sum + record.net_amount, 0));
	const money = (value: number) => new Intl.NumberFormat(localeTag(), { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
	const statusLabel = (value: string) => t(value === 'published' ? 'Dipublikasikan' : value === 'closed' ? 'Ditutup' : 'Draft');
	async function load() {
		loading = true; error = '';
		try {
			const data = await request<{ periods: Period[]; records: Record[] }>(admin ? '/v1/payrolls' : '/v1/payrolls?mine=true');
			periods = data.periods; records = data.records;
			if (!periods.some(period => period.id === periodId)) periodId = periods[0]?.id ?? '';
			if (canManage) members = await request<Member[]>('/v1/admin/operational-members');
		} catch (cause) { error = cause instanceof Error ? cause.message : t('Data tidak dapat dimuat.'); }
		finally { loading = false; }
	}
	async function save(body: unknown) {
		busy = true; error = ''; notice = '';
		try {
			const result = await request<{ period_id: string }>('/v1/admin/payrolls', { method: 'POST', body: JSON.stringify(body) });
			periodId = result.period_id; createOpen = false; notice = t('Payroll tersimpan.'); await load();
		} catch (cause) { error = cause instanceof Error ? cause.message : t('Gagal menyimpan data.'); }
		finally { busy = false; }
	}
	function periodBody(status: string) { return { period_start: selected?.period_start, period_end: selected?.period_end, version: selected?.version, status }; }
	async function exportBank(){if(!selected||busy)return;busy=true;error='';try{const response=await fetch(`/v1/admin/payrolls/${selected.id}/bank-export`,{method:'POST',credentials:'include'});if(!response.ok){const data=await response.json();throw new Error(data.error??t('Ekspor gagal.'));}const url=URL.createObjectURL(await response.blob());const link=document.createElement('a');link.href=url;link.download=`payroll-${selected.period_start}.csv`;link.click();URL.revokeObjectURL(url);}catch(cause){error=cause instanceof Error?cause.message:t('Ekspor gagal.');}finally{busy=false;}}
	function edit(record: Record) { userId=record.user_id;deduction=record.deduction_amount;const input=record.calculation?.input;automatic=Boolean(input);gross=input?.base_salary??record.gross_amount;overtimeMinutes=input?.overtime_minutes??0;overtimeHourly=input?.overtime_hourly_amount??0;extras={work_days:input?.work_days??null,paid_days:input?.paid_days??null,include_thr:input?.include_thr??false,months_worked:input?.months_worked??null,tax_category:input?.tax_category??null,adjustments:(input?.adjustments??[]).map(adjustment=>({...adjustment}))}; }
	onMount(load);
</script>
<svelte:head><title>{admin ? t('Payroll') : t('Slip Gaji')} · PasPapan</title></svelte:head>
<main class="workspace-page payroll-page" id="main-content" tabindex="-1">
	<PageHeader title={admin ? t('Payroll & Slip') : t('Slip Gaji')} description={t('Periode penggajian dan rincian penghasilan.')} backHref={admin ? '/admin/dashboard' : '/app/today'}>
		{#snippet actions()}{#if canManage}<button onclick={() => createOpen = !createOpen}><Icon name="plus" size={15} /> {t('Buat periode')}</button>{/if}{/snippet}
	</PageHeader>
	{#if error}<p role="alert" class="error">{error} <RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}
	{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	{#if canManage}<PayrollPolicy editable={session.current?.company.role==='owner'} onupdated={(version,categories)=>{policyVersion=version;taxCategories=categories;}}/><PayrollBank {members} editable={session.current?.company.role==='owner'}/><FinancePayments/>{/if}
	{#if createOpen && canManage}<form novalidate class="panel new-period editor-form" onsubmit={event => { event.preventDefault(); void save({ period_start: starts, period_end: ends, status: 'draft' }); }}>
		<label>{t('Mulai')}<input type="date" bind:value={starts} required /></label><label>{t('Selesai')}<input type="date" bind:value={ends} min={starts} required /></label><button disabled={busy}>{t('Buat draft')}</button>
	</form>{/if}
	{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !periods.length && !error}<div class="panel empty"><Icon name="wallet" size={24} /><h2>{t('Belum ada slip gaji')}</h2><p>{t('Slip tersedia setelah payroll dipublikasikan.')}</p></div>{:else if periods.length}
		<div class="period-bar"><label>{t('Periode')}<select bind:value={periodId}>{#each periods as period}<option value={period.id}>{period.period_start} — {period.period_end}</option>{/each}</select></label>{#if selected}<span class="badge">{statusLabel(selected.status)}</span>{/if}</div>
		<div class="payroll-layout" class:editable={canManage && selected?.status === 'draft'}>
			{#if canManage && selected?.status === 'draft'}<form novalidate class="panel editor editor-form" onsubmit={event => { event.preventDefault(); void save({ ...periodBody('draft'), user_id: userId, gross_amount: gross, deduction_amount: automatic?0:deduction,calculation:automatic?{...extras,policy_version:policyVersion,base_salary:gross,overtime_minutes:overtimeMinutes,overtime_hourly_amount:overtimeHourly}:null }); }}>
				<h2>{t('Rincian gaji karyawan')}</h2><label>{t('Karyawan')}<select bind:value={userId} required><option value="">{t('Pilih karyawan')}</option>{#each members as member}<option value={member.id}>{member.full_name}</option>{/each}</select></label>
				<label><input type="checkbox" bind:checked={automatic} disabled={!policyVersion}/>{t('Gunakan aturan payroll')}</label><label>{t(automatic?'Gaji pokok (Rp)':'Gaji kotor (Rp)')}<input type="number" min="0" max="999999999999" step="1" bind:value={gross} required /></label>{#if automatic}<label>{t('Lembur (menit)')}<input type="number" min="0" max="44640" step="1" bind:value={overtimeMinutes} required/></label><label>{t('Tarif lembur per jam (Rp)')}<input type="number" min="0" max="999999" step="1" bind:value={overtimeHourly} required/></label><PayrollExtras categories={taxCategories} bind:value={extras}/>{:else}<label>{t('Potongan (Rp)')}<input type="number" min="0" max={gross} step="1" bind:value={deduction} required /></label>{/if}
				{#if !automatic}<p class="net"><span>{t('Gaji bersih')}</span><strong>{money(gross - deduction)}</strong></p>{/if}<button disabled={busy || !userId}>{t('Simpan rincian')}</button>
			</form>{/if}
			<section class="panel records" aria-label={t('Rincian penggajian')}>
				<div class="summary"><h2>{t('Rincian penggajian')}</h2><strong>{money(total)}</strong></div>
				{#if !visible.length}<p class="muted">{t('Tambahkan rincian gaji sebelum publikasi.')}</p>{/if}
				{#each visible as record (record.id)}<article class="payslip"><div class="record-heading"><h3>{record.full_name}</h3>{#if canManage && selected?.status === 'draft'}<button class="quiet" onclick={() => edit(record)}>{t('Edit')}</button>{/if}</div><dl><div><dt>{t('Gaji kotor')}</dt><dd>{money(record.gross_amount)}</dd></div><div><dt>{t('Potongan')}</dt><dd>{money(record.deduction_amount)}</dd></div><div class="net"><dt>{t('Gaji bersih')}</dt><dd>{money(record.net_amount)}</dd></div></dl>{#if record.calculation}<details><summary>{t('Rincian kalkulasi')}</summary>{#each record.calculation.components.filter(component=>component.amount!==0) as component}<p class="muted">{t(component.label==='Base salary'?'Gaji pokok':component.label==='Overtime'?'Lembur':component.label)} · {t(component.kind==='employer'?'Biaya perusahaan':component.kind==='deduction'?'Potongan':'Penghasilan')}: {money(component.amount)}</p>{/each}</details>{/if}</article>{/each}
				{#if canManage && selected?.status === 'draft' && visible.length}<button disabled={busy} onclick={() => save(periodBody('published'))}>{t('Publikasikan slip gaji')}</button>{:else if canManage && selected?.status === 'published'}<button disabled={busy} class="quiet" onclick={() => save(periodBody('closed'))}>{t('Tutup periode')}</button>{/if}
				{#if selected?.status !== 'draft'}<p class="muted">{t('Periode dipublikasikan terkunci untuk menjaga riwayat penggajian.')}</p>{#if canManage}<button class="quiet" disabled={busy} onclick={exportBank}>{t('Ekspor bank')}</button>{/if}{/if}
			</section>
		</div>
	{/if}
</main>
<style>
	.payroll-page { display: grid; gap: 12px; align-content: start; }
	.panel { padding: 16px; border-radius: var(--radius); }
	h2 { font-size: 15px; margin: 0 0 12px; } h3 { font-size: 13px; margin: 0; }
	.period-bar, .summary, .record-heading { display: flex; justify-content: space-between; gap: 12px; align-items: center; }
	.period-bar label { flex: 1; max-width: 440px; }
	.new-period { display: flex; align-items: end; gap: 12px; flex-wrap: wrap; }.new-period label { flex: 1; min-width: 140px; }
	.payroll-layout { display: grid; gap: 12px; align-items: start; }
	.records { min-width: 0; }.payslip { padding: 12px 0; border-top: 1px solid var(--line); }
	dl { margin: 10px 0 0; } dl > div, .net { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; padding: 5px 0; } dd { margin: 0; font-variant-numeric: tabular-nums; }dt { color: var(--muted); }.net { font-weight: 750; }
	.muted { color: var(--muted); font-size: 12px; }.error { color: var(--danger, #b64032); }
	@media (min-width: 1024px) { .payroll-layout.editable { grid-template-columns: 320px minmax(0, 1fr); } }
</style>
