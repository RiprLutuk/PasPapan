<script lang="ts">
 import { onMount, tick } from 'svelte';
 import { request } from './api';
 import { uiText as t } from './i18n';
 import { workSession } from './session';
 import { AlertBanner } from './ui';
 import Icon from './Icon.svelte';
 import { documentVariables, previewDocumentTemplate } from './document-template';

 export type Template = { id: string; title: string; body: string | null; active: boolean; version: number };
 let { admin=false, onupdated }: { admin?: boolean; onupdated: (templates: Template[]) => void } = $props();
 const session = workSession();
 let entries = $state<Template[]>([]);
 let id = $state<string|null>(null), version = $state<number|null>(null);
 let title = $state(''), body = $state(''), active = $state(true);
 let error = $state(''), notice = $state(''), busy = $state(false), loading = $state(true);
 let bodyInput = $state<HTMLTextAreaElement>();
 let preview = $derived(previewDocumentTemplate(body, {
  employee_name: 'Budi Santoso', employee_code: 'EMP-001',
  company_name: session.current?.company.name ?? 'PT Contoh',
  date: new Intl.DateTimeFormat('sv-SE', { year:'numeric', month:'2-digit', day:'2-digit' }).format(new Date()),
  division: t('Operasional'), job_title: t('Staf operasional')
 }));
 async function load() {
  loading = true;
  try { entries = await request<Template[]>('/v1/hr-document-templates'); onupdated(entries); }
  catch (cause) { error = cause instanceof Error ? cause.message : t('Data tidak dapat dimuat.'); }
  finally { loading = false; }
 }
 function reset() { id=null; version=null; title=''; body=''; active=true; error=''; notice=''; }
 function edit(entry: Template) {
  id=entry.id; version=entry.version; title=entry.title; body=entry.body??''; active=entry.active; error=''; notice='';
 }
 async function insertVariable(key: string) {
  const token = `{{${key}}}`;
  const start = bodyInput?.selectionStart ?? body.length;
  const end = bodyInput?.selectionEnd ?? start;
  if (body.length - (end-start) + token.length > 20000) { error=t('Isi dokumen maksimal 20.000 karakter.'); return; }
  body = body.slice(0,start) + token + body.slice(end);
  await tick(); bodyInput?.focus(); bodyInput?.setSelectionRange(start+token.length,start+token.length);
 }
 async function save() {
  if (busy) return;
  busy=true; error=''; notice='';
  try {
   await request('/v1/admin/hr-document-templates', { method:'POST', body:JSON.stringify({id,version,title,body,active}) });
   reset(); notice=t('Template berhasil disimpan.'); await load();
  } catch (cause) { error = cause instanceof Error ? cause.message : t('Gagal menyimpan data.'); }
  finally { busy=false; }
 }
 onMount(load);
</script>

