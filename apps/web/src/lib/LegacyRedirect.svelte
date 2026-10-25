<script lang="ts">
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';

	let { destination, label }: { destination: string; label: string } = $props();

	onMount(() => {
		void goto(destination, { replaceState: true });
	});
</script>

<main class="workspace-page" aria-live="polite">
	<p class="eyebrow">MEMBUKA WORKSPACE</p>
	<h1>{label}</h1>
	<p class="muted">Mengalihkan ke halaman yang sesuai…</p>
	<a href={destination}>Lanjutkan</a>
</main>

<style>
	.muted { margin: 8px 0 14px; color: var(--muted); }
</style>
