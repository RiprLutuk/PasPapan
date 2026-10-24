<script lang="ts">
	let { size = 32 }: { size?: number } = $props();
</script>
<span class="identity"><img src="/brand/mark.svg" width={size} height={size} alt="" /><span>PasPapan</span></span>
<style>
	.identity{display:inline-flex;align-items:center;gap:9px;white-space:nowrap;font-weight:800;letter-spacing:-.7px;line-height:1}.identity img{flex-shrink:0;object-fit:contain}.identity>span{color:var(--brand-ink,#1e331a)}
</style>
