import { uiText as t } from '../i18n.ts';
import { documentVariables } from '../document-template.ts';

type Constraint = {
 value: string; type?: string; required?: boolean; minLength?: number; maxLength?: number;
 min?: string; max?: string; maxItems?: number; maxItemLength?: number; templateVariables?: boolean;
 validity?: Partial<ValidityState>;
};
export function validationMessage(field: Constraint, translate: (key: string) => string = t): string {
 const v = field.validity ?? {};
 if (v.badInput) return translate('Masukkan angka yang valid.');
 if (v.valueMissing || (field.required && !['checkbox','radio'].includes(field.type ?? '') && !field.value.trim())) return translate(field.type === 'select' ? 'Pilih salah satu pilihan.' : 'Kolom ini wajib diisi.');
 if (!field.value) return '';
 if (v.typeMismatch) return translate(field.type === 'email' ? 'Masukkan alamat email yang valid.' : 'Masukkan URL yang valid.');
 if (v.patternMismatch) return translate('Format isian belum sesuai.');
 if (v.rangeUnderflow) return `${translate('Nilai minimal')} ${field.min}.`;
 if (v.rangeOverflow) return `${translate('Nilai maksimal')} ${field.max}.`;
 if (v.stepMismatch) return translate('Gunakan nilai sesuai kelipatan yang ditentukan.');
 if ((field.minLength ?? -1) > field.value.length) return `${translate('Minimal')} ${field.minLength} ${translate('karakter.')}`;
 if ((field.maxLength ?? -1) >= 0 && field.value.length > field.maxLength!) return `${translate('Maksimal')} ${field.maxLength} ${translate('karakter.')}`;
 const items = field.value.split(/\r?\n/).map(value=>value.trim()).filter(Boolean);
 if (field.maxItems && items.length > field.maxItems) return `${translate('Maksimal')} ${field.maxItems} ${translate('langkah.')}`;
 if (field.maxItemLength && items.some(value=>value.length>field.maxItemLength!)) return `${translate('Maksimal')} ${field.maxItemLength} ${translate('karakter per langkah.')}`;
 if (field.templateVariables) {
  const remaining = field.value.replace(/\{\{([a-z_]+)\}\}/g,(token,key:string)=>documentVariables.some(variable=>variable.key===key)?'':token);
  if (remaining.includes('{{') || remaining.includes('}}')) return translate('Gunakan variabel dokumen yang tersedia.');
 }
 return v.customError ? translate('Periksa kembali isian ini.') : '';
}

type Control = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
let sequence = 0;
const errors = new WeakMap<Control, HTMLParagraphElement>();
function isControl(value: unknown): value is Control {
 return value instanceof HTMLInputElement || value instanceof HTMLSelectElement || value instanceof HTMLTextAreaElement;
}
function errorFor(field: Control): string {
 if (!field.willValidate || field.disabled) return '';
 return validationMessage({
  value:field.value, type:field instanceof HTMLSelectElement?'select':field.type, required:field.required,
  minLength:'minLength' in field?field.minLength:undefined, maxLength:'maxLength' in field?field.maxLength:undefined,
  min:field.getAttribute('min')??undefined, max:field.getAttribute('max')??undefined, validity:field.validity,
  maxItems:Number(field.dataset.maxItems)||undefined, maxItemLength:Number(field.dataset.maxItemLength)||undefined,
  templateVariables:field.hasAttribute('data-template-variables')
 });
}
function showError(field: Control): boolean {
 const message = errorFor(field);
 let node = errors.get(field);
 if (!message) {
  if (node) {
   const ids=(field.getAttribute('aria-describedby')??'').split(/\s+/).filter(id=>id&&id!==node!.id);
   if(ids.length)field.setAttribute('aria-describedby',ids.join(' '));else field.removeAttribute('aria-describedby');
   node.remove();errors.delete(field);field.removeAttribute('aria-invalid');field.closest('label')?.classList.remove('has-inline-error');
  }
  return true;
 }
 if (!node || !node.isConnected) {
  node=document.createElement('p');node.className='inline-field-error';node.id=`field-error-${++sequence}`;node.setAttribute('role','alert');
  const label=field.closest('label');
  if(label){label.append(node);label.classList.add('has-inline-error');}else field.insertAdjacentElement('afterend',node);
  errors.set(field,node);
  const ids=(field.getAttribute('aria-describedby')??'').split(/\s+/).filter(Boolean);ids.push(node.id);field.setAttribute('aria-describedby',ids.join(' '));
 }
 node.textContent=message;field.setAttribute('aria-invalid','true');
 return false;
}
export function validateFormFields(form: HTMLFormElement, only?: Control[]): boolean {
 const fields=only??Array.from(form.elements).filter(isControl);
 let first:Control|undefined;
 for(const field of fields)if(!showError(field)&&!first)first=field;
 first?.focus();return !first;
}
/** Delegated validation includes forms opened later in dialogs or expandable editors. */
export function installFieldValidation(root: Document): () => void {
 const owned=(form:HTMLFormElement|null)=>form&&!form.hasAttribute('data-custom-validation');
 const submit=(event:Event)=>{
  const form=event.target;
  if(!(form instanceof HTMLFormElement)||!owned(form))return;
  if(!validateFormFields(form)){event.preventDefault();event.stopImmediatePropagation();}
 };
 const blur=(event:Event)=>{const field=event.target;if(isControl(field)&&owned(field.form))showError(field);};
 const input=(event:Event)=>{
  const field=event.target;if(!isControl(field)||!owned(field.form))return;
  if(errors.has(field))showError(field);
  requestAnimationFrame(()=>{if(field.form)for(const sibling of Array.from(field.form.elements).filter(isControl))if(errors.has(sibling))showError(sibling);});
 };
 const invalid=(event:Event)=>{event.preventDefault();const field=event.target;if(isControl(field)&&owned(field.form))showError(field);};
 root.addEventListener('submit',submit,true);root.addEventListener('focusout',blur,true);
 root.addEventListener('input',input,true);root.addEventListener('change',input,true);root.addEventListener('invalid',invalid,true);
 return()=>{root.removeEventListener('submit',submit,true);root.removeEventListener('focusout',blur,true);root.removeEventListener('input',input,true);root.removeEventListener('change',input,true);root.removeEventListener('invalid',invalid,true);};
}
