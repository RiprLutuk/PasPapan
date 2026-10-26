import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const scan = readFileSync(new URL('../src/routes/app/scan/+page.svelte', import.meta.url), 'utf8');
const requests = readFileSync(new URL('../src/lib/Requests.svelte', import.meta.url), 'utf8');

test('scan refines location from multiple high-accuracy samples and releases the watcher', () => {
	assert.match(scan, /geolocation\.watchPosition/);
	assert.match(scan, /geolocation\.clearWatch/);
	assert.match(scan, /coords\.accuracy\s*<\s*bestPosition\.coords\.accuracy/);
	assert.doesNotMatch(scan, /geolocation\.getCurrentPosition/);
});

test('requests uses the available desktop canvas instead of a narrow fixed list column', () => {
	assert.doesNotMatch(requests, /\.requests-page\s*\{[^}]*max-width:\s*680px/s);
	assert.match(requests, /grid-template-columns:\s*minmax\(280px,\s*\.72fr\)\s+minmax\(0,\s*1\.28fr\)/);
});
