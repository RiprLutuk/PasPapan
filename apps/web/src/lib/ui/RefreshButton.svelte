<script lang="ts">
 import Icon from '../Icon.svelte';
 import { uiText as t } from '../i18n';
 let { onclick, loading=false, disabled=false, label }: {
  onclick: () => unknown | Promise<unknown>;
  loading?: boolean;
  disabled?: boolean;
  label?: string;
 } = $props();
 let pending = $state(false);
 let busy = $derived(loading || pending);
 async function refresh() {
  if (busy || disabled) return;
  pending = true;
  try { await onclick(); } finally { pending = false; }
 }
</script>
<button type="button" class="quiet refresh-button" disabled={disabled || busy} aria-busy={busy} onclick={refresh}>
 <span class="refresh-icon" class:spinning={busy}><Icon name="refresh" size={15}/></span>
 <span>{label ?? t('Segarkan')}</span>
</button>
<style>
 .refresh-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:34px;padding:8px 11px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--surface);color:var(--ink);white-space:nowrap;font-size:12px;font-weight:650;line-height:1;cursor:pointer}
 .refresh-button:hover:not(:disabled){background:var(--surface-alt);color:var(--primary-strong)}
 .refresh-button:disabled{opacity:.55;cursor:not-allowed}
 .refresh-icon{display:inline-flex;align-items:center;justify-content:center;flex:none}
 .spinning{animation:refresh-button-spin 1s linear infinite}
 @keyframes refresh-button-spin{to{transform:rotate(360deg)}}
 @media(prefers-reduced-motion:reduce){.spinning{animation:none}}
</style>
