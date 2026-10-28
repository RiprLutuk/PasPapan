<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { uiText as t } from './i18n';
	export type Cycle={id:string;name:string;starts_on:string;ends_on:string;status:string;version:number};
	let {admin=false,onupdated}:{admin?:boolean;onupdated:(cycles:Cycle[])=>void}=$props();
	let cycles=$state<Cycle[]>([]),name=$state(''),starts=$state(''),ends=$state(''),error=$state(''),busy=$state(false);
	async function load(){try{cycles=await request<Cycle[]>('/v1/performance-cycles');onupdated(cycles);}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}}
	async function save(body:unknown){busy=true;error='';try{await request('/v1/admin/performance-cycles',{method:'POST',body:JSON.stringify(body)});await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	onMount(load);
</script>
<details class="panel cycles"><summary>{t('Siklus penilaian')}</summary>{#if error}<p role="alert">{error}</p>{/if}
	{#each cycles as cycle(cycle.id)}<div class="cycle"><span><strong>{cycle.name}</strong> · {cycle.starts_on} — {cycle.ends_on} · {t(cycle.status==='closed'?'Ditutup':'Aktif')}</span>{#if admin&&cycle.status==='open'}<button class="quiet" disabled={busy} onclick={()=>save({...cycle,status:'closed'})}>{t('Tutup siklus')}</button>{/if}</div>{/each}
	{#if admin}<form novalidate class="editor-form editor-form--embedded" onsubmit={event=>{event.preventDefault();void save({name,starts_on:starts,ends_on:ends,status:'open'});}}><div class="form-fields"><label class="form-wide">{t('Nama siklus')}<input bind:value={name} maxlength="100" required/></label><label>{t('Mulai')}<input type="date" bind:value={starts} required/></label><label>{t('Selesai')}<input type="date" bind:value={ends} min={starts} required/></label></div><footer class="form-footer"><div class="form-actions"><button disabled={busy}>{t('Buat siklus')}</button></div></footer></form>{/if}
</details>
<style>.cycles{padding:16px;border-radius:var(--radius)}summary{font-size:13px;font-weight:700;cursor:pointer}.cycle{display:flex;gap:8px;align-items:center;justify-content:space-between;font-size:12px;padding-block:10px}form{display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-top:12px}label{flex:1;min-width:130px}</style>
