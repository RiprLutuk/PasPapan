import {test,expect} from 'bun:test';
import {companyArgument,connectionEnvironment} from './legacy-preflight';
test('preflight accepts only explicit numeric company selection and never an apply flag',()=>{
    expect(companyArgument(['--company-id','12'])).toBe('12');
    for(const args of [[],['--apply','12'],['--company-id','1;DROP TABLE users'],['--company-id','0'],['--company-id','1','--apply']])expect(()=>companyArgument(args)).toThrow();
});
test('connection credentials stay in environment, with read-only SQL and bounded execution',()=>{
    const env=connectionEnvironment('postgres://operator:hidden-password@127.0.0.1:1921/legacy?sslmode=require');
    expect(env.PGPASSWORD).toBe('hidden-password');expect(env.PGOPTIONS).toContain('default_transaction_read_only=on');expect(env.PGSSLMODE).toBe('require');
    try{connectionEnvironment('https://operator:hidden-password@example.test');throw new Error('should reject');}catch(error){expect(String(error)).not.toContain('hidden-password');}
});
