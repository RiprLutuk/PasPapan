<script lang="ts">
	import { page } from '$app/state';
	import LegacyFeature from '../../../lib/LegacyFeature.svelte';

	const pathname = $derived(page.url.pathname);
</script>

<LegacyFeature {pathname} />
