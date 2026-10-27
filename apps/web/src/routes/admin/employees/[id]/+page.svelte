<script lang="ts">
    import { untrack } from 'svelte';
    import { page } from '$app/state';
    import { request, employees, type Employee } from '../../../../lib/api';
    import { workSession } from '../../../../lib/session';
    import Icon from '../../../../lib/Icon.svelte';
    import { uiText as t } from '../../../../lib/i18n';
    type Details = Employee & { employment_status: string; manager_user_id: string | null; phone: string; address: string; team: string; job_title: string; started_on: string | null; version: number };
    const session = workSession();
    const statuses: Record<string,string> = { active: 'Aktif', inactive: 'Nonaktif', resigned: 'Resign' };
    let detail = $state<Details | null>(null), managers = $state<Employee[]>([]);
    let loading = $state(true), busy = $state(false), error = $state(''), notice = $state('');
    let full_name = $state(''), employee_code = $state(''), manager_user_id = $state(''), phone = $state(''), address = $state(''), team = $state(''), job_title = $state(''), started_on = $state('');
    let nextStatus = $state('active'), confirmed = $state(false);
    let canEdit = $derived(detail?.role !== 'owner' && !!session.current?.capabilities.includes('admin.employees.manage'));
    let canStatus = $derived(detail?.role !== 'owner' && detail?.id !== session.current?.user.id && !!session.current?.capabilities.includes('admin.employees.manage_status'));
    let sequence = 0;
    async function load(id: string) {
        const attempt = ++sequence;
        loading = true; error = ''; detail = null;
        try {
            const result = await request<Details>('/v1/admin/employees/' + encodeURIComponent(id));
            if (attempt !== sequence) return;
            detail = result; full_name = result.full_name; employee_code = result.employee_code ?? '';
            manager_user_id = result.manager_user_id ?? ''; phone = result.phone; address = result.address;
            team = result.team; job_title = result.job_title; started_on = result.started_on ?? '';
            nextStatus = result.employment_status; confirmed = false;
            managers = await employees();
        } catch (cause) { if (attempt === sequence) error = cause instanceof Error ? cause.message : t('Data karyawan gagal dimuat.'); }
        finally { if (attempt === sequence) loading = false; }
    }
    $effect(() => { const id = page.params.id; if (id) untrack(() => { void load(id); }); });
    async function save() {
        if (!detail || !canEdit || busy) return;
        busy = true; error = ''; notice = '';
        try {
            await request('/v1/admin/employees/' + detail.id, { method: 'POST', body: JSON.stringify({ full_name, employee_code, manager_user_id: manager_user_id || null, phone, address, team, job_title, started_on: started_on || null, version: detail.version }) });
            const id = detail.id; await load(id); await session.refresh(); notice = t('Data karyawan tersimpan.');
        } catch (cause) { error = cause instanceof Error ? cause.message : t('Gagal menyimpan karyawan.'); }
        finally { busy = false; }
    }
    async function changeStatus() {
        if (!detail || !canStatus || !confirmed || busy || nextStatus === detail.employment_status) return;
        busy = true; error = ''; notice = '';
        try {
            await request('/v1/admin/employees/' + detail.id + '/status', { method: 'POST', body: JSON.stringify({ status: nextStatus, version: detail.version }) });
            const id = detail.id; await load(id); notice = t('Status kerja diperbarui.');
        } catch (cause) { error = cause instanceof Error ? cause.message : t('Gagal mengubah status kerja.'); }
        finally { busy = false; }
    }
</script>

