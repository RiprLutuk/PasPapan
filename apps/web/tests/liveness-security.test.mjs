import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { advanceLiveness, createLivenessState } from '../src/lib/biometric/liveness.js';

const source = await readFile(new URL('../src/lib/FaceEnrollment.svelte', import.meta.url), 'utf8');

test('blink cannot pass from elapsed time without a verified reopen', () => {
	assert.doesNotMatch(source, /blinkObserved\s*&&\s*stageTicks\s*>=/);
});

test('head turns are measured from the calibrated baseline only', () => {
	assert.doesNotMatch(source, /yawDelta\s*>\s*0\.14\s*\|\|\s*yawRatio/);
	assert.doesNotMatch(source, /yawDelta\s*<\s*-0\.14\s*\|\|\s*yawRatio/);
});

const observe = (state, overrides = {}) => advanceLiveness(state, {
	leftEAR: 0.28,
	rightEAR: 0.28,
	yawRatio: 1,
	descriptorReady: true,
	...overrides
});

const calibrate = (state) => {
	for (let frame = 0; frame < 8; frame++) observe(state);
	assert.equal(state.stage, 'blink');
};

test('an unmoving open-eyed face never passes the blink challenge', () => {
	const state = createLivenessState('user');
	calibrate(state);
	for (let frame = 0; frame < 100; frame++) observe(state);
	assert.equal(state.stage, 'blink');
	assert.equal(state.blinkPhase, 'await_close');
});

test('closed eyes alone never pass without open-close-open sequence', () => {
	const state = createLivenessState('user');
	calibrate(state);
	for (let frame = 0; frame < 100; frame++) observe(state, { leftEAR: 0.07, rightEAR: 0.07 });
	assert.equal(state.stage, 'blink');
	assert.equal(state.blinkPhase, 'await_open');
});

test('blink and head turns require consecutive verified motion', () => {
	const state = createLivenessState('user');
	calibrate(state);
	for (let frame = 0; frame < 3; frame++) observe(state);
	for (let frame = 0; frame < 2; frame++) observe(state, { leftEAR: 0.07, rightEAR: 0.07 });
	for (let frame = 0; frame < 2; frame++) observe(state);
	assert.equal(state.stage, 'turn_left');

	for (let frame = 0; frame < 30; frame++) observe(state);
	assert.equal(state.stage, 'turn_left');
	for (let frame = 0; frame < 3; frame++) observe(state, { yawRatio: 1.2 });
	assert.equal(state.stage, 'turn_right');
	for (let frame = 0; frame < 30; frame++) observe(state);
	assert.equal(state.stage, 'turn_right');
	for (let frame = 0; frame < 3; frame++) observe(state, { yawRatio: 0.8 });
	assert.equal(state.stage, 'steady');
});

test('interrupted turn evidence is discarded instead of accumulated', () => {
	const state = createLivenessState('user');
	calibrate(state);
	for (let frame = 0; frame < 3; frame++) observe(state);
	for (let frame = 0; frame < 2; frame++) observe(state, { leftEAR: 0.07, rightEAR: 0.07 });
	for (let frame = 0; frame < 2; frame++) observe(state);

	for (let frame = 0; frame < 2; frame++) observe(state, { yawRatio: 1.2 });
	observe(state, { yawRatio: 1 });
	for (let frame = 0; frame < 2; frame++) observe(state, { yawRatio: 1.2 });
	assert.equal(state.stage, 'turn_left');
	observe(state, { yawRatio: 1.2 });
	assert.equal(state.stage, 'turn_right');
});
