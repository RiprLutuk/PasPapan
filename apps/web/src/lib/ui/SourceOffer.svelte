<script lang="ts">
	import Icon from '../Icon.svelte';
	import { uiText as t } from '../i18n';
	const sourceUrl = import.meta.env.PASPAPAN_SOURCE_URL;
</script>
<a class="source-offer" href={sourceUrl} target="_blank" rel="noopener noreferrer"><Icon name="code" size={14}/><span>{t('Kode sumber')} · AGPL-3.0</span></a>
<style>
	.source-offer { display:flex; align-items:center; justify-content:center; gap:6px; min-height:32px; padding:6px 10px; color:var(--muted); font-size:11px; text-decoration:none; border-radius:var(--radius-sm); }
	.source-offer:hover { color:var(--ink); background:var(--surface-alt); }
</style>
