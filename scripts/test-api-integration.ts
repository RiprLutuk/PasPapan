import { spawn } from 'bun';

const base = process.env.TEST_API_URL ?? 'http://127.0.0.1:3000';
if (!['localhost','127.0.0.1'].includes(new URL(base).hostname)) throw new Error('Integration tests require a local API.');
const origin = process.env.APP_ORIGIN?.split(',')[0].trim() ?? 'http://127.0.0.1:4173';
let ready=false;
for(let attempt=0;attempt<30;attempt++){
    try{if((await fetch(base+'/health')).ok){ready=true;break;}}catch{}
    await Bun.sleep(500);
}
if(!ready)throw new Error('Local API is not ready. Start bun run dev:services before running integration tests.');
for (const suite of ['operations-smoke','requests-smoke','multi-tenant-isolation','people-account-smoke']) {
	const child=spawn(['bun',`apps/api/tests/${suite}.ts`],{env:{...process.env,TEST_API_URL:base,APP_ORIGIN:origin},stdout:'inherit',stderr:'inherit',stdin:'inherit'});
	const code=await child.exited;
	if(code!==0)process.exit(code);
}
