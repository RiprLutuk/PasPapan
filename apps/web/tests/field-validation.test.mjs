import test from 'node:test';
import assert from 'node:assert/strict';
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { validationMessage } from '../src/lib/ui/field-validation.ts';
const message=spec=>validationMessage(spec,key=>key);

test('required and whitespace-only fields have app-owned inline messages',()=>{
 assert.equal(message({value:'  ',required:true}),'Kolom ini wajib diisi.');
 assert.equal(message({value:'',type:'select',validity:{valueMissing:true}}),'Pilih salah satu pilihan.');
 assert.equal(message({value:'',required:false}),'');
});
test('email, URL, ranges and steps never use the browser validation text',()=>{
 assert.equal(message({value:'bad',type:'email',validity:{typeMismatch:true}}),'Masukkan alamat email yang valid.');
 assert.equal(message({value:'bad',type:'url',validity:{typeMismatch:true}}),'Masukkan URL yang valid.');
 assert.equal(message({value:'0',min:'1',validity:{rangeUnderflow:true}}),'Nilai minimal 1.');
 assert.equal(message({value:'2026-01-01',min:'2026-02-01',validity:{rangeUnderflow:true}}),'Nilai minimal 2026-02-01.');
 assert(message({value:'1.5',validity:{stepMismatch:true}}).includes('kelipatan'));
 assert.equal(message({value:'a',minLength:3}),'Minimal 3 karakter.');
});
test('checklist limits count meaningful lines and reject oversized steps',()=>{
 assert.equal(message({value:'One\n\n Two\n',maxItems:2}),'');
 assert.equal(message({value:'One\nTwo\nThree',maxItems:2}),'Maksimal 2 langkah.');
 assert.equal(message({value:'long',maxItemLength:3}),'Maksimal 3 karakter per langkah.');
});
test('unsupported, incomplete or stray template tokens are errors on the document body',()=>{
 for(const value of ['{{unknown}}','{{employee_name','text }}','{{ employee_name }}'])assert.equal(message({value,templateVariables:true}),'Gunakan variabel dokumen yang tersedia.');
 assert.equal(message({value:'Hello {{employee_name}} at {{company_name}}',templateVariables:true}),'');
});

test('every application form opts out of native browser validation popups',()=>{
 const root=new URL('../src/',import.meta.url);
 function visit(path){for(const entry of readdirSync(path,{withFileTypes:true})){const file=join(path,entry.name);if(entry.isDirectory())visit(file);else if(file.endsWith('.svelte')){const source=readFileSync(file,'utf8');for(const match of source.matchAll(/<form\b[^>]*>/g))assert.match(match[0],/\bnovalidate\b/,file);}}}
 visit(root.pathname);
});
