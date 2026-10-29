import test from 'node:test';
import assert from 'node:assert/strict';
import { request, ApiError } from '../src/lib/api.ts';

test('successful empty creation responses do not turn saved records into UI errors',async()=>{
 const original=globalThis.fetch;
 try {
  for(const status of [200,201,204]){
   globalThis.fetch=async()=>new Response(null,{status});
   assert.equal(await request('/v1/admin/hr-checklists',{method:'POST'}),undefined);
  }
  globalThis.fetch=async()=>Response.json({id:'saved'},{status:201});
  assert.deepEqual(await request('/v1/admin/hr-checklist-templates'),{id:'saved'});
  globalThis.fetch=async()=>new Response('{broken',{status:200});
  await assert.rejects(request('/v1/admin/hr-checklists'),SyntaxError);
  globalThis.fetch=async()=>Response.json({error:'employee not found'},{status:400});
  await assert.rejects(request('/v1/admin/hr-checklists'),error=>error instanceof ApiError&&error.status===400);
 }finally{globalThis.fetch=original;}
});
