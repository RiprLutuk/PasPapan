<script lang="ts">
	import { onMount } from 'svelte';
	import { request, uploadFile } from './api';
	import { PageHeader, RefreshButton } from './ui';
	import Icon from './Icon.svelte';
	import ProviderVerification from './ProviderVerification.svelte';
	import DocumentTemplates,{type Template} from './DocumentTemplates.svelte';
	import PrintableDocument from './PrintableDocument.svelte';
	import { uiText as t } from './i18n';
	import { workSession } from './session';
	let { admin=false }:{admin?:boolean}=$props();
	type Document={id:string;user_id:string;full_name:string;title:string;reason:string;status:string;file_name:string|null;download_url:string|null;decision_note:string;version:number;signature_reference:string|null;rendered_document:{title:string;body:string;generated_on:string;template_version:number}|null};
	const session=workSession();
	let templates=$state<Template[]>([]),templateId=$state('');
	let entries=$state<Document[]>([]),loading=$state(true),busy=$state(false),uploading=$state(false),error=$state(''),title=$state(''),reason=$state(''),formOpen=$state(false);
	let resourceData=$state<Record<string,string>>({});
	let receipts=$state<Record<string,string>>({});
	let notes=$state<Record<string,string>>({}),files=$state<Record<string,string>>({});
	function statusLabel(value:string){return t(value==='issued'?'Diterbitkan':value==='rejected'?'Ditolak':'Menunggu');}
	async function load(){loading=true;error='';try{entries=await request<Document[]>(admin?'/v1/admin/hr-documents':'/v1/hr-documents');}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}finally{loading=false;}}
	async function mutate(path:string,body:unknown){busy=true;error='';try{await request(path,{method:'POST',body:JSON.stringify(body)});formOpen=false;await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	async function upload(event:Event,id:string){const input=event.currentTarget as HTMLInputElement;const file=input.files?.[0];if(!file)return;uploading=true;error='';try{if(file.type!=='application/pdf')throw new Error(t('Gunakan dokumen PDF.'));const data=await new Promise<string>((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(String(reader.result));reader.onerror=()=>reject(new Error(t('Gagal membaca berkas.')));reader.readAsDataURL(file);});const saved=await uploadFile({file_name:file.name,content_type:'application/pdf',data_base64:data});files[id]=saved.id;resourceData[id]=data;receipts[id]='';}catch(cause){error=cause instanceof Error?cause.message:t('Gagal mengunggah berkas.');}finally{uploading=false;input.value='';}}
	onMount(load);
</script>
<svelte:head><title>{t('Surat HR')} · PasPapan</title></svelte:head>
<main class="workspace-page documents-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Surat HR')} description={t('Permintaan dan penerbitan dokumen resmi karyawan.')} backHref={admin?'/admin/dashboard':'/app/today'}>{#snippet actions()}{#if !admin}<button onclick={()=>formOpen=!formOpen}><Icon name="plus" size={15}/>{t('Ajukan dokumen')}</button>{/if}{/snippet}</PageHeader>
	{#if error}<p role="alert">{error}<RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}
	<DocumentTemplates {admin} onupdated={value=>templates=value}/>
	{#if !admin&&formOpen}<form novalidate class="panel editor-form" onsubmit={event=>{event.preventDefault();void mutate('/v1/hr-documents',{title,reason,template_id:templateId||null});}}><label>{t('Template dokumen')}<select bind:value={templateId} onchange={()=>{if(templateId)title=templates.find(entry=>entry.id===templateId)?.title??title;}}><option value="">{t('Tanpa template')}</option>{#each templates.filter(entry=>entry.active) as template}<option value={template.id}>{template.title}</option>{/each}</select></label><label>{t('Dokumen yang dibutuhkan')}<input bind:value={title} maxlength="200" required/></label><label>{t('Keperluan')}<textarea bind:value={reason} rows="3" maxlength="4000" required></textarea></label><button disabled={busy}>{t('Kirim pengajuan')}</button></form>{/if}
	<section class="documents-grid" aria-busy={loading} aria-label={t('Daftar dokumen HR')}>{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !entries.length&&!error}<div class="panel empty"><Icon name="file" size={24}/><h2>{t('Belum ada permintaan dokumen')}</h2></div>{/if}
		{#each entries as entry(entry.id)}<article class="panel document"><div class="heading"><h2>{entry.title}</h2><span class="badge">{statusLabel(entry.status)}</span></div><p class="muted">{entry.full_name}</p><p class="reason">{entry.reason}</p>{#if entry.rendered_document}<PrintableDocument document={entry.rendered_document}/>{/if}{#if entry.signature_reference}<p class="muted">{t('Tanda tangan diverifikasi provider')}: {entry.signature_reference}</p>{/if}{#if entry.decision_note}<p class="note">{t('Catatan')}: {entry.decision_note}</p>{/if}{#if entry.download_url}<a class="download" href={entry.download_url} target="_blank" rel="noreferrer"><Icon name="file" size={16}/>{entry.file_name??t('Unduh dokumen')}</a>{/if}
			{#if admin&&entry.status==='pending'&&entry.user_id!==session.current?.user.id}<label>{t('Dokumen resmi (PDF)')}<input type="file" accept="application/pdf" onchange={event=>upload(event,entry.id)} disabled={busy||uploading}/></label>{#if files[entry.id]}<small>{t('Berkas siap diterbitkan')}</small><ProviderVerification purpose="document_signature" resourceId={files[entry.id]} resourceBase64={resourceData[entry.id]} bind:receipt={receipts[entry.id]}/>{/if}<label>{t('Catatan')}<textarea bind:value={notes[entry.id]} rows="2" maxlength="4000"></textarea></label><div class="actions"><button disabled={busy||uploading||!files[entry.id]} onclick={()=>mutate(`/v1/admin/hr-documents/${entry.id}/decision`,{status:'issued',file_id:files[entry.id],signature_receipt:receipts[entry.id]||null,note:notes[entry.id]??'',version:entry.version})}>{t('Terbitkan dokumen')}</button><button class="quiet" disabled={busy||uploading||!notes[entry.id]?.trim()} onclick={()=>mutate(`/v1/admin/hr-documents/${entry.id}/decision`,{status:'rejected',file_id:null,note:notes[entry.id],version:entry.version})}>{t('Tolak')}</button></div>{/if}
		</article>{/each}
	</section>
</main>
<style>
	.documents-page{display:grid;gap:12px;align-content:start}.documents-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:12px;align-items:stretch}.panel{padding:16px;border-radius:var(--radius);min-width:0}.heading{display:flex;justify-content:space-between;align-items:start;gap:12px}h2{font-size:15px;margin:0 0 8px}.muted{color:var(--muted);font-size:11px}.reason,.note{font-size:12px;white-space:pre-wrap;overflow-wrap:anywhere}.download{display:inline-flex;gap:6px;align-items:center;margin-block:10px;font-size:12px;font-weight:700}.actions{display:flex;flex-wrap:wrap;gap:8px}
.document{display:flex;flex-direction:column}.document>.actions,.document>.download:last-child{margin-top:auto;padding-top:12px}
</style>
