import test from 'node:test';
import assert from 'node:assert/strict';
import { documentVariables, previewDocumentTemplate } from '../src/lib/document-template.ts';

test('document preview supports all six server-defined employee fields',()=>{
 const values={employee_name:'Budi Santoso',employee_code:'EMP-001',company_name:'Example Co',date:'2026-10-28',division:'Operations',job_title:'Staff'};
 const body=documentVariables.map(variable=>`{{${variable.key}}}`).join(' / ');
 assert.equal(previewDocumentTemplate(body,values),Object.values(values).join(' / '));
});
test('preview never recursively expands values or hides unsupported tokens',()=>{
 assert.equal(previewDocumentTemplate('{{employee_name}} / {{company_name}} / {{unknown}}',{employee_name:'{{company_name}} $& <script>',company_name:'Example Co'}),'{{company_name}} $& <script> / Example Co / {{unknown}}');
});
