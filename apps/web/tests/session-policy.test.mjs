import test from 'node:test';
import assert from 'node:assert/strict';
import { sessionRemaining, safeReturnPath } from '../src/lib/session-policy.ts';
import { isLegacyRoute } from '../src/lib/legacy-features.ts';
import { request } from '../src/lib/api.ts';

test('idle and absolute deadlines use server time rather than the device clock', () => {
 const life={expires_at:2000,idle_expires_at:1100,server_time:1000};
 assert.equal(sessionRemaining(life,900000000,900030000),70);
 assert.equal(sessionRemaining(life,900000000,900200000),0);
 assert.equal(sessionRemaining({...life,expires_at:1010},900000000,900005000),5);
});
test('return URLs cannot navigate off site or loop into authentication pages',()=>{
 for(const value of [null,'https://evil.test','//evil.test','/\\evil.test','/login?next=/login','/login/','/reset-password','/x\n'])assert.equal(safeReturnPath(value),'/app/today');
 assert.equal(safeReturnPath('/app/requests?kind=leave'),'/app/requests?kind=leave');
});
test('unregistered subpaths never inherit a valid parent module',()=>{
 for(const value of ['/app/today/random','/admin/dashboard/typo','/unknown','/app/requests/create/whatever'])assert.equal(isLegacyRoute(value),false,value);
 assert.equal(isLegacyRoute('/admin/accounting'),true);
});
test('expired sessions trigger revalidation; incorrect passwords and outages do not log out',async()=>{
 const originalFetch=globalThis.fetch,originalWindow=globalThis.window;
 const events=[];globalThis.window={dispatchEvent:event=>events.push(event.type)};
 try {
  for(const [status,message,expected] of [[401,'invalid email or password',0],[503,'unavailable',0],[401,'session expired or revoked',1]]){
   globalThis.fetch=async()=>new Response(JSON.stringify({error:message}),{status});
   await assert.rejects(request('/v1/account/password',{method:'POST'}));assert.equal(events.length,expected);
  }
  events.length=0;
  await assert.rejects(request('/v1/auth/session'));assert.equal(events.length,0);
  globalThis.fetch=async()=>new Response(null,{status:204});
  await request('/v1/auth/logout',{method:'POST'});assert.deepEqual(events,['paspapan:logout']);
 } finally {globalThis.fetch=originalFetch;if(originalWindow===undefined)delete globalThis.window;else globalThis.window=originalWindow;}
});

test('expiry warnings distinguish inactivity from the non-renewable session limit',async()=>{
 const {sessionWarning}=await import('../src/lib/session-policy.ts');
 const life={expires_at:2000,idle_expires_at:1103,server_time:1000};
 assert.deepEqual(sessionWarning(life,10000,10000),{kind:'idle',countdown:'1:43'});
 assert.deepEqual(sessionWarning({...life,expires_at:1103},10000,10000),{kind:'absolute',countdown:'1:43'});
 assert.equal(sessionWarning({...life,idle_expires_at:1500},10000,10000),null);
 assert.equal(sessionWarning(life,10000,114000),null);
 assert.equal(sessionWarning(life,10000,11000)?.countdown,'1:42');
});
