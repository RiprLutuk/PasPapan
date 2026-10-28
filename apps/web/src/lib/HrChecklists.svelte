<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { request } from './api';
	import { PageHeader } from './ui';
	import Icon from './Icon.svelte';
	import { uiText as t } from './i18n';
	let { admin=false }:{admin?:boolean}=$props();
	type Checklist={id:string;full_name:string;title:string;kind:string;completed_at:string|null;items:{id:string;label:string;completed:boolean}[]};
	let entries=$state<Checklist[]>([]),members=$state<{id:string;full_name:string}[]>([]),loading=$state(true),busy=$state(false),error=$state(''),createOpen=$state(false);
	let userId=$state(''),title=$state(''),kind=$state(page.url.searchParams.get('kind')??'onboarding'),steps=$state('');
	async function load(){loading=true;error='';try{entries=await request<Checklist[]>(admin?'/v1/admin/hr-checklists':'/v1/hr-checklists');if(admin)members=await request<{id:string;full_name:string}[]>('/v1/admin/operational-members');}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}finally{loading=false;}}
	async function mutate(path:string,body:unknown){busy=true;error='';try{await request(path,{method:'POST',body:JSON.stringify(body)});createOpen=false;await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	let templates=$state<{id:string;title:string;kind:string;items:string[];active:boolean;version:number}[]>([]),templateId=$state('');
	async function loadTemplates(){templates=await request<typeof templates>('/v1/admin/hr-checklist-templates');}
	function selectTemplate(){const template=templates.find(item=>item.id===templateId);if(template){title=template.title;kind=template.kind;steps=template.items.join('\n');}}
	async function saveTemplate(){await mutate('/v1/admin/hr-checklist-templates',{title,kind,items:steps.split('\n').map(step=>step.trim()).filter(Boolean),active:true});await loadTemplates();}
	onMount(()=>{void load();if(admin)void loadTemplates().catch(cause=>error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.'));});
</script>
<svelte:head><title>{t('Checklist HR')} · PasPapan</title></svelte:head>
<main class="workspace-page checklists-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Checklist HR')} description={t('Langkah onboarding dan serah terima offboarding.')} backHref={admin?'/admin/dashboard':'/app/today'}>{#snippet actions()}{#if admin}<button onclick={()=>createOpen=!createOpen}><Icon name="plus" size={15}/>{t('Buat checklist')}</button>{/if}{/snippet}</PageHeader>
	{#if error}<p role="alert">{error}<button class="quiet" onclick={load}>{t('Coba lagi')}</button></p>{/if}
	{#if admin&&createOpen}<form class="panel" onsubmit={event=>{event.preventDefault();void mutate('/v1/admin/hr-checklists',{user_id:userId,template_id:templateId||null,title,kind,items:steps.split('\n').map(step=>step.trim()).filter(Boolean)});}}><label>{t('Template checklist')}<select bind:value={templateId} onchange={selectTemplate}><option value="">{t('Tanpa template')}</option>{#each templates.filter(item=>item.active) as template}<option value={template.id}>{template.title}</option>{/each}</select></label><div class="fields"><label>{t('Karyawan')}<select bind:value={userId} required><option value="">{t('Pilih karyawan')}</option>{#each members as member}<option value={member.id}>{member.full_name}</option>{/each}</select></label><label>{t('Jenis')}<select bind:value={kind}><option value="onboarding">Onboarding</option><option value="offboarding">Offboarding</option></select></label><label>{t('Judul')}<input bind:value={title} maxlength="200" required/></label></div><label>{t('Langkah checklist (satu per baris)')}<textarea bind:value={steps} rows="5" required></textarea></label><small>{t('Maksimal 30 langkah; offboarding memerlukan pengembalian seluruh aset.')}</small><button disabled={busy}>{t('Simpan')}</button><button type="button" class="quiet" disabled={busy||!title.trim()||!steps.trim()} onclick={saveTemplate}>{t('Simpan sebagai template')}</button></form>{/if}
	<section class="checklist-grid" aria-busy={loading} aria-label={t('Daftar checklist HR')}>{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !entries.length&&!error}<div class="panel empty"><Icon name="tasks" size={24}/><h2>{t('Belum ada checklist')}</h2></div>{/if}
		{#each entries as entry(entry.id)}<article class="panel checklist"><div class="heading"><h2>{entry.title}</h2><span class="badge">{t(entry.completed_at?'Selesai':'Berjalan')}</span></div><p class="muted">{entry.full_name} · {entry.kind}</p><p class="muted">{entry.items.filter(item=>item.completed).length}/{entry.items.length} {t('langkah selesai')}</p>{#each entry.items as item(item.id)}<label class="check-item"><input type="checkbox" checked={item.completed} disabled={busy||!!entry.completed_at} onchange={event=>mutate(`/v1/hr-checklists/${entry.id}/items/${item.id}`,{completed:event.currentTarget.checked})}/><span>{item.label}</span></label>{/each}{#if admin&&!entry.completed_at}<button disabled={busy||entry.items.some(item=>!item.completed)} onclick={()=>mutate(`/v1/admin/hr-checklists/${entry.id}/complete`,{})}>{t('Selesaikan proses')}</button>{/if}{#if entry.completed_at}<p class="muted">{t('Proses selesai dan terkunci.')}</p>{/if}</article>{/each}
	</section>
</main>
<style>
	.checklists-page{display:grid;gap:12px;align-content:start}.panel{padding:16px;border-radius:var(--radius);min-width:0}.checklist-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:12px;align-items:start}.heading{display:flex;justify-content:space-between;align-items:start;gap:12px}h2{font-size:15px;margin:0 0 8px}.muted,small{font-size:11px;color:var(--muted)}.fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}.check-item{display:flex;gap:10px;align-items:center;padding:10px 0;margin:0;border-bottom:1px solid var(--line);font-size:13px}.check-item input{width:18px;height:18px;min-height:18px;flex:0 0 18px;margin:0}.check-item span{overflow-wrap:anywhere}.checklist>button{margin-top:12px}
</style>
