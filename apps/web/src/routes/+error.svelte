<script lang="ts">
 import { page } from '$app/state';
 import Brand from '../lib/Brand.svelte';
 import ErrorState from '../lib/ErrorState.svelte';
 import { uiText as t } from '../lib/i18n';
</script>
<svelte:head><title>{page.status} · PasPapan</title><meta name="robots" content="noindex"/></svelte:head>
<div class="error-page"><header><a href="/app/today" aria-label={`PasPapan — ${t('Beranda')}`}><Brand size={28}/></a></header><main id="main-content"><ErrorState status={page.status} retry={page.status>=500?()=>window.location.reload():undefined}/></main></div>
<style>.error-page{min-height:100svh;background:var(--canvas)}header{padding:20px 24px}header a{color:var(--ink);text-decoration:none;font-size:18px}main{max-width:1000px;margin:auto}</style>
