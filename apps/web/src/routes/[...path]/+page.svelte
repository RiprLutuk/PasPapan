<script lang="ts">
	import { page } from '$app/state';
	import AppShell from '../../lib/AppShell.svelte';
	import LegacyFeature from '../../lib/LegacyFeature.svelte';
	import LegacyRedirect from '../../lib/LegacyRedirect.svelte';
	import { legacyDestination } from '../../lib/legacy-destinations';

	let pathname = $derived(`/${page.params.path ?? ''}`);
	let isGuestPage = $derived(['/register', '/forgot-password', '/reset-password', '/two-factor-challenge'].some((path) => pathname === path || pathname.startsWith(`${path}/`)));
	let redirect = $derived(legacyDestination(pathname));
</script>

{#if isGuestPage}
	<LegacyFeature {pathname} />
{:else if redirect}
	<AppShell><LegacyRedirect destination={redirect.destination} label={redirect.label} /></AppShell>
{:else}
	<AppShell><LegacyFeature {pathname} /></AppShell>
{/if}
