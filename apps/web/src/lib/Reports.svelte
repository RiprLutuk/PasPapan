<script lang="ts">
 import { PageHeader } from './ui';
 let loading=$state(false),error=$state('');
 async function download(){loading=true;error='';try{const response=await fetch('/v1/reports/attendance.csv',{credentials:'include'});if(!response.ok)throw new Error('Ekspor presensi gagal.');const url=URL.createObjectURL(await response.blob());const a=document.createElement('a');a.href=url;a.download='attendance-report.csv';a.click();URL.revokeObjectURL(url);}catch(e){error=e instanceof Error?e.message:'Ekspor gagal.';}finally{loading=false;}}
</script>
<main class="workspace-page"><PageHeader title="Reports" description="Ekspor data native tanpa memuat seluruh laporan ke browser." backHref="/admin/dashboard"/>{#if error}<p class="alert">{error}</p>{/if}<section class="panel"><div><h2>Attendance CSV</h2><p>Berisi hingga 10.000 catatan presensi terbaru perusahaan.</p></div><button disabled={loading} onclick={download}>{loading?'Menyiapkan…':'Unduh CSV'}</button></section></main>
<style>.workspace-page{display:grid;gap:12px}.panel{padding:16px;border-radius:var(--radius);display:flex;justify-content:space-between;align-items:center;gap:16px}.panel p{color:var(--muted);font-size:13px}.alert{color:var(--danger)}@media(max-width:600px){.panel{align-items:flex-start;flex-direction:column}}</style>
