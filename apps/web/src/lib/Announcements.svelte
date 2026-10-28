<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { PageHeader } from './ui';
	import Icon from './Icon.svelte';
	import { localeTag, uiText as t } from './i18n';

	let { admin = false }: { admin?: boolean } = $props();
	type Entry = { id: string; title: string; body: string; status: string; version: number; author_name: string; published_at: string | null; updated_at: string; read: boolean; read_count: number | null;visible_from:string|null;audience_team:string|null };
	let entries = $state<Entry[]>([]), search = $state(''), status = $state('');
	let loading = $state(true), busy = $state(false), error = $state(''), notice = $state('');
	let editing = $state(false), id = $state<string | null>(null), version = $state<number | null>(null);
	let title = $state(''), body = $state(''), draftStatus = $state('draft');
	let publishAt=$state(''),audienceTeam=$state(''),divisions=$state<{name:string;kind:string}[]>([]);
	let filtered = $derived(entries.filter(entry => (!status || entry.status === status) && `${entry.title} ${entry.body}`.toLocaleLowerCase(localeTag()).includes(search.toLocaleLowerCase(localeTag()))));
	const statusLabel = (value: string) => t(value === 'published' ? 'Dipublikasikan' : value === 'archived' ? 'Diarsipkan' : 'Draft');
	async function load() {
		loading = true; error = '';
		try { entries = await request<Entry[]>(admin ? '/v1/admin/announcements' : '/v1/announcements'); }
		catch (cause) { error = cause instanceof Error ? cause.message : t('Data tidak dapat dimuat.'); }
		finally { loading = false; }
	}
	function edit(entry?: Entry) {
		id = entry?.id ?? null; version = entry?.version ?? null;
		title = entry?.title ?? ''; body = entry?.body ?? ''; draftStatus = entry?.status ?? 'draft'; editing = true;
		audienceTeam=entry?.audience_team??'';publishAt=entry?.visible_from?new Date(new Date(entry.visible_from).getTime()-new Date(entry.visible_from).getTimezoneOffset()*60000).toISOString().slice(0,16):'';
	}
	async function save() {
		busy = true; error = ''; notice = '';
		try {
			await request('/v1/admin/announcements', { method: 'POST', body: JSON.stringify({ id, version, title, body, status: draftStatus,visible_from:publishAt?new Date(publishAt).toISOString():null,audience_team:audienceTeam||null }) });
			editing = false; notice = t('Pengumuman tersimpan.'); await load();
		} catch (cause) { error = cause instanceof Error ? cause.message : t('Gagal menyimpan data.'); }
		finally { busy = false; }
	}
	async function markRead(entry: Entry) {
		busy = true; error = '';
		try {
			await request(`/v1/announcements/${entry.id}/read`, { method: 'POST' });
			entries = entries.map(item => item.id === entry.id ? { ...item, read: true } : item);
		} catch (cause) { error = cause instanceof Error ? cause.message : t('Gagal memperbarui status.'); }
		finally { busy = false; }
	}
	onMount(()=>{void load();if(admin)void request<typeof divisions>('/v1/organization/reference-data').then(items=>divisions=items.filter(item=>item.kind==='division')).catch(()=>{});});
</script>