<svelte:head><title>{t('Detail karyawan')} · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page" tabindex="-1">
    <header class="page-heading"><div><a class="back" href="/admin/employees"><Icon name="back" size={16} />{t('Direktori karyawan')}</a><h1>{detail?.full_name ?? t('Detail karyawan')}</h1></div>{#if detail}<span class="badge">{t(statuses[detail.employment_status])}</span>{/if}</header>
    {#if error}<p role="alert">{error} <button class="quiet" onclick={() => load(page.params.id!)} disabled={busy}>{t('Muat ulang data')}</button></p>{/if}
    {#if notice}<p class="notice" role="status">{notice}</p>{/if}
    {#if loading}<p role="status">{t('Memuat karyawan…')}</p>{:else if detail}
        <div class="detail-grid">
            <form class="panel" onsubmit={event => { event.preventDefault(); void save(); }}>
                <h2>{t('Identitas & penempatan')}</h2>
                <p class="muted">{detail.email} · {detail.role}</p>
                <fieldset disabled={!canEdit || busy}>
                    <label>{t('Nama lengkap')}<input bind:value={full_name} required maxlength="200" /></label>
                    <label>{t('Kode karyawan')}<input bind:value={employee_code} required maxlength="80" /></label>
                    <div class="pair"><label>{t('Tim / divisi')}<input bind:value={team} maxlength="100" /></label><label>{t('Jabatan')}<input bind:value={job_title} maxlength="100" /></label></div>
                    <label>{t('Atasan langsung')}<select bind:value={manager_user_id}><option value="">{t('Belum ditetapkan')}</option>{#if manager_user_id && !managers.some(m => m.id === manager_user_id)}<option value={manager_user_id}>{t('Atasan nonaktif — pilih pengganti')}</option>{/if}{#each managers.filter(m => m.id !== detail!.id) as manager}<option value={manager.id}>{manager.full_name}</option>{/each}</select></label>
                    <label>{t('Tanggal mulai')}<input type="date" bind:value={started_on} /></label>
                    <label>{t('Telepon')}<input type="tel" bind:value={phone} maxlength="64" /></label>
                    <label>{t('Alamat')}<textarea bind:value={address} rows="3" maxlength="500"></textarea></label>
                </fieldset>
                {#if canEdit}<button disabled={busy}>{busy ? t('Menyimpan…') : t('Simpan karyawan')}</button>{:else}<p class="muted">{t('Informasi hanya dapat dilihat. Akun owner dilindungi.')}</p>{/if}
            </form>
            <aside class="panel"><h2>{t('Status kerja')}</h2>
                {#if canStatus}<form onsubmit={event => { event.preventDefault(); void changeStatus(); }}>
                    <label>{t('Status')}<select bind:value={nextStatus} onchange={() => confirmed = false} disabled={busy}>{#each Object.entries(statuses) as [value,label]}<option {value}>{t(label)}</option>{/each}</select></label>
                    <p class="muted">{t('Nonaktif/resign menghentikan akses perusahaan ini. Setelah aktif kembali, karyawan harus login ulang. Riwayat tetap disimpan.')}</p>
                    {#if nextStatus !== detail.employment_status}<label class="confirm"><input type="checkbox" bind:checked={confirmed} disabled={busy} />{t('Saya konfirmasi perubahan status.')}</label>{/if}
                    <button disabled={busy || !confirmed || nextStatus === detail.employment_status}>{t('Ubah status kerja')}</button>
                </form>{:else}<p class="muted">{t(statuses[detail.employment_status])}. {t('Perubahan status memerlukan izin terpisah; status sendiri dan owner dilindungi.')}</p>{/if}
                <p class="muted">{t('Email dan kata sandi tidak diubah melalui form karyawan.')}</p>
            </aside>
        </div>
    {/if}
</main>
<style>
    .back{display:inline-flex;align-items:center;gap:6px;margin-bottom:10px;font-size:12px;text-decoration:none}.detail-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(240px,1fr);gap:14px;align-items:start}fieldset{min-width:0;border:0;padding:0;margin:12px 0}.pair{display:grid;grid-template-columns:1fr 1fr;gap:10px}.muted{font-size:12px;line-height:1.6;overflow-wrap:anywhere}.confirm{display:flex;align-items:center;gap:8px}.confirm input{width:16px;height:16px;min-height:16px;margin:0}h2{margin-bottom:12px}@media(max-width:900px){.detail-grid{grid-template-columns:1fr}}@media(max-width:400px){.pair{grid-template-columns:1fr}}
</style>
