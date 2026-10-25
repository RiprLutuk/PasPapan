<script lang="ts">
    import { onMount } from 'svelte';
    import { request } from '../../../lib/api';
    import { permissionLabels } from '../../../lib/access';

    type Role = { id: string; name: string; permissions: string[] };
    type Member = { id: string; name: string; base_role: string; role_id: string | null; grants: string[]; denies: string[]; effective: string[]; protected: boolean };
    type AccessConfig = { name?: string; role_id?: string | null; permissions?: string[]; grants?: string[]; denies?: string[] };
    type AuditEvent = { id: string; action: string; actor: string; subject_id: string; occurred_at: string; changes: { before: AccessConfig | null; after: AccessConfig | null } };
    type Overview = { catalog: string[]; roles: Role[]; members: Member[]; events: AuditEvent[] };
    const actionLabels: Record<string,string> = { 'access.role.saved': 'Role disimpan', 'access.member.saved': 'Akses anggota diubah', 'access.role.deleted': 'Role dihapus' };
    function summarize(config: AccessConfig | null) {
        if (!config) return 'Tidak ada konfigurasi';
        const names = (list: string[]) => list.map(p => permissionLabels[p] ?? p).join(', ') || 'Tidak ada';
        if (config.permissions) return `${config.name ?? 'Role'} · ${names(config.permissions)}`;
        return `${config.role_id ? data?.roles.find(r => r.id === config.role_id)?.name ?? 'Role custom' : 'Role bawaan'} · Izinkan: ${names(config.grants ?? [])} · Tolak: ${names(config.denies ?? [])}`;
    }
    let data = $state<Overview | null>(null), loading = $state(true), busy = $state(false);
    let error = $state(''), notice = $state('');
    let confirmDelete = $state(false);
    let roleId = $state(''), roleName = $state(''), permissions = $state<string[]>([]);
    let memberId = $state(''), assignedRole = $state(''), overrides = $state<Record<string,string>>({});
    let selected = $derived(data?.members.find(member => member.id === memberId));

    async function load() {
        loading = true; error = '';
        try { data = await request<Overview>('/v1/admin/access'); }
        catch (cause) { data = null; error = cause instanceof Error ? cause.message : 'Gagal memuat akses.'; }
        finally { loading = false; }
    }
    function editRole(id: string) {
        const role = data?.roles.find(item => item.id === id);
        roleId = id; roleName = role?.name ?? ''; permissions = [...(role?.permissions ?? [])]; notice = ''; confirmDelete = false;
    }
    function editMember(id: string) {
        memberId = id;
        const member = data?.members.find(item => item.id === id);
        assignedRole = member?.role_id ?? '';
        overrides = Object.fromEntries((data?.catalog ?? []).map(p => [p, member?.denies.includes(p) ? 'deny' : member?.grants.includes(p) ? 'grant' : 'inherit']));
        notice = '';
    }
    async function saveRole() {
        if (busy) return;
        busy = true; error = ''; notice = '';
        try {
            const role = await request<Role>('/v1/admin/access/roles', { method: 'POST', body: JSON.stringify({ id: roleId || null, name: roleName, permissions }) });
            await load(); editRole(role.id); notice = 'Role tersimpan. Berlaku pada permintaan berikutnya.';
        } catch (cause) { error = cause instanceof Error ? cause.message : 'Gagal menyimpan role.'; }
        finally { busy = false; }
    }
    async function saveMember() {
        if (!selected || selected.protected || busy) return;
        busy = true; error = ''; notice = '';
        try {
            await request('/v1/admin/access/members/' + memberId, { method: 'POST', body: JSON.stringify({ role_id: assignedRole || null, grants: data!.catalog.filter(p => overrides[p] === 'grant'), denies: data!.catalog.filter(p => overrides[p] === 'deny') }) });
            await load(); editMember(memberId); notice = 'Akses anggota tersimpan. Sesi aktif mengikuti perubahan ini.';
        } catch (cause) { error = cause instanceof Error ? cause.message : 'Gagal menyimpan akses.'; }
        finally { busy = false; }
    }
    async function deleteRole() {
        if (!roleId || busy || !confirmDelete) return;
        busy = true; error = ''; notice = '';
        try {
            await request('/v1/admin/access/roles/' + roleId + '/delete', { method: 'POST' });
            await load(); editRole(''); notice = 'Role dihapus. Perubahan tercatat di audit.';
        } catch (cause) { error = cause instanceof Error ? cause.message : 'Gagal menghapus role.'; }
        finally { busy = false; }
    }
    onMount(load);
</script>

