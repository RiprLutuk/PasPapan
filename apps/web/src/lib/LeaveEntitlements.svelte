<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { PageHeader, RefreshButton } from './ui';
	import { uiText as t } from './i18n';
	type Entitlement={user_id:string;leave_type_id:string;full_name:string;leave_type_name:string;year:number;days:number;carryover_days:number;version:number};
	let entries=$state<Entitlement[]>([]),members=$state<{id:string;full_name:string}[]>([]),types=$state<{id:string;kind:string;name:string;active:boolean}[]>([]);
	let userId=$state(''),typeId=$state(''),year=$state(new Date().getFullYear()),days=$state(12),carryover=$state(0),busy=$state(false),loading=$state(true),error=$state(''),notice=$state('');
	let current=$derived(entries.find(entry=>entry.user_id===userId&&entry.leave_type_id===typeId&&entry.year===year));
	async function load(){loading=true;error='';try{[entries,members,types]=await Promise.all([request<Entitlement[]>('/v1/admin/organization/leave-entitlements'),request<{id:string;full_name:string}[]>('/v1/admin/operational-members'),request<{id:string;kind:string;name:string;active:boolean}[]>('/v1/admin/organization/reference-data')]);}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}finally{loading=false;}}
	async function save(){busy=true;error='';notice='';try{await request('/v1/admin/organization/leave-entitlements',{method:'POST',body:JSON.stringify({user_id:userId,leave_type_id:typeId,year,days,carryover_days:carryover,version:current?.version??null})});await load();notice=t('Jatah cuti tersimpan.');}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	onMount(load);
</script>
<svelte:head><title>{t('Jatah cuti')} · PasPapan</title></svelte:head>
<main class="workspace-page entitlements-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Jatah cuti')} description={t('Atur pengecualian kuota tahunan per karyawan.')} backHref="/admin/organization"/>
	{#if error}<p role="alert">{error}<RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	<div class="quota-layout"><form class="panel" onsubmit={event=>{event.preventDefault();void save();}}><h2>{t('Atur jatah cuti')}</h2><label>{t('Karyawan')}<select bind:value={userId} required><option value="">{t('Pilih karyawan')}</option>{#each members as member}<option value={member.id}>{member.full_name}</option>{/each}</select></label><label>{t('Jenis cuti')}<select bind:value={typeId} required><option value="">{t('Pilih jenis cuti')}</option>{#each types.filter(item=>item.kind==='leave_type'&&item.active) as item}<option value={item.id}>{item.name}</option>{/each}</select></label><label>{t('Tahun')}<input type="number" min="2000" max="2200" step="1" bind:value={year} required/></label><label>{t('Jumlah hari')}<input type="number" min="0" max="366" step="1" bind:value={days} required/></label><label>{t('Sisa tahun lalu yang disetujui (hari)')}<input type="number" min="0" max={366-days} step="1" bind:value={carryover} required/></label><button disabled={busy||loading}>{t('Simpan')}</button><p class="muted">{t('Tanpa pengecualian, kuota mengikuti jatah tahunan pada jenis cuti.')}</p></form>
	<section class="panel" aria-busy={loading}><h2>{t('Kuota khusus karyawan')}</h2>{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !entries.length}<p class="muted">{t('Belum ada pengecualian kuota.')}</p>{/if}{#each entries as entry}<button class="quota-row quiet" onclick={()=>{userId=entry.user_id;typeId=entry.leave_type_id;year=entry.year;days=entry.days;carryover=entry.carryover_days;}}><span><strong>{entry.full_name}</strong><small>{entry.leave_type_name} · {entry.year}</small></span><span>{entry.days+entry.carryover_days} {t('hari')}</span></button>{/each}</section></div>
</main>
<style>
	.entitlements-page{display:grid;gap:12px;align-content:start}.quota-layout{display:grid;grid-template-columns:320px minmax(0,1fr);gap:12px;align-items:start}.panel{padding:16px;border-radius:var(--radius)}h2{font-size:15px;margin-bottom:12px}.muted{color:var(--muted);font-size:12px}.quota-row{display:flex;justify-content:space-between;align-items:center;text-align:left;width:100%;padding:12px;border-radius:var(--radius-sm);gap:12px}.quota-row strong{display:block}.quota-row small{display:block;color:var(--muted);margin-top:4px}@media(max-width:800px){.quota-layout{grid-template-columns:1fr}}
</style>
