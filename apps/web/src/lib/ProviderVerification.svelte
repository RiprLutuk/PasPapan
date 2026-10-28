<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { uiText as t } from './i18n';
	let { purpose, resourceId, resourceBase64='', receipt = $bindable('') }: {purpose:'biometric_enrollment'|'document_signature';resourceId:string;resourceBase64?:string;receipt?:string} = $props();
	let busy=$state(false),error=$state(''),url=$state(''),challenge=$state('');
	let popup:Window|null=null;
	async function start(){busy=true;error='';receipt='';try{const result=await request<{id:string;verification_url:string}>('/v1/providers/challenges',{method:'POST',body:JSON.stringify({purpose,resource_id:resourceId})});challenge=result.id;url=result.verification_url;popup=window.open(url,'paspapan-provider','popup,width=600,height=760');}catch(cause){error=cause instanceof Error?cause.message:t('Verifikasi tidak tersedia.');}finally{busy=false;}}
	onMount(()=>{const receive=(event:MessageEvent)=>{if(!url||event.origin!==new URL(url).origin||event.source!==popup||event.data?.challenge_id!==challenge)return;if(event.data?.type==='paspapan.provider.ready'&&resourceBase64){popup?.postMessage({type:'paspapan.provider.resource',challenge_id:challenge,data_base64:resourceBase64},new URL(url).origin);return;}if(typeof event.data?.receipt!=='string')return;receipt=event.data.receipt;popup?.close();};window.addEventListener('message',receive);return()=>{window.removeEventListener('message',receive);popup?.close();};});
</script>
<div class="provider-verification">
	{#if receipt}<p role="status">{t('Bukti provider diterima. Simpan untuk validasi server.')}</p>{:else}<button type="button" class="quiet" disabled={busy} onclick={start}>{t('Verifikasi melalui provider')}</button>{/if}
	{#if url&&!receipt}<button type="button" class="quiet" onclick={()=>{popup=window.open(url,'paspapan-provider','popup,width=600,height=760');}}>{t('Buka kembali verifikasi')}</button>{/if}
	{#if error}<p role="alert">{error}</p>{/if}
</div>
<style>.provider-verification{display:grid;gap:8px;margin-block:12px}.provider-verification p{font-size:12px;margin:0}</style>
