<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { uiText as t } from './i18n';
	export type Template={id:string;title:string;body:string|null;active:boolean;version:number};
	let {admin=false,onupdated}:{admin?:boolean;onupdated:(templates:Template[])=>void}=$props();
	let entries=$state<Template[]>([]),id=$state<string|null>(null),version=$state<number|null>(null),title=$state(''),body=$state(''),active=$state(true),error=$state(''),busy=$state(false);
	const placeholders='{{employee_name}}, {{employee_code}}, {{company_name}}, {{date}}, {{division}}, {{job_title}}';
	async function load(){try{entries=await request<Template[]>('/v1/hr-document-templates');onupdated(entries);}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}}
	function edit(entry:Template){id=entry.id;version=entry.version;title=entry.title;body=entry.body??'';active=entry.active;}
	async function save(){busy=true;error='';try{await request('/v1/admin/hr-document-templates',{method:'POST',body:JSON.stringify({id,version,title,body,active})});id=null;version=null;title='';body='';await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	onMount(load);
</script>
{#if admin}<details class="panel templates"><summary>{t('Template dokumen')}</summary>{#if error}<p role="alert">{error}</p>{/if}{#each entries as entry(entry.id)}<button class="quiet" onclick={()=>edit(entry)}>{entry.title} · v{entry.version}</button>{/each}<form onsubmit={event=>{event.preventDefault();void save();}}><label>{t('Judul')}<input bind:value={title} maxlength="200" required/></label><label>{t('Isi dokumen')}<textarea bind:value={body} rows="6" maxlength="20000" required></textarea></label><small>{placeholders}</small><label><input type="checkbox" bind:checked={active}/>{t('Aktif')}</label><div class="actions"><button disabled={busy}>{t('Simpan template')}</button>{#if id}<button type="button" class="quiet" onclick={()=>{id=null;version=null;title='';body='';}}>{t('Template baru')}</button>{/if}</div></form></details>{/if}
<style>.templates{padding:16px;border-radius:var(--radius)}summary{font-size:13px;font-weight:700;cursor:pointer}form{display:grid;gap:10px;margin-top:12px}.actions{display:flex;gap:8px}small{font-size:11px;color:var(--muted);overflow-wrap:anywhere}input[type=checkbox]{width:auto}</style>
