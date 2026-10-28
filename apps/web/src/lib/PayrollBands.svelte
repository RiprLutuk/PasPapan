<script lang="ts">
	import { uiText as t } from './i18n';
	export type Band={up_to:number|null;rate_bps:number};
	let {bands=$bindable<Band[]>([])}:{bands:Band[]}=$props();
</script>
<div class="bands"><p>{t('Batas atas meningkat. Baris terakhir tanpa batas; tarif dalam basis poin.')}</p>{#each bands as band,index}<div class="band"><label>{t('Batas penghasilan (Rp)')}{#if index===bands.length-1}<span>{t('Tanpa batas')}</span>{:else}<input type="number" bind:value={band.up_to} min="1" max="999999999999" step="1" required/>{/if}</label><label>{t('Tarif (basis poin)')}<input type="number" bind:value={band.rate_bps} min="0" max="10000" step="1" required/></label>{#if index<bands.length-1}<button type="button" class="quiet" onclick={()=>bands=bands.filter((_,i)=>i!==index)}>{t('Hapus')}</button>{/if}</div>{/each}<button type="button" class="quiet" disabled={bands.length>=50} onclick={()=>bands=[...bands.slice(0,-1),{up_to:1,rate_bps:0},bands.at(-1)??{up_to:null,rate_bps:0}]}>{t('Tambah lapisan')}</button></div>
<style>.bands{grid-column:1/-1;display:grid;gap:8px;font-size:12px}.band{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end}p{color:var(--muted)}label span{display:block;padding:10px 0}</style>
