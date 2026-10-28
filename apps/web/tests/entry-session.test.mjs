import test from 'node:test';
import assert from 'node:assert/strict';
import { load as entry } from '../src/routes/+page.server.ts';
import { load as login } from '../src/routes/login/+page.server.ts';
import { load as layout } from '../src/routes/+layout.server.ts';

function event({ token='a'.repeat(64), status=200, body={user:{id:'user'},company:{id:'company'},capabilities:[]}, outage=false, path='/', route=path.split('?')[0] }={}) {
 const state={calls:0,deleted:[]};
 return {state,route:{id:route},params:{},url:new URL(path,'https://paspapan.example'),cookies:{get:()=>token,delete:(name,options)=>state.deleted.push({name,...options})},fetch:async(path,options)=>{
  state.calls++;assert.equal(path,'/v1/auth/session');assert.equal(options.cache,'no-store');
  if(outage)throw new TypeError('network failure');
  return new Response(JSON.stringify(body),{status,headers:{'content-type':'application/json'}});
 }};
}
const redirected=(location)=>error=>error.status===303&&error.location===location;

test('authenticated root and login requests redirect before rendering a login page',async()=>{
 const root=event();await assert.rejects(entry(root),redirected('/app/today'));assert.equal(root.state.calls,1);
 const direct=event({path:'/login?next=/admin/dashboard'});await assert.rejects(login(direct),redirected('/admin/dashboard'));
 const unsafe=event({path:'/login?next=https://external.example'});await assert.rejects(login(unsafe),redirected('/app/today'));
});
test('anonymous root redirects and anonymous login renders without an API roundtrip',async()=>{
 const root=event({token:null});
 await assert.rejects(entry(root),redirected('/login'));assert.equal(root.state.calls,0);
 assert.deepEqual(await login(root),{});assert.equal(root.state.calls,0);
});
test('invalid and expired cookies are cleared before presenting sign-in',async()=>{
 for(const fixture of [{token:'malformed'},{status:401}]){
  const request=event(fixture);await assert.rejects(entry(request),redirected('/login?reason=expired'));
  assert.deepEqual(request.state.deleted,[{name:'paspapan_session',path:'/'}]);
 }
});
test('outages and malformed API responses do not redirect valid-cookie users to login',async()=>{
 for(const fixture of [{outage:true},{status:500},{status:502},{body:{}}]){
  const request=event(fixture);await assert.rejects(login(request),error=>error.status===503);
  assert.deepEqual(request.state.deleted,[]);
 }
});


test('all protected areas redirect anonymous requests before rendering their shell',async()=>{
 for(const path of ['/app/today','/app/schedule','/app/scan','/app/requests?kind=leave','/admin/employees','/admin/dashboard','/user/profile','/face-enrollment']){
  const request=event({path,token:null});
  await assert.rejects(layout(request),redirected('/login?'+new URLSearchParams({next:path})));
  assert.equal(request.state.calls,0);
 }
});
test('server session data initializes workspace, profile and biometrics directly',async()=>{
 for(const path of ['/app/today','/user/profile','/face-enrollment']){
  const request=event({path});const data=await layout(request);
  assert.equal(data.authSession.user.id,'user');assert.equal(request.state.calls,1);
 }
});
test('permissions are checked on the server before an admin page renders',async()=>{
 const denied=event({path:'/admin/employees'});await assert.rejects(layout(denied),error=>error.status===403);
 const allowed=event({path:'/admin/employees',body:{user:{id:'user'},company:{id:'company'},capabilities:['admin.employees.view']}});
 assert.equal((await layout(allowed)).authSession.user.id,'user');
});
test('unknown URLs retain 404 handling while recovery pages remain public',async()=>{
 for(const path of ['/register','/forgot-password','/reset-password']){
  const request=event({path});assert.equal((await layout(request)).authSession,null);assert.equal(request.state.calls,0);
 }
 const unknown=event({path:'/app/today/typo',route:'/app/[...path]'});
 assert.equal((await layout(unknown)).authSession,null);assert.equal(unknown.state.calls,0);
});
test('legacy aliases use server redirects without rendering a transitional page',async()=>{
 for(const [path,target] of [['/home','/app/today'],['/scan','/app/scan'],['/announcements','/app/announcements'],['/admin/masterdata/shift','/admin/attendance']]){
  const request=event({path});await assert.rejects(layout(request),redirected(target));assert.equal(request.state.calls,0);
 }
});
