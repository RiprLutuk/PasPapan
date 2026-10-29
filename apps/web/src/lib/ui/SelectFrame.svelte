<script lang="ts">
	import type { Snippet } from 'svelte';
	import Icon from '../Icon.svelte';
	let { children }: { children: Snippet } = $props();
</script>
<span class="select-frame">
	{@render children()}
	<span class="select-arrow" aria-hidden="true"><Icon name="chevron" size={16}/></span>
</span>
<style>
	.select-frame { position:relative; display:block; min-width:0; width:100%; }
	.select-frame :global(select) { appearance:none; -webkit-appearance:none; padding-inline-end:46px !important; }
	.select-arrow { position:absolute; inset-inline-end:16px; top:50%; display:flex; pointer-events:none; color:var(--muted); transform:translateY(-50%); transition:transform 140ms ease; }
	.select-frame:has(:global(select:open)) .select-arrow { transform:translateY(-50%) rotate(180deg); }
	.select-frame:has(:global(select:disabled)) .select-arrow { opacity:.5; }
	@media(prefers-reduced-motion:reduce) { .select-arrow { transition:none; } }
</style>
