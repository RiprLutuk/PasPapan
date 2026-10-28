<script lang="ts">
	import { page } from '$app/state';
	import LegacyFeature from '../../../lib/LegacyFeature.svelte';
	import LegacyRedirect from '../../../lib/LegacyRedirect.svelte';
	import { legacyDestination } from '../../../lib/legacy-destinations';

	const pathname = $derived(page.url.pathname);
	const redirect = $derived(legacyDestination(pathname));
</script>

{#if redirect}
	<LegacyRedirect destination={redirect.destination} label={redirect.label} />
{:else}
	<LegacyFeature {pathname} />
{/if}