<svelte:head><title>Hak akses · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page" tabindex="-1">
    <header class="page-heading"><div><p class="eyebrow">KEAMANAN</p><h1>Hak akses</h1><p>Role perusahaan dan pengecualian anggota.</p></div><span class="badge">Owner</span></header>
    {#if error}<p role="alert">{error} <button class="quiet" onclick={load} disabled={busy}>Muat ulang</button></p>{/if}
    {#if notice}<p class="notice" role="status">{notice}</p>{/if}
    {#if loading}<p role="status">Memuat hak akses…</p>
    {:else if data}
        <div class="access-grid">
            <form class="panel" onsubmit={event => { event.preventDefault(); void saveRole(); }}>
                <h2>Role perusahaan</h2>
                <label>Role<select value={roleId} onchange={event => editRole(event.currentTarget.value)} disabled={busy}><option value="">Buat role baru</option>{#each data.roles as role}<option value={role.id}>{role.name}</option>{/each}</select></label>
                <label>Nama role<input bind:value={roleName} maxlength="80" required disabled={busy} placeholder="Contoh: HR read-only" /></label>
                <fieldset disabled={busy}><legend>Permission role</legend>
                    {#each data.catalog as permission}<label class="permission"><input type="checkbox" bind:group={permissions} value={permission} /><span>{permissionLabels[permission] ?? permission}</span></label>{/each}
                </fieldset>
                <button disabled={busy}>{busy ? 'Menyimpan…' : 'Simpan role'}</button>
                {#if roleId}<button class="quiet" type="button" disabled={busy} onclick={() => confirmDelete = !confirmDelete}>Hapus role</button>{/if}
                {#if confirmDelete}<p class="help">Hapus “{roleName}”? Role yang masih dipakai anggota tidak bisa dihapus.</p><button class="quiet" type="button" disabled={busy} onclick={deleteRole}>Ya, hapus role</button>{/if}
                <p class="help">Role custom menggantikan permission bawaan. Role kosong berarti tanpa akses admin.</p>
            </form>
            <form class="panel" onsubmit={event => { event.preventDefault(); void saveMember(); }}>
                <h2>Akses anggota</h2>
                <label>Anggota<select value={memberId} onchange={event => editMember(event.currentTarget.value)} disabled={busy}><option value="">Pilih anggota</option>{#each data.members as member}<option value={member.id}>{member.name} · {member.base_role}</option>{/each}</select></label>
                {#if selected}
                    {#if selected.protected}<p class="help">Akses owner dilindungi dan tidak dapat diubah di sini.</p>
                    {:else}
                        <label>Role akses<select bind:value={assignedRole} disabled={busy}><option value="">Bawaan ({selected.base_role})</option>{#each data.roles as role}<option value={role.id}>{role.name}</option>{/each}</select></label>
                        <fieldset disabled={busy}><legend>Pengecualian anggota</legend>
                            {#each data.catalog as permission}<label class="override"><span>{permissionLabels[permission] ?? permission}</span><select aria-label={permissionLabels[permission] ?? permission} bind:value={overrides[permission]}><option value="inherit">Ikuti role</option><option value="grant">Izinkan</option><option value="deny">Tolak</option></select></label>{/each}
                        </fieldset>
                        <button disabled={busy}>Simpan akses</button>
                        <p class="help">Tolak mengalahkan izin role. Pilih role bawaan dan “Ikuti role” untuk mengembalikan akses awal.</p>
                    {/if}
                    <details class="effective"><summary>Akses tersimpan · {selected.effective.length}</summary><ul>{#each selected.effective as permission}<li>{permissionLabels[permission] ?? permission}</li>{/each}</ul></details>
                {:else}<p class="help">Pilih anggota untuk meninjau akses efektifnya.</p>{/if}
            </form>
        </div>
        <p class="help">Permission hanya mencakup modul rewrite yang sudah terhubung. Persetujuan tetap melarang self-approval; tanpa jangkauan perusahaan, hanya bawahan langsung.</p>
        <section class="panel audit" aria-label="Riwayat perubahan akses">
            <h2>Riwayat perubahan <span class="muted">· 50 terbaru</span></h2>
            {#each data.events as event (event.id)}
                <details>
                    <summary><span><strong>{actionLabels[event.action] ?? event.action}</strong> · {data.members.find(m => m.id === event.subject_id)?.name ?? event.changes.after?.name ?? event.changes.before?.name ?? 'Role'}<small>{event.actor}</small></span><time datetime={event.occurred_at}>{new Date(event.occurred_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</time></summary>
                    <dl><dt>Sebelum</dt><dd>{summarize(event.changes.before)}</dd><dt>Sesudah</dt><dd>{summarize(event.changes.after)}</dd></dl>
                </details>
            {:else}<p class="help">Belum ada perubahan akses.</p>{/each}
        </section>
    {/if}
</main>

<style>
    .access-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:12px;align-items:start}
    h2{margin-bottom:14px}fieldset{border:0;padding:0;margin:0 0 14px;min-width:0}legend{font-size:12px;font-weight:700;margin-bottom:8px}
    .permission{display:flex;align-items:center;gap:8px;margin:0;padding:8px 0;border-bottom:1px solid var(--line);font-size:12px}
    .permission input{width:16px;height:16px;min-height:16px;margin:0;flex-shrink:0;accent-color:var(--primary)}
    .override{display:grid;grid-template-columns:minmax(0,1fr) 110px;gap:12px;align-items:center;margin:0;padding:5px 0;border-bottom:1px solid var(--line);font-size:12px}
    .override select{margin:0}.help{font-size:12px;color:var(--muted);line-height:1.6;margin:12px 0 0}.effective{margin-top:14px;border-top:1px solid var(--line);padding-top:10px}.effective summary{cursor:pointer;font-size:12px}.effective ul{padding-left:18px;font-size:12px;line-height:1.8}
    .audit{margin-top:14px}.audit details{border-top:1px solid var(--line);font-size:12px}.audit summary{display:flex;justify-content:space-between;gap:12px;padding:12px 0;cursor:pointer;flex-wrap:wrap}.audit small{display:block;color:var(--muted);margin-top:4px}.audit time{font-size:11px;color:var(--muted)}.audit dl{margin:0 0 12px;line-height:1.7}.audit dt{font-weight:700}.audit dd{margin:0 0 8px;overflow-wrap:anywhere;color:var(--muted)}
    @media(max-width:1000px){.access-grid{grid-template-columns:1fr}}@media(max-width:760px){.permission{min-height:44px}.override{grid-template-columns:minmax(0,1fr) 120px}}
</style>
