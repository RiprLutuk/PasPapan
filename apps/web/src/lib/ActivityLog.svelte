<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { PageHeader, RefreshButton } from './ui';
	import { localeTag, uiText as t } from './i18n';

	type AuditEvent = {
		id: string;
		title: string;
		detail: string;
		status: string;
		updated: string;
	};

	let entries = $state<AuditEvent[]>([]);
	let query = $state('');
	let loading = $state(true);
	let error = $state('');

	let filtered = $derived(
		entries.filter((entry) =>
			`${entry.title} ${entry.detail} ${entry.updated}`
				.toLocaleLowerCase(localeTag())
				.includes(query.trim().toLocaleLowerCase(localeTag()))
		)
	);

	async function load() {
		loading = true;
		error = '';
		try {
			entries = await request<AuditEvent[]>('/v1/admin/activity-logs');
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Data tidak dapat dimuat.');
		} finally {
			loading = false;
		}
	}

	function eventLabel(value: string) {
		return value.replaceAll('_', ' ').replace(/\b\p{L}/gu, (letter) => letter.toLocaleUpperCase(localeTag()));
	}

	onMount(load);
</script>

<svelte:head><title>{t('Audit Trail')} · PasPapan</title></svelte:head>

<main class="workspace-page audit-page" id="main-content" tabindex="-1">
	<PageHeader
		title={t('Audit Trail')}
		description={t('Jejak aktivitas yang tercatat untuk perusahaan aktif.')}
		backHref="/admin/dashboard"
	/>

	<section class="panel" aria-busy={loading}>
		<header class="toolbar">
			<label>
				<span>{t('Cari aktivitas')}</span>
				<input bind:value={query} type="search" placeholder={t('Jenis aktivitas, pengguna, atau waktu')} />
			</label>
			<p>{loading ? t('Memuat…') : `${filtered.length} ${t('aktivitas')}`}</p>
		</header>

		{#if error}
			<p class="alert" role="alert">{error} <RefreshButton onclick={load} label={t('Coba lagi')} /></p>
		{:else if loading}
			<p role="status">{t('Memuat…')}</p>
		{:else if !filtered.length}
			<div class="empty"><h2>{t('Belum ada aktivitas')}</h2><p>{t('Aktivitas perusahaan yang tercatat akan muncul di sini.')}</p></div>
		{:else}
			<div class="table-wrap">
				<table>
					<thead><tr><th>{t('Aktivitas')}</th><th>{t('Detail')}</th><th>{t('Waktu')}</th></tr></thead>
					<tbody>
						{#each filtered as entry (entry.id)}
							<tr>
								<td><strong>{eventLabel(entry.title)}</strong><small>{entry.status}</small></td>
								<td>{entry.detail}</td>
								<td><time datetime={entry.updated.replace(' ', 'T')}>{entry.updated}</time></td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		{/if}
	</section>
</main>

<style>
	.audit-page{display:grid;gap:12px;align-content:start}.panel{padding:16px;border-radius:var(--radius)}.toolbar{display:flex;align-items:end;justify-content:space-between;gap:12px;margin-bottom:12px}.toolbar label{display:grid;gap:5px;min-width:min(100%,420px);font-size:11px;font-weight:650}.toolbar p,.alert,.empty p,small{color:var(--muted);font-size:11px}.toolbar p{margin:0}.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:var(--radius-sm)}table{width:100%;border-collapse:collapse;text-align:start;font-size:12px}th{padding:10px 12px;background:var(--surface-alt);color:var(--muted);font-size:10px;font-weight:700}td{padding:11px 12px;border-top:1px solid var(--line);vertical-align:top}td small{display:block;margin-top:3px}time{white-space:nowrap;font-variant-numeric:tabular-nums;color:var(--muted)}.empty{padding:30px 12px;text-align:center}.empty h2{margin:0;font-size:15px}.empty p{margin:7px 0 0}.alert{display:flex;align-items:center;gap:8px}@media(max-width:640px){.toolbar{display:grid;align-items:start}.toolbar label{min-width:0}th:nth-child(2),td:nth-child(2){display:none}}
</style>