{#if admin}
 <details class="panel templates">
  <summary><span class="summary-title"><Icon name="file" size={17}/>{t('Template dokumen')}<small>{entries.length}</small></span><span class="expand-icon"><Icon name="chevron" size={16}/></span></summary>
  <div class="template-content">
   <AlertBanner message={error}/><AlertBanner variant="success" message={notice}/>
   {#if loading}<p class="muted" role="status">{t('Memuat…')}</p>{/if}
   {#if entries.length}
    <nav class="template-list" aria-label={t('Template tersimpan')}>
     {#each entries as entry(entry.id)}<button type="button" class="quiet template-item" class:selected={entry.id===id} aria-pressed={entry.id===id} disabled={busy} onclick={()=>edit(entry)}><span>{entry.title}</span><small>v{entry.version}</small>{#if !entry.active}<small>{t('Nonaktif')}</small>{/if}</button>{/each}
    </nav>
   {/if}
   <form class="editor-form editor-form--embedded editor-form--wide" novalidate onsubmit={event=>{event.preventDefault();void save();}} aria-busy={busy}>
    <div class="editor-heading"><h2>{t(id?'Edit template':'Template baru')}</h2>{#if id}<button type="button" class="quiet new-template" disabled={busy} onclick={reset}><Icon name="plus" size={14}/>{t('Template baru')}</button>{/if}</div>
    <div class="editor-grid">
     <div class="editor-fields">
      <label class="field" for="template-title">{t('Judul')}<input id="template-title" bind:value={title} maxlength="200" placeholder={t('Contoh: Surat keterangan kerja')} disabled={busy||loading} required/></label>
      <label class="field" for="template-body">{t('Isi dokumen')}<textarea id="template-body" bind:this={bodyInput} bind:value={body} data-template-variables rows="7" maxlength="20000" placeholder={t('Tulis isi surat, lalu sisipkan data otomatis di bawah.')} aria-describedby="template-variable-help" disabled={busy||loading} required></textarea></label>
      <div class="variable-tools"><span class="field-caption">{t('Sisipkan data otomatis')}</span><p id="template-variable-help">{t('Klik untuk menyisipkan. Data diisi dari profil karyawan saat pengajuan dibuat.')}</p><div class="variable-chips">{#each documentVariables as variable}<button type="button" class="quiet variable-chip" title={`{{${variable.key}}}`} disabled={busy||loading} onclick={()=>insertVariable(variable.key)}><Icon name="plus" size={12}/>{t(variable.label)}</button>{/each}</div></div>
     </div>
     <aside class="template-preview" aria-labelledby="template-preview-heading"><div class="preview-heading"><Icon name="eye" size={15}/><h3 id="template-preview-heading">{t('Pratinjau')}</h3></div><p class="preview-note">{t('Menggunakan data contoh. Hasil surat mengikuti data karyawan yang mengajukan.')}</p>{#if body.trim()}<div class="preview-paper"><h4>{title||t('Judul dokumen')}</h4><p>{preview}</p></div>{:else}<div class="preview-empty"><Icon name="file" size={25}/><p>{t('Tulis isi surat untuk melihat pratinjau.')}</p></div>{/if}</aside>
    </div>
    <footer class="editor-footer"><label class="active-toggle"><input type="checkbox" bind:checked={active} disabled={busy||loading}/><span>{t('Template aktif')}</span></label><button class="save-template" disabled={busy||loading} type="submit"><Icon name="check" size={15}/>{t(busy?'Menyimpan…':'Simpan template')}</button></footer>
   </form>
  </div>
 </details>
{/if}

<style>
 .templates{padding:0!important;border-radius:var(--radius)}summary{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;list-style:none;cursor:pointer}summary::-webkit-details-marker{display:none}.summary-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700}.summary-title small{font-size:10px;color:var(--muted);font-weight:600}.expand-icon{display:flex;color:var(--muted);transition:transform 140ms ease}.templates[open] .expand-icon{transform:rotate(180deg)}.template-content{padding:0 16px 16px}.template-list{display:flex;gap:6px;flex-wrap:wrap;max-height:136px;overflow:auto;padding:0 0 12px;margin-bottom:14px;border-bottom:1px solid var(--line)}.template-item{display:inline-flex;align-items:center;gap:8px;max-width:100%;min-height:32px;padding:6px 9px;font-size:11px}.template-item span{overflow-wrap:anywhere;white-space:normal;text-align:start}.template-item small{font-size:10px;color:var(--muted);flex:none}.template-item.selected{background:var(--primary-soft);border-color:var(--primary);color:var(--primary-strong)}form{display:grid;gap:14px;margin:0}.editor-heading{display:flex;align-items:center;justify-content:space-between;gap:10px}h2,h3,h4{margin:0}h2{font-size:13px}.new-template{display:inline-flex;align-items:center;gap:6px;min-height:30px;padding:5px 8px;font-size:11px}.editor-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(260px,.8fr);gap:16px;align-items:stretch}.editor-fields{display:grid;gap:12px;min-width:0}.field{display:grid;gap:6px;margin:0;font-size:12px}.field input,.field textarea{font-size:13px;line-height:1.6;font-weight:400}.field textarea{min-height:150px}.field-caption{font-size:11px;font-weight:650}.variable-tools p{margin:4px 0 8px;font-size:11px;color:var(--muted);line-height:1.6}.variable-chips{display:flex;gap:6px;flex-wrap:wrap}.variable-chip{display:inline-flex;align-items:center;gap:5px;min-height:28px;padding:5px 8px;font-size:10px;font-weight:600;background:var(--surface-alt);border-color:transparent}.template-preview{min-width:0;padding:14px;background:var(--surface-alt);border-radius:var(--radius);display:flex;flex-direction:column;gap:10px}.preview-heading{display:flex;align-items:center;gap:7px;color:var(--muted)}h3{font-size:12px;color:var(--ink)}.preview-note{font-size:10px;color:var(--muted);line-height:1.6;margin:0}.preview-paper{padding:14px;background:var(--surface);border-radius:var(--radius-sm);min-height:160px;max-height:360px;overflow:auto}h4{font-size:13px;overflow-wrap:anywhere}.preview-paper p{font-size:12px;line-height:1.8;white-space:pre-wrap;overflow-wrap:anywhere;margin:12px 0 0}.preview-empty{flex:1;display:grid;place-content:center;justify-items:center;gap:8px;padding:24px 12px;color:var(--muted);text-align:center}.preview-empty p{font-size:11px;margin:0;line-height:1.6}.editor-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding-top:12px;border-top:1px solid var(--line)}.active-toggle{display:inline-flex;align-items:center;gap:8px;margin:0;font-size:12px;cursor:pointer}.active-toggle input{appearance:auto;width:16px;height:16px;min-height:16px;margin:0;padding:0;accent-color:var(--primary);flex:none}.save-template{display:inline-flex;align-items:center;gap:7px;min-height:34px;font-size:12px}.muted{font-size:11px;color:var(--muted)}
 @media(max-width:900px){.editor-grid{grid-template-columns:minmax(0,1fr)}.template-preview{min-height:160px}.preview-paper{max-height:240px}}
 @media(max-width:480px){summary{padding:12px}.template-content{padding:0 12px 12px}.template-preview{padding:12px}.editor-footer{gap:10px}.save-template{margin-inline-start:auto}}
 @media(prefers-reduced-motion:reduce){.expand-icon{transition:none}}
</style>
