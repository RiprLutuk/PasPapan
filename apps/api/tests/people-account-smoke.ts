// Dedicated rewrite database only. Creates and removes this run's UUID fixtures.
import assert from 'node:assert/strict';
const base = process.env.TEST_API_URL ?? 'http://127.0.0.1:3000';
const origin = process.env.APP_ORIGIN ?? 'http://127.0.0.1:4173';
const database = process.env.DATABASE_URL!;
const url = new URL(database);
assert.equal(url.pathname, '/paspapan_rewrite');
assert(['127.0.0.1','localhost'].includes(url.hostname));
assert(['127.0.0.1','localhost'].includes(new URL(base).hostname));
const users: string[] = [], companies: string[] = [];
const password = 'people-smoke-password-123';
let checks = 0;
function sql(query: string) {
    const r = Bun.spawnSync(['psql', database, '-v', 'ON_ERROR_STOP=1', '-Atqc', query]);
    assert.equal(r.exitCode, 0, r.stderr.toString());
    return r.stdout.toString().trim();
}
const serviceKey = process.env.API_INTERNAL_KEY?.trim();
async function call(path: string, cookie = '', body?: unknown, expected = 200, trusted = true) {
    const response = await fetch(base + path, { method: body === undefined ? 'GET' : 'POST', headers: { cookie, 'content-type':'application/json', ...(trusted ? {Origin:origin} : {}), ...(serviceKey ? { 'x-paspapan-service-key': serviceKey } : {}) }, body: body === undefined ? undefined : JSON.stringify(body) });
    const text = await response.text();
    assert.equal(response.status, expected, path + ': ' + text); checks++;
    if(path.startsWith('/v1/account/')) assert.equal(response.headers.get('cache-control'),'no-store');
    return { body: text && response.headers.get('content-type')?.includes('json') ? JSON.parse(text) : null, cookie: response.headers.get('set-cookie')?.split(';')[0] ?? '' };
}
async function owner() {
    const suffix = crypto.randomUUID(), email = suffix + '@example.test';
    const login = await call('/v1/auth/register', '', { company_name:'People smoke', company_slug:'people-'+suffix, full_name:'Owner', email, password });
    const session = (await call('/v1/auth/session', login.cookie)).body;
    users.push(session.user.id); companies.push(session.company.id);
    return { cookie:login.cookie, id:session.user.id, company:session.company.id };
}
async function employee(c: Awaited<ReturnType<typeof owner>>, name: string) {
    const email = crypto.randomUUID()+'@example.test';
    const created = (await call('/v1/admin/employees',c.cookie,{full_name:name,email,password,employee_code:name,manager_user_id:c.id},201)).body;
    users.push(created.id);
    const login = await call('/v1/auth/login','',{email,password});
    return { id:created.id, email, cookie:login.cookie };
}
function edit(d: any, overrides: Record<string,unknown> = {}) {
    return { full_name:d.full_name, employee_code:d.employee_code, manager_user_id:d.manager_user_id,
        phone:d.phone,address:d.address,team:d.team,job_title:d.job_title,started_on:d.started_on,version:d.version,...overrides };
}
try {
    const c=await owner(), foreign=await owner(), a=await employee(c,'Employee A'), b=await employee(c,'Employee B');
    const detail=async(id=a.id)=>(await call('/v1/admin/employees/'+id,c.cookie)).body;
    const update=async(body:unknown,status=204)=>call('/v1/admin/employees/'+a.id,c.cookie,body,status);
    await call('/v1/admin/employee-directory','',undefined,401);
    await call('/v1/admin/employee-directory',a.cookie,undefined,403);
    await call('/v1/admin/employee-directory?page=0',c.cookie,undefined,400);
    await call('/v1/admin/employee-directory?status=deleted',c.cookie,undefined,400);
    assert.equal((await call('/v1/admin/employee-directory?q=Employee%20A&role=employee',c.cookie)).body.total,1);
    assert.equal((await call('/v1/admin/employee-directory?page=2',c.cookie)).body.items.length,0);
    await call('/v1/admin/employees/'+a.id,foreign.cookie,undefined,400);
    let d=await detail();
    await call('/v1/admin/employees/'+a.id,foreign.cookie,edit(d),400);
    await call('/v1/admin/employees/'+a.id,c.cookie,edit(d),403,false);
    await update(edit(d,{team:'Field',job_title:'Engineer',phone:'08123456',started_on:'2026-01-01'}));
    await update(edit(d),409);
    d=await detail(); assert.equal(d.team,'Field'); assert.equal(d.version,1);
    await update(edit(d,{employee_code:'Employee B',full_name:'Must roll back'}),409);
    assert.equal((await detail()).full_name,'Employee A');
    await update(edit(d,{started_on:'2026-02-30'}),400);
    await update(edit(d,{role:'owner'}),422);
    await update(edit(d,{manager_user_id:a.id}),400);
    await update(edit(d,{manager_user_id:foreign.id}),400);
    await update(edit(d,{manager_user_id:b.id}));
    await call('/v1/admin/employees/'+b.id,c.cookie,edit(await detail(b.id),{manager_user_id:a.id}),400);
    await call('/v1/admin/employees/'+c.id+'/status',c.cookie,{status:'inactive',version:0},400);
    await call('/v1/admin/employees/'+c.id,c.cookie,edit(await detail(c.id)),403);

    await call('/v1/admin/access/members/'+a.id,c.cookie,{role_id:null,grants:['admin.employees.view'],denies:[]},204);
    await call('/v1/admin/employees/'+a.id,a.cookie);
    await call('/v1/admin/employees/'+b.id,a.cookie,edit(await detail(b.id)),403);
    await call('/v1/admin/employees/'+b.id+'/status',a.cookie,{status:'inactive',version:0},403);
    await call('/v1/admin/access/members/'+a.id,c.cookie,{role_id:null,grants:[],denies:[]},204);

    const oldSession=a.cookie;
    d=await detail();
    await call('/v1/admin/employees/'+a.id+'/status',c.cookie,{status:'inactive',version:d.version},204);
    await call('/v1/auth/session',oldSession,undefined,401);
    await call('/v1/auth/login','',{email:a.email,password},401);
    assert(!(await call('/v1/admin/employees',c.cookie)).body.some((p:any)=>p.id===a.id));
    assert.equal((await call('/v1/admin/employee-directory?status=inactive',c.cookie)).body.items[0].id,a.id);
    await call('/v1/admin/employees',c.cookie,{full_name:'Rejected',email:crypto.randomUUID()+'@example.test',password,employee_code:'rejected',manager_user_id:a.id},400);
    await call('/v1/admin/employees/'+a.id+'/status',c.cookie,{status:'active',version:d.version},409);
    await call('/v1/admin/employees/'+a.id+'/status',c.cookie,{status:'active',version:(await detail()).version},204);
    await call('/v1/auth/session',oldSession,undefined,401);
    a.cookie=(await call('/v1/auth/login','',{email:a.email,password})).cookie;
    const second=(await call('/v1/auth/login','',{email:a.email,password})).cookie;

    const profile=(await call('/v1/account/profile',a.cookie)).body;
    await call('/v1/account/profile',a.cookie,{full_name:'Edited self',phone:'0800000',address:'Local office',version:profile.version},204);
    await call('/v1/account/profile',a.cookie,{full_name:'Stale',phone:'',address:'',version:profile.version},409);
    assert.equal((await call('/v1/auth/session',a.cookie)).body.user.full_name,'Edited self');
    await call('/v1/account/profile',a.cookie,{full_name:'Injection',phone:'',address:'',version:profile.version+1,role:'owner'},422);
    await call('/v1/account/profile',a.cookie,{full_name:'CSRF',phone:'',address:'',version:profile.version+1},403,false);

    // The same user can belong to another company: admin edits must not mutate that identity.
    sql(`INSERT INTO company_memberships(company_id,user_id,role) VALUES('${foreign.company}','${a.id}','employee'); INSERT INTO employee_profiles(company_id,user_id,employee_code) VALUES('${foreign.company}','${a.id}','SHARED');`);
    d=await detail();
    await update(edit(d,{full_name:'Cross-company rename'}),409);
    await update(edit(d,{team:'Primary only'}));
    assert.equal(sql(`SELECT team FROM employee_profiles WHERE company_id='${foreign.company}' AND user_id='${a.id}'`),'');

    const list=(await call('/v1/account/sessions',a.cookie)).body;
    assert(list.some((s:any)=>s.current));
    assert(!JSON.stringify(list).includes('token_hash'));
    const other=list.find((s:any)=>!s.current);
    await call('/v1/account/sessions/'+other.id+'/revoke',b.cookie,{current_password:password},400);
    await call('/v1/account/sessions/'+other.id+'/revoke',a.cookie,{current_password:'wrong'},401);
    await call('/v1/account/sessions/'+other.id+'/revoke',a.cookie,{current_password:password},403,false);
    await call('/v1/account/sessions/'+other.id+'/revoke',a.cookie,{current_password:password},204);
    const activeHash=sql(`SELECT token_hash FROM sessions WHERE id='${other.id}'`);
    assert(activeHash.length>0);
    // Revoke every remaining other session deterministically, including second login.
    for(const s of (await call('/v1/account/sessions',a.cookie)).body.filter((s:any)=>!s.current)) {
        await call('/v1/account/sessions/'+s.id+'/revoke',a.cookie,{current_password:password},204);
    }
    await call('/v1/auth/session',second,undefined,401);
    const extra=(await call('/v1/auth/login','',{email:a.email,password})).cookie;
    await call('/v1/account/password',a.cookie,{current_password:'wrong',new_password:'new-password-123'},401);
    await call('/v1/account/password',a.cookie,{current_password:password,new_password:'short'},400);
    await call('/v1/account/password',a.cookie,{current_password:password,new_password:'new-password-123'},403,false);
    const changed=await call('/v1/account/password',a.cookie,{current_password:password,new_password:'new-password-123'});
    assert(changed.cookie && changed.cookie!==a.cookie);
    await call('/v1/auth/session',changed.cookie);
    await call('/v1/auth/session',a.cookie,undefined,401);
    await call('/v1/auth/session',extra,undefined,401);
    await call('/v1/auth/login','',{email:a.email,password},401);
    await call('/v1/auth/login','',{email:a.email,password:'new-password-123'});
    await call('/v1/auth/session',b.cookie);
    assert.equal(sql(`SELECT count(*) FROM audit_events WHERE company_id='${c.company}' AND event_type='account.password_changed'`),'1');
    assert.equal(sql(`SELECT metadata::text FROM audit_events WHERE company_id='${c.company}' AND event_type='account.password_changed'`),'{}');
    assert.equal(sql(`SELECT count(*) FROM audit_events WHERE company_id='${c.company}' AND event_type='employee.status_changed'`),'2');
    // Populate > one page without expensive password hashing for each fixture.
    const paged = Array.from({length:51},()=>crypto.randomUUID()); users.push(...paged);
    sql(`INSERT INTO users(id,email,password_hash,full_name) VALUES ${paged.map((id,i)=>`('${id}','${id}@example.test','unused-test-fixture','PAGED ${String(i).padStart(3,'0')}')`).join(',')};
        INSERT INTO company_memberships(company_id,user_id,role) SELECT '${c.company}',id,'employee' FROM users WHERE id IN (${paged.map(id=>`'${id}'`).join(',')});
        INSERT INTO employee_profiles(company_id,user_id,employee_code) SELECT '${c.company}',id,id::text FROM users WHERE id IN (${paged.map(id=>`'${id}'`).join(',')});`);
    const first=(await call('/v1/admin/employee-directory',c.cookie)).body;
    const last=(await call('/v1/admin/employee-directory?page=2',c.cookie)).body;
    assert.equal(first.total,54); assert.equal(first.items.length,50); assert.equal(last.items.length,4);
    assert.equal(new Set([...first.items,...last.items].map(p=>p.id)).size,54);
    assert.equal((await call('/v1/admin/employee-directory?q=PAGED&role=employee',c.cookie)).body.total,51);
    console.log(`PASS: ${checks} HTTP assertions — employee edit/filter/pagination, cycle prevention, optimistic locking, tenant isolation, status/session invalidation, profile, password rotation, session revocation and audit`);
} finally {
    for(const id of companies) sql(`DELETE FROM audit_events WHERE company_id='${id}'; DELETE FROM member_access WHERE company_id='${id}'; DELETE FROM access_roles WHERE company_id='${id}'; DELETE FROM employee_profiles WHERE company_id='${id}'; DELETE FROM companies WHERE id='${id}';`);
    for(const id of users) sql(`DELETE FROM users WHERE id='${id}'`);
}
