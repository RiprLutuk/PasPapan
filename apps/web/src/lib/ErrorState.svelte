<script lang="ts">
 import RefreshButton from './ui/RefreshButton.svelte';
 import Icon from './Icon.svelte';
 import { uiText as t } from './i18n';
 let {status=500,retry}: {status?:number;retry?:()=>void|Promise<void>}=$props();
 let copy=$derived(status===404?['Halaman tidak ditemukan','Halaman yang Anda cari tidak tersedia. Periksa alamat atau kembali ke beranda.']:status===403?['Akses dibatasi','Akun Anda tidak memiliki izin untuk membuka halaman ini.']:status===401?['Sesi berakhir','Silakan masuk kembali untuk melanjutkan.']:status===503||status===502?['Layanan belum tersedia','Koneksi ke layanan sedang terganggu. Coba lagi setelah beberapa saat.']:['Terjadi kendala pada layanan','Halaman belum dapat dimuat. Coba lagi atau kembali ke beranda.']);
</script>
<section class="error-state" aria-labelledby="error-title">
 <span class="error-icon"><Icon name={status===404?'search':status===403?'shield':'panel'} size={25}/></span>
 <span class="error-code">{status}</span><h1 id="error-title">{t(copy[0])}</h1><p>{t(copy[1])}</p>
 <div class="error-actions">{#if retry}<RefreshButton onclick={retry} label={t('Coba lagi')}/>{/if}<a href={status===401?'/login':'/app/today'}>{t(status===401?'Masuk kembali':'Kembali ke beranda')}<Icon name="arrow" size={15}/></a></div>
</section>
<style>
 .error-state{display:grid;justify-items:center;align-content:center;gap:12px;min-height:60svh;padding:32px 20px;text-align:center;color:var(--ink)}.error-icon{display:grid;place-items:center;width:54px;height:54px;border-radius:var(--radius-lg);color:var(--muted);background:var(--surface-alt)}.error-code{font-size:12px;letter-spacing:.12em;color:var(--muted);font-variant-numeric:tabular-nums}h1{font-size:clamp(20px,3vw,28px);margin:0;letter-spacing:-.04em}p{max-width:420px;font-size:13px;line-height:1.7;color:var(--muted);margin:0}.error-actions{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px;margin-top:10px}.error-actions a{display:flex;align-items:center;gap:8px;min-height:36px;padding:8px 12px;border-radius:var(--radius-sm);background:var(--primary-soft);color:var(--primary-strong);font-size:12px;font-weight:650;text-decoration:none}
</style>
