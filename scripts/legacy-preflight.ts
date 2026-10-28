// Read-only inventory. Source passwords and personal records are never printed.
import {spawnSync} from 'bun';

export function companyArgument(args:string[]):string{
    if(args.length!==2||args[0]!=='--company-id'||!/^\d{1,18}$/.test(args[1])||BigInt(args[1])<1n)throw new Error('Usage: migration:preflight --company-id <legacy numeric company ID>. This command cannot apply data changes.');
    return args[1];
}
export function connectionEnvironment(raw:string|undefined):Record<string,string>{
    try{
        if(!raw)throw new Error();const url=new URL(raw);
        if(!['postgres:','postgresql:'].includes(url.protocol)||!url.hostname||url.pathname.length<2)throw new Error();
        const ssl=url.searchParams.get('sslmode')??'prefer';if(!['disable','allow','prefer','require','verify-ca','verify-full'].includes(ssl))throw new Error();
        return {PGHOST:url.hostname,PGPORT:url.port||'5432',PGDATABASE:decodeURIComponent(url.pathname.slice(1)),PGUSER:decodeURIComponent(url.username),PGPASSWORD:decodeURIComponent(url.password),PGSSLMODE:ssl,PGOPTIONS:'-c default_transaction_read_only=on -c statement_timeout=15000'};
    }catch{throw new Error('Set LEGACY_DATABASE_URL to the source PostgreSQL connection; no credential value was logged.');}
}
export function inspect(company:string,environment:Record<string,string>){
    company=companyArgument(['--company-id',company]);
    function query<T>(sql:string):T{
        const result=spawnSync(['psql','-X','-q','-At','-v','ON_ERROR_STOP=1','-c','BEGIN READ ONLY','-c',sql,'-c','ROLLBACK'],{env:{...process.env,...environment},stdout:'pipe',stderr:'pipe'});
        if(result.exitCode!==0)throw new Error('Source inspection failed. Check connectivity, schema and read-only access; no source changes were made.');
        return JSON.parse(result.stdout.toString().trim());
    }
    const tables=query<string[]>("SELECT COALESCE(json_agg(table_name),'[]') FROM information_schema.tables WHERE table_schema='public'");
    if(!tables.includes('users')||!tables.includes('companies'))throw new Error('Explicit company mapping is required: source users/companies schema was not recognized.');
    const columns=query<Record<string,string[]>>("SELECT COALESCE(json_object_agg(table_name,columns),'{}') FROM (SELECT table_name,json_agg(column_name) columns FROM information_schema.columns WHERE table_schema='public' GROUP BY table_name) c");
    if(!columns.users?.includes('company_id'))throw new Error('The source has no user company IDs. Prepare an explicit tenant mapping before migration.');
    if(!query<boolean>(`SELECT to_json(EXISTS(SELECT 1 FROM public.companies WHERE id=${company}))`))throw new Error('Legacy company ID does not exist.');
    const rows:Record<string,number>={users:query<number>(`SELECT to_json(count(*)) FROM public.users WHERE company_id=${company}`)};
    for(const table of ['attendances','schedules','payrolls','reimbursements']){
        if(tables.includes(table)&&columns[table]?.includes('user_id'))rows[table]=query<number>(`SELECT to_json(count(*)) FROM public.${table} r JOIN public.users u ON u.id=r.user_id WHERE u.company_id=${company}`);
    }
    const blockers:string[]=['Select an explicit legacy-user → owner mapping. No source role is promoted automatically.','Create a migration batch with stable source-ID mappings before applying records.'];
    const warnings:string[]=['Legacy sessions, reset tokens, API tokens and biometric verification are not transferable.','Bank names require an explicit mapping to the bank codes configured by super admin.'];
    if(columns.users.includes('password')){
        const incompatible=query<number>(`SELECT to_json(count(*)) FROM public.users WHERE company_id=${company} AND (password IS NULL OR password NOT LIKE '$argon2id$%')`);
        if(incompatible)blockers.push(`${incompatible} account(s) need a password-reset plan; legacy hashes cannot be treated as Rust Argon2 credentials.`);
    }
    if(columns.attendances?.includes('photo'))warnings.push('Attendance photos need a separate private-storage copy and checksum reconciliation.');
    if(columns.payrolls?.includes('net_salary')){
        const fractions=query<number>(`SELECT to_json(count(*)) FROM public.payrolls p JOIN public.users u ON u.id=p.user_id WHERE u.company_id=${company} AND p.net_salary<>trunc(p.net_salary)`);
        if(fractions)blockers.push(`${fractions} payroll record(s) contain fractional rupiah. Define a rounding/preservation policy before import.`);
    }
    return {format_version:1,mode:'read-only preflight',source_database:environment.PGDATABASE,source_company_id:company,rows,blockers,warnings,source_modified:false};
}
if(import.meta.main){try{console.log(JSON.stringify(inspect(companyArgument(process.argv.slice(2)),connectionEnvironment(process.env.LEGACY_DATABASE_URL)),null,2));}catch(error){console.error(error instanceof Error?error.message:'Preflight failed.');process.exitCode=1;}}
