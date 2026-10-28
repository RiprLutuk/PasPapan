<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import RequestPayments from './RequestPayments.svelte';
	import { localeTag,uiText as t } from './i18n';
	let rows=$state<{id:string;user_id:string;full_name:string;kind:string;reason:string;amount_cents:number}[]>([]),error=$state('');
	async function load(){try{rows=await request('/v1/admin/request-payments');}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}}
	onMount(load);
</script>
<details class="panel finance"><summary>{t('Pencairan dan cicilan')}</summary>
	{#if error}<p role="alert">{error}</p>{/if}
	{#if !rows.length}<p>{t('Belum ada pengajuan keuangan yang disetujui.')}</p>{/if}
	{#each rows as row(row.id)}<article><strong>{row.full_name}</strong><p>{t(row.kind==='cash_advance'?'Kasbon':'Klaim')} · {new Intl.NumberFormat(localeTag(),{style:'currency',currency:'IDR'}).format(row.amount_cents/100)}</p><p>{row.reason}</p><RequestPayments id={row.id} kind={row.kind} owner={row.user_id}/></article>{/each}
</details>
<style>.finance{padding:16px;border-radius:var(--radius)}summary{font-size:13px;font-weight:700;cursor:pointer}article{padding-block:12px;border-bottom:1px solid var(--line)}p{font-size:12px;color:var(--muted)}</style>
