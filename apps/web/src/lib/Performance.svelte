<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { PageHeader, RefreshButton } from './ui';
	import { workSession } from './session';
	import Icon from './Icon.svelte';
	import AppraisalCycles, {type Cycle} from './AppraisalCycles.svelte';
	import { uiText as t } from './i18n';
	let { admin=false }:{admin?:boolean}=$props();
	type Goal={id:string;user_id:string;full_name:string;title:string;unit:string;target:number;actual:number;due_on:string;status:string;evidence:string;review_note:string;version:number;cycle_id:string|null;weight:number;score:number|null};
	const session=workSession();
	let entries=$state<Goal[]>([]),members=$state<{id:string;full_name:string}[]>([]),loading=$state(true),busy=$state(false),error=$state('');
	let cycles=$state<Cycle[]>([]),cycleId=$state(''),weight=$state(100);
	let summaries=$derived(Object.values(entries.filter(entry=>entry.cycle_id).reduce((result,entry)=>{const key=entry.user_id+entry.cycle_id;const row=result[key]??={name:entry.full_name,cycle:cycles.find(c=>c.id===entry.cycle_id)?.name??'',weighted:0,weight:0};row.weighted+=(entry.score??0)*entry.weight;row.weight+=entry.weight;return result;},{} as Record<string,{name:string;cycle:string;weighted:number;weight:number}>)));
	let createOpen=$state(false),userId=$state(''),title=$state(''),unit=$state(''),target=$state(1),dueOn=$state('');
	let actuals=$state<Record<string,number>>({}),evidence=$state<Record<string,string>>({}),notes=$state<Record<string,string>>({});
	function statusLabel(status:string){return t(({active:'Aktif',submitted:'Menunggu penilaian',approved:'Disetujui',needs_revision:'Perlu revisi'} as Record<string,string>)[status]??status);}
	async function load(){loading=true;error='';try{entries=await request<Goal[]>(admin?'/v1/admin/performance-goals':'/v1/performance-goals');if(admin)members=await request<{id:string;full_name:string}[]>('/v1/admin/operational-members');for(const entry of entries){actuals[entry.id]=entry.actual;evidence[entry.id]=entry.evidence;}}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}finally{loading=false;}}
	async function mutate(path:string,body:unknown){busy=true;error='';try{await request(path,{method:'POST',body:JSON.stringify(body)});createOpen=false;await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal menyimpan data.');}finally{busy=false;}}
	onMount(load);
</script>
<svelte:head><title>{t('Evaluasi KPI')} · PasPapan</title></svelte:head>
<main class="workspace-page performance-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Evaluasi KPI')} description={t('Target terukur, bukti capaian, dan penilaian.')} backHref={admin?'/admin/dashboard':'/app/today'}>{#snippet actions()}{#if admin}<button onclick={()=>createOpen=!createOpen}><Icon name="plus" size={15}/>{t('Tambah target')}</button>{/if}{/snippet}</PageHeader>
	{#if error}<p role="alert">{error}<RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}
	<AppraisalCycles {admin} onupdated={value=>cycles=value}/>
	{#if summaries.length}<section class="panel">{#each summaries as summary}<p>{summary.name} · {summary.cycle}: <strong>{(summary.weighted/summary.weight).toFixed(1)}%</strong></p>{/each}<p class="muted">{t('Nilai berbobot memakai capaian yang disetujui; target belum disetujui bernilai nol. Bobot dinormalisasi per karyawan dan siklus.')}</p></section>{/if}
	{#if admin&&createOpen}<form class="panel editor" onsubmit={event=>{event.preventDefault();void mutate('/v1/admin/performance-goals',{user_id:userId,title,unit,target,due_on:dueOn,cycle_id:cycleId||null,weight});}}><h2>{t('Target baru')}</h2><div class="fields"><label>{t('Karyawan')}<select bind:value={userId} required><option value="">{t('Pilih karyawan')}</option>{#each members as member}<option value={member.id}>{member.full_name}</option>{/each}</select></label><label>{t('Judul')}<input bind:value={title} maxlength="200" required/></label><label>{t('Target')}<input type="number" bind:value={target} min="1" max="1000000000" step="1" required/></label><label>{t('Satuan')}<input bind:value={unit} maxlength="40" required/></label><label>{t('Siklus penilaian')}<select bind:value={cycleId}><option value="">{t('Tanpa siklus')}</option>{#each cycles.filter(c=>c.status==='open') as cycle}<option value={cycle.id}>{cycle.name}</option>{/each}</select></label><label>{t('Bobot')}<input type="number" min="1" max="1000" step="1" bind:value={weight} required/></label><label>{t('Batas waktu')}<input type="date" bind:value={dueOn} required/></label></div><button disabled={busy}>{t('Simpan')}</button></form>{/if}
	<section class="goal-grid" aria-busy={loading} aria-label={t('Daftar target KPI')}>
		{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !entries.length&&!error}<div class="panel empty"><Icon name="chart" size={24}/><h2>{t('Belum ada target KPI')}</h2><p>{t('Target dari penilai akan muncul di sini.')}</p></div>{/if}
		{#each entries as entry(entry.id)}<article class="panel goal"><div class="heading"><h2>{entry.title}</h2><span class="badge">{statusLabel(entry.status)}</span></div><p class="muted">{entry.full_name} · {entry.due_on} · {t('Bobot')} {entry.weight}</p><p class="metric"><strong>{entry.actual}</strong> / {entry.target} {entry.unit}</p><progress max={entry.target} value={Math.min(entry.actual,entry.target)} aria-label={t('Capaian')}></progress>{#if entry.evidence}<p class="evidence">{entry.evidence}</p>{/if}{#if entry.review_note}<p class="review-note">{t('Catatan penilai')}: {entry.review_note}</p>{/if}
			{#if !admin&&['active','needs_revision'].includes(entry.status)}<form onsubmit={event=>{event.preventDefault();void mutate(`/v1/performance-goals/${entry.id}/submit`,{actual:actuals[entry.id],evidence:evidence[entry.id],version:entry.version});}}><label>{t('Capaian')}<input type="number" bind:value={actuals[entry.id]} min="0" max="1000000000" step="1" required/></label><label>{t('Bukti capaian')}<textarea bind:value={evidence[entry.id]} rows="2" maxlength="4000" required></textarea></label><button disabled={busy}>{t('Kirim untuk penilaian')}</button></form>{/if}
			{#if admin&&entry.status==='submitted'&&entry.user_id!==session.current?.user.id}<label>{t('Catatan penilai')}<textarea bind:value={notes[entry.id]} rows="2" maxlength="4000"></textarea></label><div class="actions"><button disabled={busy} onclick={()=>mutate(`/v1/admin/performance-goals/${entry.id}/review`,{decision:'approved',note:notes[entry.id]??'',version:entry.version})}>{t('Setujui')}</button><button class="quiet" disabled={busy||!notes[entry.id]?.trim()} onclick={()=>mutate(`/v1/admin/performance-goals/${entry.id}/review`,{decision:'needs_revision',note:notes[entry.id],version:entry.version})}>{t('Minta revisi')}</button></div>{/if}
		</article>{/each}
	</section>
</main>
<style>
	.performance-page{display:grid;gap:12px;align-content:start}.panel{padding:16px;border-radius:var(--radius);min-width:0}.goal-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,330px),1fr));gap:12px;align-items:stretch}.heading{display:flex;gap:10px;align-items:start;justify-content:space-between}h2{font-size:15px;margin:0 0 8px}.muted{color:var(--muted);font-size:11px}.metric{font-size:13px}.metric strong{font-size:24px}progress{width:100%;height:6px;accent-color:var(--primary);margin-bottom:10px}.evidence,.review-note{font-size:12px;white-space:pre-wrap;overflow-wrap:anywhere}.review-note{padding:8px;background:var(--surface-subtle,var(--primary-soft));border-radius:var(--radius-sm)}.fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px}.actions{display:flex;gap:8px;flex-wrap:wrap}
.goal{display:flex;flex-direction:column}.goal>.actions,.goal>form:last-child{margin-top:auto;padding-top:12px}
</style>
