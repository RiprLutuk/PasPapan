<script lang="ts">
	import { PageHeader, MetricCard } from './ui';
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { request, uploadFile, type StoredFile } from './api';
	import { workSession } from './session';
	import Icon from './Icon.svelte';
	import RequestPayments from './RequestPayments.svelte';
	import { localeTag, uiText as t } from './i18n';

	const session = workSession();
	function canDecide(status: string) {
		return session.current?.capabilities.includes(status === 'pending_manager' ? 'requests.approvals.manager' : 'requests.approvals.admin') ?? false;
	}

	let { approvals = false }: { approvals?: boolean } = $props();

	type Entry = {
		id: string;
		kind: string;
		starts_on: string;
		ends_on: string;
		minutes?: number | null;
		amount_cents?: number | null;
		target_user_id?: string | null;
		target_user_name?: string | null;
		attachment_file_id?: string | null;
		attachment_file_name?: string | null;
		attachment_download_url?: string | null;
		reason: string;
		details?: { corrected_check_in?:string;corrected_check_out?:string;colleague_consented?:boolean };
		status: string;
		full_name?: string;
		decision_note?: string | null;
		decided_by?: string | null;
	};
	type Colleague = { id: string; full_name: string };
	type LeaveBalance = { id: string; name: string; year: number; days: number; reserved_days: number };

	const kinds: Record<string, string> = {
		leave: t('Cuti'),
		overtime: t('Lembur'),
		attendance_correction: t('Koreksi absensi'),
		reimbursement: 'Reimbursement',
		wfh: 'WFH',
		cash_advance: t('Kasbon'),
		shift_swap: t('Tukar shift')
	};

	const statuses: Record<string, string> = {
		pending_manager: t('Menunggu manager'),
		pending_admin: t('Menunggu admin'),
		approved: t('Disetujui'),
		rejected: t('Ditolak'),
		cancelled: t('Dibatalkan')
	};

	let entries = $state<Entry[]>([]);
	let colleagues = $state<Colleague[]>([]);
	let leaveBalances = $state<LeaveBalance[]>([]), leaveTypeId = $state('');
	let swapInvitations=$state<{id:string;full_name:string;date:string;reason:string}[]>([]);
	let correctedIn=$state(''),correctedOut=$state('');
	let loading = $state(true), busy = $state(false), error = $state(''), notice = $state('');
	let kind = $state(page.url.searchParams.get('kind') ?? 'leave'), starts_on = $state(''), ends_on = $state(''), reason = $state(''), minutes = $state<number | undefined>();
	let amount = $state<number | undefined>(), targetUserId = $state('');
	let attachment = $state<StoredFile | null>(null);
	let uploading = $state(false);
	let notes = $state<Record<string, string>>({});
	let search = $state(''), status = $state('');
	let showForm = $state(page.url.searchParams.get('create') === '1' || page.url.searchParams.has('new'));

	let filtered = $derived(
		entries.filter((entry) =>
			(!page.url.searchParams.get('kind') || entry.kind === page.url.searchParams.get('kind')) &&
			(!status || entry.status === status) &&
			`${entry.full_name ?? ''} ${entry.reason} ${kinds[entry.kind] ?? entry.kind}`.toLocaleLowerCase(localeTag()).includes(search.toLocaleLowerCase(localeTag()))
		)
	);

	let summaryCounts = $derived({
		total: entries.length,
		pending: entries.filter((e) => e.status === 'pending_manager' || e.status === 'pending_admin').length,
		approved: entries.filter((e) => e.status === 'approved').length,
		rejected: entries.filter((e) => e.status === 'rejected').length
	});

	function formatMinutes(value: number) {
		const hours = Math.floor(value / 60);
		const rem = value % 60;
		if (!hours) return `${rem} ${t('menit')}`;
		if (!rem) return `${hours} ${t('jam')} (${value} ${t('menit')})`;
		return `${hours} ${t('jam')} ${rem} ${t('menit')}`;
	}

	async function load() {
		error = ''; loading = true;
		try {
			const [loadedEntries, loadedColleagues, balances, invitations] = await Promise.all([
				request<Entry[]>(approvals ? '/v1/approvals' : '/v1/requests'),
				approvals ? Promise.resolve([] as Colleague[]) : request<Colleague[]>('/v1/colleagues'),
				approvals ? Promise.resolve([] as LeaveBalance[]) : request<LeaveBalance[]>('/v1/leave-balances'),
				approvals ? Promise.resolve([]) : request<typeof swapInvitations>('/v1/shift-swap-invitations')
			]);
			entries = loadedEntries;
			colleagues = loadedColleagues;
			leaveBalances = balances;
			swapInvitations=invitations;
		}
		catch (e) { error = e instanceof Error ? e.message : t('Data tidak dapat dimuat.'); }
		finally { loading = false; }
	}

	async function handleFileSelect(event: Event) {
		const input = event.currentTarget as HTMLInputElement;
		const file = input.files?.[0];
		if (!file) return;
		uploading = true;
		error = '';
		try {
			const dataUrl = await new Promise<string>((resolve, reject) => {
				const reader = new FileReader();
				reader.onload = () => resolve(String(reader.result ?? ''));
				reader.onerror = () => reject(new Error(t('Gagal membaca berkas.')));
				reader.readAsDataURL(file);
			});
			attachment = await uploadFile({
				file_name: file.name,
				content_type: file.type || 'application/octet-stream',
				data_base64: dataUrl
			});
		} catch (e) {
			error = e instanceof Error ? e.message : t('Gagal mengunggah lampiran.');
		} finally {
			uploading = false;
			input.value = '';
		}
	}

	async function submit() {
		busy = true; error = ''; notice = '';
		try {
			await request('/v1/requests', {
				method: 'POST',
				body: JSON.stringify({
					kind,
					starts_on,
					ends_on,
					minutes: kind === 'overtime' ? (minutes ?? null) : null,
					amount_cents: ['reimbursement', 'cash_advance'].includes(kind) && amount ? Math.round(amount * 100) : null,
					target_user_id: kind === 'shift_swap' ? targetUserId || null : null,
					attachment_file_id: attachment?.id ?? null,
					leave_type_id: kind === 'leave' ? leaveTypeId || null : null,
					corrected_check_in:kind==='attendance_correction'?correctedIn:null,
					corrected_check_out:kind==='attendance_correction'?correctedOut:null,
					reason
				})
			});
			reason = '';
			minutes = undefined;
			amount = undefined;
			targetUserId = '';
			attachment = null;
			showForm = false;
			notice = t('Pengajuan berhasil dikirim.');
			await load();
		} catch (e) { error = e instanceof Error ? e.message : t('Pengajuan gagal dikirim.'); }
		finally { busy = false; }
	}

	async function decide(entry: Entry, decision: string) {
		if (!canDecide(entry.status) || busy) return;
		busy = true; error = ''; notice = '';
		try {
			const stage = entry.status === 'pending_manager' ? 'manager' : 'admin';
			await request('/v1/requests/' + entry.id + '/' + stage + '-decision', { method: 'POST', body: JSON.stringify({ decision, note: notes[entry.id] || null }) });
			notice = t('Keputusan tersimpan.'); await load();
		} catch (e) { error = e instanceof Error ? e.message : t('Keputusan gagal disimpan.'); }
		finally { busy = false; }
	}

	async function cancel(entry: Entry) {
		busy = true; error = '';
		try { await request(`/v1/requests/${entry.id}/cancel`, { method: 'POST' }); await load(); }
		catch(cause) { error = cause instanceof Error ? cause.message : t('Gagal memperbarui status.'); }
		finally { busy = false; }
	}
	async function swapConsent(id:string,accepted:boolean){busy=true;error='';try{await request(`/v1/requests/${id}/swap-consent`,{method:'POST',body:JSON.stringify({accepted})});await load();}catch(cause){error=cause instanceof Error?cause.message:t('Gagal memperbarui status.');}finally{busy=false;}}

	function exportCsv() {
		const header = ['Karyawan', 'Jenis', 'Mulai', 'Selesai', 'Status', 'Alasan'].map(t);
		const rows = filtered.map((item) => [
			`"${(item.full_name ?? session.current?.user.full_name ?? '').replace(/"/g, '""')}"`,
			`"${(kinds[item.kind] ?? item.kind).replace(/"/g, '""')}"`,
			`"${item.starts_on}"`,
			`"${item.ends_on}"`,
			`"${statuses[item.status] ?? item.status}"`,
			`"${item.reason.replace(/\r?\n/g, ' ').replace(/"/g, '""')}"`
		]);
		const csv = [header.join(','), ...rows.map((r) => r.join(','))].join('\n');
		const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
		const url = URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = `${approvals ? 'persetujuan' : 'pengajuan'}.csv`;
		link.click();
		URL.revokeObjectURL(url);
	}

	function formatMoney(cents: number) {
		return new Intl.NumberFormat(localeTag(), { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(cents / 100);
	}

	onMount(load);
</script>

<svelte:head><title>{approvals ? t('Persetujuan') : t('Pengajuan')} · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page requests-page" tabindex="-1">
	<PageHeader
		title={approvals ? t('Persetujuan tim') : t('Pengajuan & izin')}
		description={approvals ? t('Tinjau dan putuskan pengajuan anggota tim.') : t('Cuti, lembur, koreksi absensi, dan reimbursement.')}
		backHref="/app/today"
	>
		{#snippet actions()}
			<div class="header-action-group">
				{#if filtered.length > 0}
					<button type="button" class="quiet csv-btn" onclick={exportCsv}>{t('Ekspor CSV')}</button>
				{/if}
				{#if !approvals}
					<button
						type="button"
						class="action-trigger-btn"
						class:active={showForm}
						onclick={() => (showForm = !showForm)}
						aria-expanded={showForm}
					>
						<Icon name={showForm ? 'close' : 'plus'} size={14} />
						<span>{showForm ? t('Tutup') : t('Pengajuan')}</span>
					</button>
				{/if}
			</div>
		{/snippet}
	</PageHeader>

	{#if error}
		<aside role="alert">{error} <button onclick={load} disabled={busy}>{t('Coba lagi')}</button></aside>
	{/if}
	{#if notice}
		<p class="notice" role="status">{notice}</p>
	{/if}

	<section class="request-kpi-strip" aria-label={t('Ringkasan pengajuan')}>
		<MetricCard label={t('Total')} value={summaryCounts.total} loading={loading || !!error} />
		<MetricCard label={t('Menunggu')} value={summaryCounts.pending} loading={loading || !!error} />
		<MetricCard label={t('Disetujui')} value={summaryCounts.approved} loading={loading || !!error} />
		<MetricCard label={t('Ditolak')} value={summaryCounts.rejected} loading={loading || !!error} />
	</section>
	{#if !approvals&&swapInvitations.length}<section class="panel"><h2>{t('Undangan tukar shift')}</h2>{#each swapInvitations as invitation}<article class="record-card"><h3>{invitation.full_name} · {invitation.date}</h3><p>{invitation.reason}</p><div class="actions"><button disabled={busy} onclick={()=>swapConsent(invitation.id,true)}>{t('Saya menyetujui pertukaran')}</button><button class="quiet" disabled={busy} onclick={()=>swapConsent(invitation.id,false)}>{t('Tolak')}</button></div></article>{/each}</section>{/if}

	<div class:review={approvals} class:form-open={!approvals && showForm} class="form-grid">
		{#if !approvals && showForm}
			<form class="panel form-panel" onsubmit={(e) => { e.preventDefault(); void submit(); }}>
				<div class="section-heading compact">
					<div class="form-title-wrap">
						<span class="form-icon-pill"><Icon name="plus" size={13} /></span>
						<h2>{t('Pengajuan baru')}</h2>
					</div>
					<div class="heading-side">
						<span class="badge">{t('Lampiran opsional')}</span>
						<button
							type="button"
							class="quiet form-close-btn"
							onclick={() => (showForm = false)}
							aria-label={t('Tutup form')}
							title={t('Tutup')}
						>
							<Icon name="close" size={14} />
						</button>
					</div>
				</div>
				<label>{t('Jenis')}
					<select bind:value={kind} onchange={() => {
						if (kind !== 'overtime') minutes = undefined;
						if (!['reimbursement', 'cash_advance'].includes(kind)) amount = undefined;
						if (kind !== 'shift_swap') targetUserId = '';
					}}>
						{#each Object.entries(kinds) as [value, label]}
							<option {value}>{label}</option>
						{/each}
					</select>
				</label>
				{#if kind === 'leave' && leaveBalances.length}
					<label>{t('Jenis cuti')}<select bind:value={leaveTypeId} required><option value="">{t('Pilih jenis cuti')}</option>{#each leaveBalances as balance}<option value={balance.id}>{balance.name} · {balance.days - balance.reserved_days} {t('hari tersedia')}</option>{/each}</select></label>
					<small>{t('Kuota dihitung per hari kalender; pengajuan menunggu ikut mencadangkan saldo.')}</small>
				{/if}
				<div class="date-fields">
					<label>{t('Mulai')}<input type="date" bind:value={starts_on} onchange={() => { if (starts_on && (!ends_on || ends_on < starts_on)) ends_on = starts_on; }} required /></label>
					<label>{t('Selesai')}<input type="date" bind:value={ends_on} min={starts_on} max={['shift_swap','attendance_correction'].includes(kind)?starts_on:undefined} required /></label>
				</div>
				{#if kind==='attendance_correction'}<div class="date-fields"><label>{t('Jam masuk koreksi')}<input type="time" bind:value={correctedIn} required/></label><label>{t('Jam pulang koreksi')}<input type="time" bind:value={correctedOut} required/></label></div>{/if}
				{#if kind === 'overtime'}
					<label>{t('Durasi (menit)')}<input type="number" min="1" step="1" bind:value={minutes} required /></label>
				{/if}
				{#if kind === 'reimbursement' || kind === 'cash_advance'}
					<label>{t('Nominal (Rp)')}<input type="number" min="1" step="1" bind:value={amount} required /></label>
				{/if}
				{#if kind === 'shift_swap'}
					<label>{t('Rekan tukar shift')}
						<select bind:value={targetUserId} required>
							<option value="">{t('Pilih rekan')}</option>
							{#each colleagues as colleague}<option value={colleague.id}>{colleague.full_name}</option>{/each}
						</select>
					</label>
				{/if}
				<label>{t('Alasan')}<textarea rows="3" maxlength="4000" bind:value={reason} placeholder={t('Jelaskan alasan atau rincian biaya…')} required></textarea></label>
				<label>{t('Lampiran berkas / bukti (opsional)')}
					<input type="file" onchange={handleFileSelect} disabled={busy || uploading} />
					{#if uploading}
						<small>{t('Mengunggah berkas…')}</small>
					{:else if attachment}
						<small>{t('Terunggah')}: <a href={attachment.download_url} target="_blank" rel="noreferrer">{attachment.file_name}</a></small>
					{/if}
				</label>
				<div class="form-actions">
					<button type="button" class="quiet" onclick={() => (showForm = false)}>{t('Batal')}</button>
					<button type="submit" disabled={busy || uploading}>{busy ? t('Menyimpan…') : t('Kirim pengajuan')}</button>
				</div>
			</form>
		{/if}

		<section aria-label={approvals ? t('Daftar persetujuan') : t('Riwayat pengajuan')} aria-busy={loading}>
			<div class="filters">
				<label class="filter-search">
					{t('Cari pengajuan')}
					<input type="search" bind:value={search} placeholder={t('Cari alasan, jenis, atau nama…')} />
				</label>
				<label class="filter-status">
					{t('Status')}
					<select aria-label={t('Status')} bind:value={status}>
						<option value="">{t('Semua status')}</option>
						{#each Object.entries(statuses) as [value, label]}
							<option {value}>{label}</option>
						{/each}
					</select>
				</label>
			</div>

			{#if loading}
				<p class="status-msg">{t('Memuat pengajuan…')}</p>
			{:else if !entries.length && !error}
				<div class="panel empty">
					<div class="empty-icon-pill"><Icon name="request" size={22} /></div>
					<h2>{approvals ? t('Tidak ada pengajuan menunggu') : t('Belum ada pengajuan')}</h2>
					<p>{approvals ? t('Pengajuan yang membutuhkan keputusan Anda akan muncul di sini.') : t('Ajukan cuti, lembur, koreksi absensi, atau reimbursement baru.')}</p>
					{#if !approvals}
						<button type="button" class="empty-trigger-btn" onclick={() => (showForm = true)}>
							<Icon name="plus" size={14} />
							<span>{t('Buat Pengajuan Baru')}</span>
						</button>
					{/if}
				</div>
			{:else if !filtered.length && !error}
				<div class="panel empty">
					<h2>{t('Tidak ada hasil')}</h2>
					<p>{t('Coba kata kunci atau status lain.')}</p>
				</div>
			{/if}

			{#each filtered as entry (entry.id)}
				<article class="panel record-card">
					<div class="heading">
						<div>
							<h2>{kinds[entry.kind] ?? entry.kind}</h2>
							{#if entry.full_name}<b class="requester-name">{entry.full_name}</b>{/if}
						</div>
						<span class="badge">{statuses[entry.status] ?? entry.status}</span>
					</div>
					<p class="date">
						{entry.starts_on} — {entry.ends_on}
						{#if entry.minutes} · {t('Durasi')} {formatMinutes(entry.minutes)}{/if}
						{#if entry.amount_cents} · {formatMoney(entry.amount_cents)}{/if}
						{#if entry.target_user_name} · {t('Dengan')} {entry.target_user_name}{/if}
					</p>
					<p class="reason">{entry.reason}</p>
					{#if !approvals&&entry.status==='approved'&&['cash_advance','reimbursement'].includes(entry.kind)}<RequestPayments id={entry.id} kind={entry.kind} owner={session.current?.user.id}/>{/if}
					{#if entry.kind==='attendance_correction'&&entry.details?.corrected_check_in}<p class="date">{t('Koreksi')}: {entry.details.corrected_check_in} — {entry.details.corrected_check_out}</p>{/if}
					{#if entry.kind==='shift_swap'}<p class="date">{t(entry.details?.colleague_consented?'Rekan sudah menyetujui':'Menunggu persetujuan rekan')}</p>{/if}
					{#if entry.attachment_download_url}
						<a class="attachment" href={entry.attachment_download_url} target="_blank" rel="noreferrer">
							<Icon name="file" size={13} /> {entry.attachment_file_name ?? t('Lampiran')}
						</a>
					{/if}
					{#if entry.decision_note || entry.decided_by}
						<p class="decision-meta">
							{#if entry.decided_by}<span>{t('Ditinjau oleh')} <strong>{entry.decided_by}</strong></span>{/if}
							{#if entry.decision_note}<span>{t('Catatan')}: {entry.decision_note}</span>{/if}
						</p>
					{/if}
					{#if approvals && canDecide(entry.status)}
						<label>{t('Catatan keputusan')}
							<textarea rows="2" maxlength="4000" bind:value={notes[entry.id]} placeholder={t('Tambahkan catatan persetujuan/penolakan…')}></textarea>
						</label>
						<div class="actions">
							<button disabled={busy||(entry.kind==='shift_swap'&&!entry.details?.colleague_consented)} onclick={() => decide(entry, 'approved')}>
								{t('Setujui tahap')} {entry.status === 'pending_manager' ? 'manager' : 'admin'}
							</button>
							<button class="reject" disabled={busy} onclick={() => decide(entry, 'rejected')}>{t('Tolak')}</button>
						</div>
					{/if}
					{#if !approvals && ['pending_manager','pending_admin'].includes(entry.status)}<button class="quiet" disabled={busy} onclick={() => cancel(entry)}>{t('Batalkan pengajuan')}</button>{/if}
				</article>
			{/each}
		</section>
	</div>
</main>

<style>
	.requests-page {
		display: grid;
		gap: 12px;
		align-content: start;
	}
	.form-grid { grid-template-columns: minmax(0, 1fr); }
	@media (min-width: 1024px) {
		.form-grid.form-open { grid-template-columns: minmax(280px, .72fr) minmax(0, 1.28fr); }
	}
	.header-action-group {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.action-trigger-btn {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		min-height: 32px !important;
		padding: 4px 10px !important;
		font-size: 11.5px !important;
		font-weight: 700;
		border-radius: var(--radius-sm) !important;
		background: var(--accent-solid) !important;
		color: #ffffff !important;
		border: 1px solid transparent !important;
		box-shadow: none !important;
	}
	.action-trigger-btn:hover {
		background: var(--accent-solid-hover) !important;
	}
	.action-trigger-btn.active {
		background: var(--surface) !important;
		color: var(--ink) !important;
		border-color: var(--line) !important;
	}
	.csv-btn {
		min-height: 32px !important;
		padding: 4px 9px !important;
		font-size: 11.5px !important;
		border-radius: var(--radius-sm) !important;
	}
	.request-kpi-strip {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 6px;
		margin-bottom: 2px;
	}
	.form-panel {
		border: 1px solid #bfcec1 !important;
		background: var(--surface) !important;
		padding: 14px !important;
		border-radius: var(--radius) !important;
		margin-bottom: 4px;
		box-shadow: var(--shadow-soft) !important;
	}
	.form-title-wrap {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.form-title-wrap h2 {
		margin: 0;
		font-size: 14px;
		font-weight: 750;
	}
	.form-icon-pill {
		display: grid;
		place-items: center;
		width: 22px;
		height: 22px;
		border-radius: 50%;
		background: var(--primary-soft);
		color: var(--primary-strong);
	}
	.heading-side {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.form-close-btn {
		width: 26px !important;
		min-height: 26px !important;
		padding: 3px !important;
		border-radius: var(--radius-sm) !important;
		display: grid !important;
		place-items: center;
	}
	.form-actions {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 8px;
		margin-top: 14px;
	}
	.form-actions button {
		min-height: 34px !important;
		padding: 5px 12px !important;
		font-size: 12px !important;
	}
	.date-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
	.date-fields label { min-width: 0; }
	@media (max-width: 480px) {
		.date-fields { grid-template-columns: 1fr; gap: 0; }
	}
	.review { grid-template-columns: 1fr !important; }
	.compact { margin-bottom: 6px; }
	.record-card { margin-bottom: 8px; padding: 12px 14px !important; border-radius: var(--radius) !important; }
	.heading { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 3px; }
	.heading h2 { margin: 0; font-size: 14px; font-weight: 750; }
	.requester-name { font-size: 11.5px; color: var(--primary-strong); }
	.date { font-size: 11px; color: var(--muted); font-variant-numeric: tabular-nums; }
	.actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
	.actions .reject { background: #883d35; }
	.actions button { min-height: 32px !important; padding: 4px 10px !important; font-size: 11.5px !important; }
	.reason { margin-top: 5px; white-space: pre-wrap; overflow-wrap: anywhere; font-size: 12px; }
	.decision-meta { display: grid; gap: 2px; margin-top: 6px; padding-top: 6px; border-top: 1px solid var(--line); color: var(--muted); font-size: 11px; }
	.attachment { display: inline-flex; align-items: center; gap: 5px; margin-top: 5px; color: var(--primary-strong); font-size: 11.5px; font-weight: 700; }
	aside button { margin-left: 8px; }
	.filters {
		display: grid;
		grid-template-columns: minmax(0, 1fr) 130px;
		gap: 8px;
		margin-bottom: 8px;
		align-items: center;
	}
	.filters label {
		margin: 0;
		display: grid;
		gap: 3px;
		font-size: 11px;
		font-weight: 650;
		color: var(--muted);
	}
	.filters input, .filters select {
		min-height: 34px !important;
		padding: 5px 9px !important;
		font-size: 12px !important;
	}
	.status-msg {
		color: var(--muted);
		font-size: 12.5px;
		margin: 12px 0;
	}
	.empty-icon-pill {
		display: grid;
		place-items: center;
		width: 42px;
		height: 42px;
		margin: 0 auto 8px;
		border-radius: 50%;
		background: var(--surface-alt);
		color: var(--muted);
	}
	.empty h2 {
		font-size: 14px;
		font-weight: 750;
		margin-bottom: 4px;
	}
	.empty p {
		font-size: 12px;
		color: var(--muted);
		max-width: 44ch;
		margin: 0 auto;
	}
	.empty-trigger-btn {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		margin-top: 12px;
		min-height: 34px !important;
		padding: 5px 12px !important;
		font-size: 12px !important;
		border-radius: var(--radius-sm) !important;
	}
</style>
