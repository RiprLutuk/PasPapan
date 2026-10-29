<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { request } from './api';
	import { PageHeader, RefreshButton, AlertBanner } from './ui';
	import Icon from './Icon.svelte';
	import SelectFrame from './ui/SelectFrame.svelte';
	import { checklistStarters } from './checklist-templates';
	import { validateFormFields } from './ui/field-validation';
	import { uiText as t } from './i18n';
	let { admin=false }:{admin?:boolean}=$props();
	type Checklist={id:string;full_name:string;title:string;kind:string;completed_at:string|null;items:{id:string;label:string;completed:boolean}[]};
	let entries=$state<Checklist[]>([]),members=$state<{id:string;full_name:string}[]>([]),loading=$state(true),busy=$state(false),error=$state(''),createOpen=$state(false);
	let userId=$state(''),title=$state(''),kind=$state(page.url.searchParams.get('kind')??'onboarding'),steps=$state('');
	async function load(){loading=true;error='';try{entries=await request<Checklist[]>(admin?'/v1/admin/hr-checklists':'/v1/hr-checklists');if(admin)members=await request<{id:string;full_name:string}[]>('/v1/admin/operational-members');}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}finally{loading=false;}}
	async function mutate(path:string,body:unknown){busy=true;error='';try{await request(path,{method:'POST',body:JSON.stringify(body)});createOpen=false;await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	let editorForm=$state<HTMLFormElement>();
	let titleInput=$state<HTMLInputElement>(),stepsInput=$state<HTMLTextAreaElement>();
	let notice=$state('');
	let stepItems=$derived(steps.split('\n').map(step=>step.trim()).filter(Boolean));
	let selectedMember=$derived(members.find(member=>member.id===userId)?.full_name??'');
	let templates=$state<{id:string;title:string;kind:string;items:string[];active:boolean;version:number}[]>([]),templateId=$state('');
	async function loadTemplates(){templates=await request<typeof templates>('/v1/admin/hr-checklist-templates');}
	function selectTemplate(){
		const starter=checklistStarters.find(item=>item.id===templateId);
		if(starter){title=t(starter.title);kind=starter.kind;steps=starter.items.map(item=>t(item)).join('\n');return;}
		const template=templates.find(item=>item.id===templateId);
		if(template){title=template.title;kind=template.kind;steps=template.items.join('\n');}
	}
	async function saveTemplate(){
		if(busy||!editorForm||!titleInput||!stepsInput||!validateFormFields(editorForm,[titleInput,stepsInput]))return;
		busy=true;error='';notice='';
		try{await request('/v1/admin/hr-checklist-templates',{method:'POST',body:JSON.stringify({title,kind,items:stepItems,active:true})});await loadTemplates();notice=t('Template berhasil disimpan.');}
		catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}
	}
	onMount(()=>{void load();if(admin)void loadTemplates().catch(cause=>error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.'));});
</script>
<svelte:head><title>{t('Checklist HR')} · PasPapan</title></svelte:head>
<main class="workspace-page checklists-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Checklist HR')} description={t('Langkah onboarding dan serah terima offboarding.')} backHref={admin?'/admin/dashboard':'/app/today'}>{#snippet actions()}{#if admin}<button onclick={()=>createOpen=!createOpen}><Icon name="plus" size={15}/>{t('Buat checklist')}</button>{/if}{/snippet}</PageHeader>
	{#if error}<p role="alert">{error}<RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}
	{#if admin&&createOpen}
	 <form novalidate class="panel editor-form editor-form--wide checklist-editor" bind:this={editorForm} onsubmit={event=>{event.preventDefault();void mutate('/v1/admin/hr-checklists',{user_id:userId,template_id:templates.find(item=>item.id===templateId)?.id??null,title,kind,items:stepItems});}}>
	  <h2>{t('Buat checklist')}</h2><AlertBanner variant="success" message={notice}/>
	  <div class="checklist-editor-grid">
	   <div class="form-fields">
	    <label>{t('Template checklist')}<SelectFrame><select bind:value={templateId} onchange={selectTemplate} disabled={busy}><option value="">{t('Mulai dari kosong')}</option><optgroup label={t('Template bawaan')}>{#each checklistStarters as starter}<option value={starter.id}>{t(starter.title)}</option>{/each}</optgroup>{#if templates.some(item=>item.active)}<optgroup label={t('Template perusahaan')}>{#each templates.filter(item=>item.active) as template}<option value={template.id}>{template.title}</option>{/each}</optgroup>{/if}</select></SelectFrame><small class="template-help">{t('Pilih contoh bawaan atau template yang disimpan perusahaan.')}</small></label>
	    <label>{t('Jenis')}<SelectFrame><select bind:value={kind} onchange={()=>templateId=''} disabled={busy}><option value="onboarding">Onboarding</option><option value="offboarding">Offboarding</option></select></SelectFrame></label>
	    <label>{t('Karyawan')}<SelectFrame><select bind:value={userId} required disabled={busy}><option value="">{t('Pilih karyawan')}</option>{#each members as member}<option value={member.id}>{member.full_name}</option>{/each}</select></SelectFrame></label>
	    <label>{t('Judul')}<input bind:this={titleInput} bind:value={title} maxlength="200" placeholder={t('Contoh: Persiapan karyawan baru')} required disabled={busy}/></label>
	    <label class="form-wide">{t('Langkah checklist (satu per baris)')}<textarea bind:this={stepsInput} bind:value={steps} onchange={()=>templateId=''} oninput={()=>templateId=''} rows="6" data-max-items="30" data-max-item-length="300" placeholder={t('Tulis satu langkah pada setiap baris.')} required disabled={busy}></textarea></label>
	   </div>
	   <aside class="checklist-preview" aria-labelledby="checklist-preview-title"><div class="preview-heading"><Icon name="tasks" size={16}/><h3 id="checklist-preview-title">{t('Pratinjau checklist')}</h3><small>{stepItems.length}/30</small></div><strong>{title||t('Checklist baru')}</strong><p>{selectedMember||t('Pilih karyawan')} · {kind==='offboarding'?'Offboarding':'Onboarding'}</p>{#if stepItems.length}<ol>{#each stepItems as item,index}<li><span>{index+1}</span><span>{item}</span></li>{/each}</ol>{:else}<p class="preview-empty">{t('Tambahkan langkah untuk melihat ringkasan.')}</p>{/if}{#if kind==='offboarding'}<p class="offboarding-note">{t('Offboarding memerlukan pengembalian seluruh aset.')}</p>{/if}</aside>
	  </div>
	  <footer class="form-footer"><div class="form-help"><p>{t('Maksimal 30 langkah, masing-masing 300 karakter.')}</p><p>{t('Buat template: isi judul, jenis, dan langkah, lalu pilih Simpan sebagai template. Karyawan tidak diperlukan.')}</p></div><div class="form-actions"><button type="button" class="quiet" disabled={busy} onclick={()=>createOpen=false}>{t('Batal')}</button><button type="button" class="quiet" disabled={busy} onclick={saveTemplate}>{t('Simpan sebagai template')}</button><button disabled={busy} type="submit"><Icon name="check" size={15}/>{t('Simpan')}</button></div></footer>
	 </form>
	{/if}
	<section class="checklist-grid" aria-busy={loading} aria-label={t('Daftar checklist HR')}>{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !entries.length&&!error}<div class="panel empty"><Icon name="tasks" size={24}/><h2>{t('Belum ada checklist')}</h2></div>{/if}
		{#each entries as entry(entry.id)}<article class="panel checklist"><div class="heading"><h2>{entry.title}</h2><span class="badge">{t(entry.completed_at?'Selesai':'Berjalan')}</span></div><p class="muted">{entry.full_name} · {entry.kind}</p><p class="muted">{entry.items.filter(item=>item.completed).length}/{entry.items.length} {t('langkah selesai')}</p>{#each entry.items as item(item.id)}<label class="check-item"><input type="checkbox" checked={item.completed} disabled={busy||!!entry.completed_at} onchange={event=>mutate(`/v1/hr-checklists/${entry.id}/items/${item.id}`,{completed:event.currentTarget.checked})}/><span>{item.label}</span></label>{/each}{#if admin&&!entry.completed_at}<button disabled={busy||entry.items.some(item=>!item.completed)} onclick={()=>mutate(`/v1/admin/hr-checklists/${entry.id}/complete`,{})}>{t('Selesaikan proses')}</button>{/if}{#if entry.completed_at}<p class="muted">{t('Proses selesai dan terkunci.')}</p>{/if}</article>{/each}
	</section>
</main>
<style>
	.checklists-page{display:grid;gap:12px;align-content:start}.panel{padding:16px;border-radius:var(--radius);min-width:0}.checklist-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:12px;align-items:stretch}.heading{display:flex;justify-content:space-between;align-items:start;gap:12px}h2{font-size:15px;margin:0 0 8px}.muted,small{font-size:11px;color:var(--muted)}.check-item{display:flex;gap:10px;align-items:center;padding:10px 0;margin:0;border-bottom:1px solid var(--line);font-size:13px}.check-item input{width:18px;height:18px;min-height:18px;flex:0 0 18px;margin:0}.check-item span{overflow-wrap:anywhere}.checklist>button{margin-top:12px}
.checklist{display:flex;flex-direction:column}.checklist>button{margin-top:auto;align-self:flex-start}.checklist>p:last-child{margin-top:auto;padding-top:12px}
.template-help{line-height:1.5;font-weight:400}.form-help p{margin:0}.form-help p+p{margin-top:3px}
.checklist-editor-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(260px,.8fr);gap:16px;align-items:stretch}.checklist-preview{padding:14px;background:var(--surface-alt);border-radius:var(--radius);min-width:0;display:flex;flex-direction:column;gap:8px}.preview-heading{display:flex;align-items:center;gap:7px;color:var(--muted)}.preview-heading h3{color:var(--ink);flex:1}.checklist-preview>strong{font-size:13px;overflow-wrap:anywhere}.checklist-preview>p{margin:0;font-size:11px;color:var(--muted);line-height:1.6}.checklist-preview ol{list-style:none;margin:4px 0 0;padding:0;max-height:260px;overflow:auto}.checklist-preview li{display:flex;gap:8px;align-items:flex-start;padding:8px 0;border-bottom:1px solid var(--line);font-size:12px}.checklist-preview li>span:first-child{display:grid;place-items:center;min-width:22px;height:22px;background:var(--surface);border-radius:var(--radius-sm);font-size:10px;color:var(--muted)}.checklist-preview li>span:last-child{overflow-wrap:anywhere;min-width:0}.preview-empty{flex:1;display:grid;place-content:center;min-height:100px;text-align:center}.checklist-preview .offboarding-note{margin-top:auto;padding-top:12px}@media(max-width:900px){.checklist-editor-grid{grid-template-columns:minmax(0,1fr)}}
</style>
