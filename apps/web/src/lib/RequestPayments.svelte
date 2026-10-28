<script lang="ts">
	import { request } from './api';
	import { workSession } from './session';
	import { localeTag,uiText as t } from './i18n';
	let { id,kind,owner }:{id:string;kind:string;owner?:string}=$props();
	const session=workSession();
	let manages=$derived(session.current?.capabilities.includes('admin.payroll.manage')&&owner!==session.current?.user.id);
	type Payment={id:string;kind:string;amount_cents:number;reference:string;recorded_at:string};
	let rows=$state<Payment[]>([]),error=$state(''),busy=$state(false),amount=$state(0),reference=$state(''),paymentKind=$state('disbursement');
	const money=(value:number)=>new Intl.NumberFormat(localeTag(),{style:'currency',currency:'IDR'}).format(value/100);
	async function load(){try{rows=await request<Payment[]>(`/v1/requests/${id}/payments`);}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}}
	async function save(){busy=true;error='';try{await request(`/v1/requests/${id}/payments`,{method:'POST',body:JSON.stringify({kind:paymentKind,amount_cents:Math.round(amount*100),reference})});reference='';amount=0;await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
</script>
<details class="payments" ontoggle={event=>{if(event.currentTarget.open)void load();}}><summary>{t('Pencairan dan cicilan')}</summary>
	{#if error}<p role="alert">{error}</p>{/if}
	{#each rows as row(row.id)}<p>{t(row.kind==='disbursement'?'Pencairan':'Cicilan')}: {money(row.amount_cents)} · {row.reference}</p>{/each}
	{#if manages}<form onsubmit={event=>{event.preventDefault();void save();}}><label>{t('Jenis')}<select bind:value={paymentKind}><option value="disbursement">{t('Pencairan')}</option>{#if kind==='cash_advance'}<option value="repayment">{t('Cicilan')}</option>{/if}</select></label><label>{t('Nominal (Rp)')}<input type="number" bind:value={amount} min="0.01" max="999999999.99" step="0.01" required/></label><label>{t('Referensi transaksi')}<input bind:value={reference} minlength="3" maxlength="200" required/></label><p>{t('Catat hanya transaksi yang sudah dilakukan. Pencatatan ini tidak mentransfer uang.')}</p><button disabled={busy}>{t('Catat transaksi')}</button></form>{/if}
</details>
<style>.payments{margin-top:12px;font-size:12px}summary{font-weight:700;cursor:pointer}form{display:grid;gap:8px;padding-top:10px}p{color:var(--muted);overflow-wrap:anywhere}</style>
