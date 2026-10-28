<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { request } from './api';
	import { PageHeader, RefreshButton } from './ui';
	import Icon from './Icon.svelte';
	import WorkCalendar from './WorkCalendar.svelte';
	import { uiText as t, localeTag } from './i18n';
	type Reference = { id:string;kind:string;name:string;active:boolean;holiday_date:string|null;annual_days:number|null;version:number };
	const categories = { division:'Divisi',job_title:'Jabatan',education:'Pendidikan',leave_type:'Jenis cuti',holiday:'Hari libur' };
	let kind = $state(page.url.searchParams.get('kind') ?? 'division');
	let entries = $state<Reference[]>([]), search = $state(''), loading = $state(true), busy = $state(false), error = $state('');
	let editing = $state(false), id = $state<string|null>(null), version = $state<number|null>(null), name = $state(''), active = $state(true), holidayDate = $state(''), annualDays = $state(12);
	let filtered = $derived(entries.filter(entry => entry.kind === kind && entry.name.toLocaleLowerCase(localeTag()).includes(search.toLocaleLowerCase(localeTag()))));
	async function load() {
		loading=true;error='';
		try { entries=await request<Reference[]>('/v1/admin/organization/reference-data'); }
		catch(cause) { error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.'); }
		finally { loading=false; }
	}
	function edit(entry?:Reference) { id=entry?.id??null;version=entry?.version??null;name=entry?.name??'';active=entry?.active??true;holidayDate=entry?.holiday_date??'';annualDays=entry?.annual_days??12;editing=true; }
	async function save() {
		busy=true;error='';
		try { await request('/v1/admin/organization/reference-data',{method:'POST',body:JSON.stringify({id,version,kind,name,active,holiday_date:kind==='holiday'?holidayDate:null,annual_days:kind==='leave_type'?annualDays:null})});editing=false;await load(); }
		catch(cause) { error=cause instanceof Error?cause.message:t('Gagal menyimpan data.'); }
		finally { busy=false; }
	}
	onMount(load);
</script>
<svelte:head><title>{t('Master data organisasi')} · PasPapan</title></svelte:head>
<main class="workspace-page organization-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Master data organisasi')} description={t('Struktur tim, kebijakan cuti, dan kalender perusahaan.')} backHref="/admin/dashboard">{#snippet actions()}<button onclick={()=>edit()}><Icon name="plus" size={15}/>{t('Tambah data')}</button>{/snippet}</PageHeader>
	{#if error}<p role="alert">{error}<RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}
	<nav class="categories" aria-label={t('Kategori master data')}>{#each Object.entries(categories) as [value,label]}<button class="quiet" class:active={kind===value} aria-pressed={kind===value} onclick={()=>{kind=value;editing=false;search='';}}>{t(label)}</button>{/each}</nav>
	{#if kind==='leave_type'||kind==='holiday'}<WorkCalendar/>{/if}
	<div class="reference-layout" class:editing>
		{#if editing}<form class="panel" onsubmit={event=>{event.preventDefault();void save();}}><h2>{t(id?'Edit data':'Tambah data')}</h2><label>{t('Nama')}<input bind:value={name} maxlength="100" required/></label>{#if kind==='holiday'}<label>{t('Tanggal')}<input type="date" bind:value={holidayDate} required/></label>{/if}{#if kind==='leave_type'}<label>{t('Jatah tahunan (hari)')}<input type="number" bind:value={annualDays} min="0" max="366" step="1" required/></label>{/if}<label>{t('Status')}<select bind:value={active}><option value={true}>{t('Aktif')}</option><option value={false}>{t('Nonaktif')}</option></select></label><div class="actions"><button class="quiet" type="button" onclick={()=>editing=false} disabled={busy}>{t('Batal')}</button><button disabled={busy}>{t('Simpan')}</button></div></form>{/if}
		<section class="panel" aria-busy={loading}><input type="search" bind:value={search} placeholder={t('Ketik untuk mencari…')} aria-label={t('Cari data')}/>{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !filtered.length}<div class="empty"><h2>{t('Belum ada data')}</h2></div>{/if}{#each filtered as entry(entry.id)}<div class="reference-row"><div><strong>{entry.name}</strong>{#if entry.holiday_date}<small>{entry.holiday_date}</small>{/if}{#if entry.annual_days!==null}<small>{entry.annual_days} {t('hari / tahun')}</small>{/if}</div><span class="badge">{t(entry.active?'Aktif':'Nonaktif')}</span><button class="quiet" onclick={()=>edit(entry)} aria-label={`${t('Edit')} ${entry.name}`}><Icon name="edit" size={15}/></button></div>{/each}</section>
	</div>
</main>
<style>
	.organization-page{display:grid;gap:12px;align-content:start}.categories{display:flex;gap:6px;overflow-x:auto;padding-bottom:4px}.categories button{white-space:nowrap;border-radius:var(--radius-sm)}.categories button.active{background:var(--primary-soft);color:var(--primary-strong)}.reference-layout{display:grid;gap:12px;align-items:start}.panel{padding:16px;border-radius:var(--radius);min-width:0}h2{font-size:15px;margin-bottom:12px}.reference-row{display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid var(--line)}.reference-row:last-child{border-bottom:0}.reference-row>div{flex:1}.reference-row strong{font-size:13px}.reference-row small{display:block;color:var(--muted);font-size:11px;margin-top:4px}.actions{display:flex;justify-content:flex-end;gap:8px}@media(min-width:1024px){.reference-layout.editing{grid-template-columns:320px minmax(0,1fr)}}
</style>
