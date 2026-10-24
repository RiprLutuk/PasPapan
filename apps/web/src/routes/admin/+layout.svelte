<script lang="ts">
	import AppShell from '../../lib/AppShell.svelte';
	import type { LayoutProps } from './$types';
	let { children }: LayoutProps = $props();
</script>
<AppShell admin>{@render children()}</AppShell>
