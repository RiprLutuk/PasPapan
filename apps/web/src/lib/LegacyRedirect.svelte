<script lang="ts">
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { uiText as t } from './i18n';

	let { destination, label }: { destination: string; label: string } = $props();

	onMount(() => {
		void goto(destination, { replaceState: true });
	});
</script>

<main class="workspace-page" aria-live="polite">
	<p class="eyebrow">{t('MEMBUKA WORKSPACE')}</p>
	<h1>{t(label)}</h1>
	<p class="muted">{t('Mengalihkan ke halaman yang sesuai…')}</p>
	<a href={destination}>{t('Lanjutkan')}</a>
</main>

<style>
	.muted { margin: 8px 0 14px; color: var(--muted); }
</style>