<svelte:head><title>{t('Pengumuman')} · PasPapan</title></svelte:head>
<main class="workspace-page announcements-page" id="main-content" tabindex="-1">
	<PageHeader title={t('Pengumuman')} description={t('Informasi terbaru untuk tim.')} backHref={admin ? '/admin/dashboard' : '/app/today'}>
		{#snippet actions()}{#if admin}<button onclick={() => edit()}><Icon name="plus" size={16} /> {t('Buat pengumuman')}</button>{/if}{/snippet}
	</PageHeader>
	{#if error}<p role="alert" class="error">{error} <button class="quiet" onclick={load}>{t('Coba lagi')}</button></p>{/if}
	{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	<div class="announcement-layout" class:editing>
		{#if admin && editing}
			<form class="panel editor" onsubmit={event => { event.preventDefault(); void save(); }}>
				<h2>{id ? t('Edit pengumuman') : t('Buat pengumuman')}</h2>
				<label>{t('Judul')}<input bind:value={title} maxlength="200" required /></label>
				<label>{t('Isi pengumuman')}<textarea rows="8" bind:value={body} maxlength="20000" required></textarea></label>
				<label>{t('Audiens divisi')}<select bind:value={audienceTeam}><option value="">{t('Seluruh perusahaan')}</option>{#each divisions as division}<option value={division.name}>{division.name}</option>{/each}</select></label>
				<label>{t('Tayang mulai (opsional)')}<input type="datetime-local" bind:value={publishAt}/></label>
				<label>{t('Status')}<select bind:value={draftStatus}><option value="draft">{t('Draft')}</option><option value="published">{t('Dipublikasikan')}</option><option value="archived">{t('Diarsipkan')}</option></select></label>
				<div class="actions"><button type="button" class="quiet" disabled={busy} onclick={() => editing = false}>{t('Batal')}</button><button disabled={busy}>{busy ? t('Menyimpan…') : t('Simpan')}</button></div>
			</form>
		{/if}
		<section aria-label={t('Daftar pengumuman')} aria-busy={loading}>
			<div class="filters"><input type="search" bind:value={search} placeholder={t('Cari pengumuman…')} aria-label={t('Cari pengumuman')} />{#if admin}<select bind:value={status} aria-label={t('Status')}><option value="">{t('Semua status')}</option>{#each ['draft', 'published', 'archived'] as value}<option {value}>{statusLabel(value)}</option>{/each}</select>{/if}</div>
			{#if loading}<p class="muted" role="status">{t('Memuat…')}</p>{:else if !filtered.length && !error}<div class="panel empty"><Icon name="megaphone" size={24} /><h2>{t('Belum ada pengumuman')}</h2><p>{t('Informasi terbaru dari perusahaan akan muncul di sini.')}</p></div>{/if}
			<div class="feed">{#each filtered as entry (entry.id)}
				<article class="panel announcement">
					<div class="entry-heading"><h2>{entry.title}</h2><span class="badge">{admin ? statusLabel(entry.status) : entry.read ? t('Sudah dibaca') : t('Baru')}</span></div>
					<p class="muted metadata">{entry.author_name} · {new Intl.DateTimeFormat(localeTag(), { dateStyle: 'medium' }).format(new Date(entry.published_at ?? entry.updated_at))}</p>
					<p class="body">{entry.body}</p>
					<div class="actions">{#if admin}<small class="muted">{entry.read_count ?? 0} {t('pembaca')}</small><button class="quiet" onclick={() => edit(entry)}>{t('Edit')}</button>{:else if !entry.read}<button class="quiet" disabled={busy} onclick={() => markRead(entry)}><Icon name="check" size={14} /> {t('Tandai dibaca')}</button>{/if}</div>
				</article>
			{/each}</div>
		</section>
	</div>
</main>

<style>
	.announcements-page { display: grid; gap: 12px; align-content: start; }
	.announcement-layout { display: grid; gap: 12px; min-width: 0; }
	.announcement-layout > section { min-width: 0; }
	.filters { display: flex; gap: 8px; margin-bottom: 12px; }
	.filters input { flex: 1; min-width: 0; }
	.filters select { width: 160px; }
	.feed { display: grid; gap: 10px; }
	.announcement, .editor { padding: 16px; border-radius: var(--radius); }
	.entry-heading { display: flex; justify-content: space-between; align-items: start; gap: 12px; }
	h2 { margin: 0; font-size: 15px; }
	.metadata { font-size: 11px; margin: 6px 0 12px; }
	.body { white-space: pre-wrap; overflow-wrap: anywhere; font-size: 13px; line-height: 1.7; }
	.actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin-top: 12px; }
	.actions small { margin-right: auto; }
	.muted { color: var(--muted); }
	.error { color: var(--danger, #b64032); }
	@media (min-width: 1024px) { .announcement-layout.editing { grid-template-columns: minmax(300px, .8fr) minmax(0, 1.2fr); align-items: start; } }
</style>
