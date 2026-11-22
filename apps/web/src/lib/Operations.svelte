<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	import { PageHeader, RefreshButton } from './ui';

	type Check = { id: string; label: string; completed: boolean };
	type Task = { id: string; title: string; priority: string; due_on: string | null; status: 'todo'|'in_progress'|'done'; version: number; assignee_name: string | null; checklist: Check[] };
	type Project = { id: string; code: string; name: string; description: string; status: string; client_name: string | null; branch_name: string | null; manager_name: string | null; tasks: Task[] };
	let projects = $state<Project[]>([]);
	let loading = $state(true), error = $state(''), saving = $state(false);
	let projectName = $state(''), projectDescription = $state('');
	let taskProject = $state(''), taskTitle = $state(''), taskPriority = $state('normal'), taskDue = $state(''), checklist = $state('');
	let clientName = $state(''), clientContact = $state(''), clientPhone = $state('');
	let branchName = $state(''), branchKind = $state('branch'), branchAddress = $state('');
	let visitProject = $state(''), visitNotes = $state(''), visitAddress = $state(''), visitFile = $state<File | null>(null);

	async function load() { loading = true; error = ''; try { projects = await request<Project[]>('/v1/admin/operations'); taskProject ||= projects[0]?.id ?? ''; visitProject ||= projects[0]?.id ?? ''; } catch (cause) { error = cause instanceof Error ? cause.message : 'Data operasional tidak dapat dimuat.'; } finally { loading = false; } }
	async function submit(path: string, body: unknown, clear: () => void) { saving = true; error = ''; try { await request(path,{method:'POST',body:JSON.stringify(body)}); clear(); await load(); } catch (cause) { error = cause instanceof Error ? cause.message : 'Perubahan tidak dapat disimpan.'; } finally { saving = false; } }
	function createProject() { return submit('/v1/admin/operations',{name:projectName,description:projectDescription},()=>{projectName='';projectDescription='';}); }
	function createTask() { return submit('/v1/admin/operations/tasks',{project_id:taskProject,title:taskTitle,priority:taskPriority,due_on:taskDue||null,checklist:checklist.split('\n').map(value=>value.trim()).filter(Boolean)},()=>{taskTitle='';taskDue='';checklist='';}); }
	function createClient() { return submit('/v1/admin/operations/clients',{name:clientName,contact_name:clientContact,contact_phone:clientPhone},()=>{clientName='';clientContact='';clientPhone='';}); }
	function createBranch() { return submit('/v1/admin/operations/branches',{name:branchName,kind:branchKind,address:branchAddress},()=>{branchName='';branchAddress='';}); }
	async function createVisit() {
		saving = true; error = '';
		try {
			let file_id: string | null = null;
			if (visitFile) {
				const data_base64 = await new Promise<string>((resolve, reject) => { const reader = new FileReader(); reader.onload = () => resolve(String(reader.result)); reader.onerror = reject; reader.readAsDataURL(visitFile!); });
				const uploaded = await request<{ id: string }>('/v1/files',{method:'POST',body:JSON.stringify({file_name:visitFile.name,content_type:visitFile.type||'application/octet-stream',data_base64})});
				file_id = uploaded.id;
			}
			await request('/v1/admin/operations/visits',{method:'POST',body:JSON.stringify({project_id:visitProject,visited_at:new Date().toISOString(),address:visitAddress,notes:visitNotes,file_id})});
			visitNotes=''; visitAddress=''; visitFile=null; await load();
		} catch (cause) { error = cause instanceof Error ? cause.message : 'Kunjungan tidak dapat disimpan.'; }
		finally { saving = false; }
	}
	async function setStatus(task: Task, status: Task['status']) { await submit(`/v1/admin/operations/tasks/${task.id}/status`,{status,version:task.version},()=>{}); }
	async function toggle(task: Task, item: Check) { await submit(`/v1/admin/operations/tasks/${task.id}/checklist/${item.id}`,{},()=>{}); }
	onMount(load);
</script>

