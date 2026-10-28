// Local dedicated rewrite database only. Tests alter only this run's session fixtures.
import assert from 'node:assert/strict';
const base=process.env.TEST_API_URL??'http://127.0.0.1:3000';
const database=process.env.DATABASE_URL!;
const origin=process.env.APP_ORIGIN?.split(',')[0].trim()??'http://127.0.0.1:4173';
assert.equal(new URL(database).pathname,'/paspapan_rewrite');
for(const url of [base,database])assert(['localhost','127.0.0.1'].includes(new URL(url).hostname));
let user='',company='',checks=0;
function sql(query:string){const r=Bun.spawnSync(['psql',database,'-v','ON_ERROR_STOP=1','-Atqc',query]);assert.equal(r.exitCode,0,r.stderr.toString());return r.stdout.toString().trim();}
const suffix=crypto.randomUUID(),email=suffix+'@example.test',password='session-fixture-password-123';
async function call(path:string,cookie='',body?:unknown,status=200,requestOrigin=origin){
 const response=await fetch(base+path,{method:body===undefined?'GET':'POST',headers:{cookie,origin:requestOrigin,'content-type':'application/json',...(process.env.API_INTERNAL_KEY?{'x-paspapan-service-key':process.env.API_INTERNAL_KEY}:{})},body:body===undefined?undefined:JSON.stringify(body)});
 const text=await response.text();assert.equal(response.status,status,path+': '+text);checks++;
 return {body:text?JSON.parse(text):null,cookie:response.headers.get('set-cookie')??''};
}
async function login(){return (await call('/v1/auth/login','',{email,password})).cookie.split(';')[0];}
async function info(cookie:string){return (await call('/v1/auth/session',cookie)).body.lifetime;}
async function expired(cookie:string){const r=await call('/v1/auth/session',cookie,undefined,401);assert.equal(r.body.error,'session expired or revoked');assert.match(r.cookie,/Max-Age=0/);checks+=2;await call('/v1/auth/activity',cookie,{},401);await call('/v1/attendance/today',cookie,undefined,401);}
try{
 const registration=await call('/v1/auth/register','',{company_name:'Session fixture',company_slug:'session-'+suffix,full_name:'Session test',email,password});
 const cookie=registration.cookie.split(';')[0];
 const session=(await call('/v1/auth/session',cookie)).body;user=session.user.id;company=session.company.id;
 const a=session.lifetime;assert(a.id&&a.expires_at>a.idle_expires_at&&a.idle_timeout_seconds>=60);checks++;
 assert.match(registration.cookie,/HttpOnly/);checks++;
 const original=sql(`SELECT idle_expires_at::text FROM sessions WHERE id='${a.id}'`);
 await info(cookie);assert.equal(sql(`SELECT idle_expires_at::text FROM sessions WHERE id='${a.id}'`),original);checks++;
 await call('/v1/auth/activity',cookie,{},403,'https://untrusted.example');
 sql(`UPDATE sessions SET idle_expires_at=now()+interval '90 seconds' WHERE id='${a.id}'`);
 const renewed=(await call('/v1/auth/activity',cookie,{})).body;
 assert(renewed.idle_expires_at>renewed.server_time+90);assert.equal(renewed.expires_at,a.expires_at);checks+=2;
 const wrong=await call('/v1/account/password',cookie,{current_password:'incorrect',new_password:'replacement-password-123'},401);
 assert.equal(wrong.cookie,'');await info(cookie);checks++;
 sql(`UPDATE sessions SET idle_expires_at=now()-interval '1 second' WHERE id='${a.id}'`);await expired(cookie);
 const absoluteCookie=await login(),b=await info(absoluteCookie);
 sql(`UPDATE sessions SET created_at=now()-interval '1 day',expires_at=now()-interval '1 second' WHERE id='${b.id}'`);await expired(absoluteCookie);
 const revokedCookie=await login(),c=await info(revokedCookie);
 sql(`UPDATE sessions SET revoked_at=now() WHERE id='${c.id}'`);await expired(revokedCookie);
 const cappedCookie=await login(),d=await info(cappedCookie);
 sql(`UPDATE sessions SET expires_at=now()+interval '75 seconds' WHERE id='${d.id}'`);
 const capped=(await call('/v1/auth/activity',cappedCookie,{})).body;
 assert.equal(capped.expires_at,capped.idle_expires_at);checks++;
 const listed=(await call('/v1/account/sessions',cappedCookie)).body;
 assert(listed.every((s:any)=>s.id!==a.id&&s.id!==b.id&&s.id!==c.id));assert(listed.find((s:any)=>s.id===d.id)?.last_active_at);checks+=2;
 await call('/v1/auth/logout',cappedCookie,{},204);await expired(cappedCookie);
 console.log(`${checks} session lifecycle checks passed`);
}finally{
 if(company)sql(`DELETE FROM companies WHERE id='${company}'`);
 if(user)sql(`DELETE FROM users WHERE id='${user}'`);
}
