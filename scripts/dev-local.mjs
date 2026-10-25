#!/usr/bin/env node

import { spawn } from 'node:child_process';
import { readFileSync, existsSync, copyFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const root = process.cwd();
const useProcessGroups = process.platform !== 'win32';

function loadEnvFile(envPath, examplePath) {
	if (!existsSync(envPath) && existsSync(examplePath)) {
		copyFileSync(examplePath, envPath);
	}
	if (!existsSync(envPath)) return {};
	const env = {};
	for (const rawLine of readFileSync(envPath, 'utf8').split(/\r?\n/)) {
		const line = rawLine.trim();
		if (!line || line.startsWith('#')) continue;
		const idx = line.indexOf('=');
		if (idx === -1) continue;
		const key = line.slice(0, idx).trim();
		const value = line.slice(idx + 1).trim().replace(/^['"]|['"]$/g, '');
		env[key] = value;
	}
	return env;
}

const apiEnv = loadEnvFile(
	resolve(root, 'apps/api/.env'),
	resolve(root, 'apps/api/.env.example')
);
const webEnv = loadEnvFile(
	resolve(root, 'apps/web/.env'),
	resolve(root, 'apps/web/.env.example')
);

const services = [
	{
		name: 'api',
		cwd: resolve(root, 'apps/api'),
		command: 'cargo',
		args: ['run', '--bin', 'paspapan-api'],
		env: { ...apiEnv, ...process.env }
	},
	{
		name: 'web',
		cwd: resolve(root, 'apps/web'),
		command: 'bun',
		args: ['run', 'dev'],
		env: { ...webEnv, ...process.env }
	}
];

const children = [];
let stopping = false;

function startService(service) {
	console.log(`Starting ${service.name} (${service.cwd}): ${service.command} ${service.args.join(' ')}`);

	const child = spawn(service.command, service.args, {
		cwd: service.cwd,
		stdio: 'inherit',
		detached: useProcessGroups,
		env: service.env
	});

	children.push({ ...service, child });

	child.once('error', (error) => {
		if (stopping) return;
		console.error(`\n${service.name} failed to start: ${error.message}`);
		shutdown(1);
	});

	child.once('exit', (code, signal) => {
		if (stopping) return;
		const exitCode = code ?? 1;
		const reason = signal ? `signal ${signal}` : `exit code ${exitCode}`;
		console.log(`\n${service.name} stopped with ${reason}.`);
		shutdown(exitCode);
	});
}

function stopChild(child) {
	if (child.exitCode !== null || child.signalCode !== null || typeof child.pid !== 'number') {
		return;
	}
	try {
		if (useProcessGroups) {
			process.kill(-child.pid, 'SIGTERM');
		} else {
			child.kill('SIGTERM');
		}
	} catch (error) {
		if (error.code !== 'ESRCH') {
			console.error(`Failed to stop process ${child.pid}: ${error.message}`);
		}
	}
}

function shutdown(code = 0) {
	if (stopping) return;
	stopping = true;
	if (children.length > 0) {
		console.log('\nStopping local services (api + web)...');
	}
	for (const { child } of children) {
		stopChild(child);
	}
	setTimeout(() => process.exit(code), 300);
}

process.once('SIGINT', () => shutdown(130));
process.once('SIGTERM', () => shutdown(143));
process.once('exit', () => {
	for (const { child } of children) {
		stopChild(child);
	}
});

for (const service of services) {
	startService(service);
}

console.log('');
console.log('Separated FE & BE dev services are running:');
console.log(`  API (BE): http://${apiEnv.API_BIND || '127.0.0.1:3000'}`);
console.log(`  Web (FE): http://${webEnv.WEB_HOST || '127.0.0.1'}:${webEnv.WEB_PORT || '4173'}`);
console.log('Press Ctrl+C to stop everything.');
