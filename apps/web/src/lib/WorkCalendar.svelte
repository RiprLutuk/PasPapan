<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { uiText as t } from './i18n';
	let weekdays=$state<number[]>([1,2,3,4,5,6,7]),excludeHolidays=$state(false),version=$state<number|null>(null),busy=$state(false),error=$state('');
	const days=['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
	async function load(){try{const result=await request<{weekdays:number[];exclude_holidays:boolean;version:number|null}>('/v1/admin/work-calendar');weekdays=result.weekdays;excludeHolidays=result.exclude_holidays;version=result.version;}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}}
	async function save(){busy=true;error='';try{await request('/v1/admin/work-calendar',{method:'POST',body:JSON.stringify({weekdays,exclude_holidays:excludeHolidays,version})});await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	onMount(load);
</script>
<details class="panel calendar"><summary>{t('Kalender perhitungan cuti')}</summary><p>{t('Perubahan kalender berlaku untuk pengajuan baru. Kuota pengajuan lama tetap memakai perhitungan saat dibuat.')}</p>{#if error}<p role="alert">{error}</p>{/if}<form class="editor-form editor-form--embedded" novalidate onsubmit={event=>{event.preventDefault();void save();}}><div class="days">{#each days as day,index}<label><input type="checkbox" value={index+1} bind:group={weekdays}/>{t(day)}</label>{/each}</div><label><input type="checkbox" bind:checked={excludeHolidays}/>{t('Kecualikan hari libur perusahaan')}</label><button disabled={busy||!weekdays.length}>{t('Simpan kalender')}</button></form></details>
<style>.calendar{padding:16px;border-radius:var(--radius)}summary{font-size:13px;font-weight:700;cursor:pointer}p{font-size:12px;color:var(--muted)}form{display:grid;gap:12px}.days{display:flex;gap:12px;flex-wrap:wrap}label{display:flex;gap:6px;align-items:center;font-size:12px}input{width:auto}</style>
