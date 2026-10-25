<script lang="ts">
	import { page } from '$app/state';
	import AppShell from '../../lib/AppShell.svelte';
	import LegacyFeature from '../../lib/LegacyFeature.svelte';

	let pathname = $derived(`/${page.params.path ?? ''}`);
	let isGuestPage = $derived(['/register', '/forgot-password', '/reset-password', '/two-factor-challenge'].some((path) => pathname === path || pathname.startsWith(`${path}/`)));
</script>

{#if isGuestPage}
	<LegacyFeature {pathname} />
{:else}
	<AppShell><LegacyFeature {pathname} /></AppShell>
{/if}
