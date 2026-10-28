<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { PageHeader, RefreshButton } from './ui';
	import Icon from './Icon.svelte';
	import AssetValuation from './AssetValuation.svelte';
	import { uiText as t, localeTag } from './i18n';
	let { admin = false }: { admin?: boolean } = $props();
	type Asset = { id: string; asset_code: string; name: string; serial_number: string; condition: string; notes: string; version: number; assignment_id: string | null; user_id: string | null; assigned_at: string | null; acknowledged_at: string | null; assignee_name: string | null };
	type Member = { id: string; full_name: string };
	let entries = $state<Asset[]>([]), members = $state<Member[]>([]);
	let loading = $state(true), busy = $state(false), error = $state(''), notice = $state(''), search = $state('');
	let editor = $state(false), id = $state<string | null>(null), version = $state<number | null>(null);
	let assetCode = $state(''), name = $state(''), serialNumber = $state(''), condition = $state('good'), notes = $state('');
	let assignees = $state<Record<string, string>>({}), returnNotes = $state<Record<string, string>>({}), returnConditions = $state<Record<string, string>>({});
	let histories=$state<Record<string,{id:string;full_name:string;assigned_at:string;returned_at:string|null;return_note:string|null;return_condition:string|null}[]>>({});
	async function loadHistory(id:string){try{histories[id]=await request(`/v1/assets/${id}/history`);}catch(cause){error=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.');}}
	let filtered = $derived(entries.filter(entry => `${entry.name} ${entry.asset_code} ${entry.serial_number} ${entry.assignee_name ?? ''}`.toLocaleLowerCase(localeTag()).includes(search.toLocaleLowerCase(localeTag()))));
	const conditions = ['good', 'damaged', 'maintenance', 'retired'];
	function conditionLabel(value: string) { return t(({ good: 'Baik', damaged: 'Rusak', maintenance: 'Perawatan', retired: 'Tidak digunakan' } as Record<string, string>)[value] ?? value); }
	async function load() {
		loading = true; error = '';
		try {
			entries = await request<Asset[]>(admin ? '/v1/admin/assets' : '/v1/assets');
			if (admin) members = await request<Member[]>('/v1/admin/operational-members');
		} catch (cause) { error = cause instanceof Error ? cause.message : t('Data tidak dapat dimuat.'); }
		finally { loading = false; }
	}
	function edit(entry?: Asset) {
		id = entry?.id ?? null; version = entry?.version ?? null; assetCode = entry?.asset_code ?? ''; name = entry?.name ?? '';
		serialNumber = entry?.serial_number ?? ''; condition = entry?.condition ?? 'good'; notes = entry?.notes ?? ''; editor = true;
	}
	async function mutate(path: string, body: unknown) {
		busy = true; error = ''; notice = '';
		try { await request(path, { method: 'POST', body: JSON.stringify(body) }); notice = t('Perubahan tersimpan.'); await load(); return true; }
		catch (cause) { error = cause instanceof Error ? cause.message : t('Gagal menyimpan data.'); return false; }
		finally { busy = false; }
	}
	async function save() {
		if (await mutate('/v1/admin/assets', { id, version, asset_code: assetCode, name, serial_number: serialNumber, condition, notes })) editor = false;
	}
	onMount(load);
</script>
<svelte:head><title>{t('Inventaris')} · PasPapan</title></svelte:head>
<main class="workspace-page assets-page" id="main-content" tabindex="-1">
	<PageHeader title={admin ? t('Aset & Inventaris') : t('Aset saya')} description={t('Perangkat kerja dan serah terima aset.')} backHref={admin ? '/admin/dashboard' : '/app/today'}>
		{#snippet actions()}{#if admin}<button onclick={() => edit()}><Icon name="plus" size={15} /> {t('Tambah aset')}</button>{/if}{/snippet}
	</PageHeader>
	{#if error}<p class="error" role="alert">{error} <RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}
	{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	{#if admin && editor}<form class="panel editor" onsubmit={event => { event.preventDefault(); void save(); }}>
		<h2>{id ? t('Edit aset') : t('Tambah aset')}</h2>
		<div class="fields"><label>{t('Kode aset')}<input bind:value={assetCode} maxlength="80" required /></label><label>{t('Nama aset')}<input bind:value={name} maxlength="200" required /></label><label>{t('Nomor seri')}<input bind:value={serialNumber} maxlength="200" /></label><label>{t('Kondisi')}<select bind:value={condition}>{#each conditions as value}<option {value}>{conditionLabel(value)}</option>{/each}</select></label></div>
		<label>{t('Catatan')}<textarea bind:value={notes} rows="2" maxlength="4000"></textarea></label>
		<div class="actions"><button type="button" class="quiet" onclick={() => editor = false} disabled={busy}>{t('Batal')}</button><button disabled={busy}>{t('Simpan')}</button></div>
	</form>{/if}
	<input type="search" bind:value={search} placeholder={t('Cari aset…')} aria-label={t('Cari aset')} />
	<section class="asset-grid" aria-busy={loading} aria-label={t('Daftar aset')}>
		{#if loading}<p role="status">{t('Memuat…')}</p>{:else if !filtered.length && !error}<div class="panel empty"><Icon name="box" size={24} /><h2>{t('Belum ada aset')}</h2><p>{t('Aset yang diserahkan akan tercatat di sini.')}</p></div>{/if}
		{#each filtered as entry (entry.id)}<article class="panel asset">
			{#if admin}<AssetValuation id={entry.id}/>{/if}
			<div class="heading"><div><small class="muted">{entry.asset_code}</small><h2>{entry.name}</h2></div><span class="badge">{conditionLabel(entry.condition)}</span></div>
			{#if entry.serial_number}<p class="muted">{t('Nomor seri')}: {entry.serial_number}</p>{/if}
			{#if entry.notes}<p class="notes">{entry.notes}</p>{/if}
			<details ontoggle={event=>{if(event.currentTarget.open)void loadHistory(entry.id);}}><summary>{t('Riwayat serah terima')}</summary>{#each histories[entry.id]??[] as history}<div class="assignment"><div><strong>{history.full_name}</strong><small>{history.assigned_at.slice(0,16)} — {history.returned_at?.slice(0,16)??t('Masih dipinjam')}</small>{#if history.return_condition}<small>{conditionLabel(history.return_condition)} · {history.return_note??''}</small>{/if}</div></div>{/each}</details>
			{#if entry.assignment_id}<div class="assignment"><Icon name="profile" size={17} /><div><strong>{entry.assignee_name}</strong><small>{entry.acknowledged_at ? t('Penerimaan dikonfirmasi') : t('Menunggu konfirmasi penerimaan')}</small></div></div>{:else}<p class="muted">{t('Tersedia')}</p>{/if}
			{#if admin}
				<details><summary>{t('Kelola aset')}</summary><div class="asset-controls">
				<button class="quiet" disabled={busy} onclick={() => edit(entry)}>{t('Edit aset')}</button>
				{#if entry.assignment_id}
					<label>{t('Kondisi pengembalian')}<select bind:value={returnConditions[entry.id]}>{#each conditions as value}<option {value}>{conditionLabel(value)}</option>{/each}</select></label>
					<label>{t('Catatan pengembalian')}<input bind:value={returnNotes[entry.id]} maxlength="4000" /></label>
					<button disabled={busy} onclick={() => mutate(`/v1/admin/assets/${entry.id}/return`, { condition: returnConditions[entry.id] ?? 'good', note: returnNotes[entry.id] ?? '' })}>{t('Konfirmasi pengembalian')}</button>
				{:else if entry.condition === 'good'}
					<label>{t('Serahkan kepada')}<select bind:value={assignees[entry.id]}><option value="">{t('Pilih karyawan')}</option>{#each members as member}<option value={member.id}>{member.full_name}</option>{/each}</select></label>
					<button disabled={busy || !assignees[entry.id]} onclick={() => mutate(`/v1/admin/assets/${entry.id}/assign`, { user_id: assignees[entry.id] })}>{t('Serahkan aset')}</button>
				{/if}</div></details>
			{:else if entry.assignment_id && !entry.acknowledged_at}<button class="quiet" disabled={busy} onclick={() => mutate(`/v1/assets/${entry.id}/acknowledge`, {})}><Icon name="check" size={15} /> {t('Konfirmasi penerimaan')}</button>{/if}
		</article>{/each}
	</section>
</main>
<style>
	.assets-page { display: grid; gap: 12px; align-content: start; }
	.asset-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 12px; align-items:stretch; }
	.asset, .editor { padding: 16px; border-radius: var(--radius); min-width: 0; }
	.heading { display: flex; align-items: flex-start; gap: 12px; justify-content: space-between; }
	h2 { font-size: 15px; margin: 3px 0 8px; }
	p { font-size: 12px; overflow-wrap: anywhere; }
	.notes { white-space: pre-wrap; }
	.muted { color: var(--muted); }
	.assignment { display: flex; align-items: center; gap: 8px; padding: 10px; background: var(--surface-subtle, var(--primary-soft)); border-radius: var(--radius-sm); margin: 12px 0; }
	.assignment strong, .assignment small { display: block; font-size: 12px; }
	.assignment small { color: var(--muted); font-size: 11px; margin-top: 3px; }
	.fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
	.actions { display: flex; gap: 8px; justify-content: flex-end; }
	summary { cursor: pointer; font-size: 12px; color: var(--primary-strong); padding-block: 8px; }
	.asset-controls { display: grid; gap: 8px; }
	.error { color: var(--danger, #b64032); }
	@media (max-width: 480px) { .fields { grid-template-columns: 1fr; } }
.asset{display:flex;flex-direction:column}.asset>details:last-child,.asset>button:last-child,.asset>.assignment:last-child,.asset>p:last-child{margin-top:auto}
</style>