<svelte:head><title>Operations · PasPapan</title></svelte:head>
<main class="workspace-page" id="main-content" tabindex="-1">
	<PageHeader title="Operations" description="Proyek, aktivitas, client, dan lokasi kerja dalam satu workspace." backHref="/admin/dashboard" />
	{#if error}<p class="alert" role="alert">{error} <RefreshButton onclick={load} label="Coba lagi" /></p>{/if}
	<section class="grid forms" aria-busy={saving}>
		<form class="panel" novalidate onsubmit={(event)=>{event.preventDefault(); createProject();}}><h2>Proyek baru</h2><input aria-label="Nama proyek" bind:value={projectName} placeholder="Nama proyek" required maxlength="180"/><textarea aria-label="Deskripsi proyek" bind:value={projectDescription} placeholder="Deskripsi singkat (opsional)" maxlength="1500"></textarea><button disabled={saving}>Buat proyek</button></form>
		<form class="panel" novalidate onsubmit={(event)=>{event.preventDefault(); createTask();}}><h2>Task baru</h2><select aria-label="Proyek" bind:value={taskProject} required>{#each projects as project}<option value={project.id}>{project.name}</option>{/each}</select><input aria-label="Judul task" bind:value={taskTitle} placeholder="Judul task" required maxlength="180"/><div class="inline"><select aria-label="Prioritas" bind:value={taskPriority}><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option></select><input aria-label="Jatuh tempo" bind:value={taskDue} type="date"/></div><textarea aria-label="Checklist" bind:value={checklist} placeholder="Checklist, satu baris per item"></textarea><button disabled={saving || !taskProject}>Buat task</button></form>
		<details class="panel"><summary>Tambah client</summary><form novalidate onsubmit={(event)=>{event.preventDefault();createClient();}}><input bind:value={clientName} placeholder="Nama client" required maxlength="180"/><input bind:value={clientContact} placeholder="Kontak" maxlength="160"/><input bind:value={clientPhone} placeholder="Telepon" maxlength="60"/><button disabled={saving}>Simpan client</button></form></details>
		<details class="panel"><summary>Tambah lokasi</summary><form novalidate onsubmit={(event)=>{event.preventDefault();createBranch();}}><input bind:value={branchName} placeholder="Nama lokasi" required maxlength="160"/><select bind:value={branchKind}><option value="branch">Cabang</option><option value="store">Toko</option><option value="office">Kantor</option><option value="warehouse">Gudang</option><option value="site">Site</option></select><textarea bind:value={branchAddress} placeholder="Alamat (opsional)"></textarea><button disabled={saving}>Simpan lokasi</button></form></details>
		<details class="panel"><summary>Catat kunjungan lapangan</summary><form novalidate onsubmit={(event)=>{event.preventDefault();createVisit();}}><select bind:value={visitProject} required>{#each projects as project}<option value={project.id}>{project.name}</option>{/each}</select><input bind:value={visitAddress} placeholder="Alamat (opsional)" maxlength="1000"/><textarea bind:value={visitNotes} placeholder="Catatan kunjungan" maxlength="3000"></textarea><input aria-label="Foto atau lampiran kunjungan" type="file" accept="image/*,.pdf" onchange={(event)=>visitFile=(event.currentTarget as HTMLInputElement).files?.[0] ?? null}/><button disabled={saving || !visitProject}>Simpan kunjungan</button></form></details>
	</section>
	<section class="panel projects" aria-busy={loading}>
		<header><h2>Daftar proyek</h2><RefreshButton onclick={load} label="Muat ulang" /></header>
		{#if loading}<p>Memuat workspace…</p>{:else if !projects.length}<p class="muted">Belum ada proyek. Buat proyek pertama di atas.</p>{:else}{#each projects as project (project.id)}
			<article><header><div><strong>{project.name}</strong><small>{project.code} · {project.client_name ?? 'Tanpa client'} · {project.branch_name ?? 'Tanpa lokasi'}</small></div><span>{project.tasks.length} task</span></header>{#if project.description}<p>{project.description}</p>{/if}
				{#if project.tasks.length}<ul>{#each project.tasks as task (task.id)}<li><div><strong>{task.title}</strong><small>{task.priority} · {task.due_on ?? 'tanpa tenggat'} · {task.assignee_name ?? 'belum ditugaskan'}</small>{#if task.checklist.length}<div class="checks">{#each task.checklist as item (item.id)}<button class:done={item.completed} onclick={()=>toggle(task,item)} aria-pressed={item.completed}>{item.completed ? '✓' : '○'} {item.label}</button>{/each}</div>{/if}</div><select aria-label={`Status ${task.title}`} value={task.status} onchange={(event)=>setStatus(task,(event.currentTarget as HTMLSelectElement).value as Task['status'])}><option value="todo">To do</option><option value="in_progress">Berjalan</option><option value="done">Selesai</option></select></li>{/each}</ul>{:else}<p class="muted">Belum ada task.</p>{/if}
			</article>
		{/each}{/if}
	</section>
</main>

<style>
	.workspace-page{display:grid;gap:12px;align-content:start}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.panel{padding:16px;border-radius:var(--radius)}h2{font-size:14px;margin:0 0 10px}.panel form{display:grid;gap:8px}.panel input,.panel textarea,.panel select{width:100%;box-sizing:border-box}.panel textarea{min-height:64px;resize:vertical}.inline{display:grid;grid-template-columns:1fr 1fr;gap:8px}.projects>header,article>header,li{display:flex;align-items:center;justify-content:space-between;gap:10px}.projects>header{margin-bottom:8px}article{border-top:1px solid var(--line);padding:13px 0}article:first-of-type{border-top:0}small,.muted{display:block;color:var(--muted);font-size:11px;margin-top:3px}article p{font-size:12px;color:var(--muted)}ul{list-style:none;padding:0;margin:10px 0 0;display:grid;gap:6px}li{background:var(--surface-alt);border-radius:var(--radius-sm);padding:9px;font-size:12px}li select{max-width:110px}.checks{display:flex;flex-wrap:wrap;gap:4px;margin-top:7px}.checks button{font-size:10px;padding:3px 6px}.checks button.done{opacity:.62;text-decoration:line-through}.alert{margin:0;color:var(--danger);font-size:12px}@media(max-width:760px){.grid{grid-template-columns:1fr}.inline{grid-template-columns:1fr}li{align-items:flex-start;flex-direction:column}li select{max-width:none;width:100%}}
</style>
