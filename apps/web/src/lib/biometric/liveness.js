/** @typedef {'position' | 'blink' | 'turn_left' | 'turn_right' | 'steady' | 'complete'} LivenessStage */
/** @typedef {'await_open' | 'await_close' | 'await_reopen'} BlinkPhase */

/**
 * @typedef {object} LivenessState
 * @property {LivenessStage} stage
 * @property {number} progress
 * @property {string} prompt
 * @property {'user' | 'environment'} facingMode
 * @property {number} baselineLeftEAR
 * @property {number} baselineRightEAR
 * @property {number} baselineYaw
 * @property {number} positionFrames
 * @property {BlinkPhase} blinkPhase
 * @property {number} openFrames
 * @property {number} closedFrames
 * @property {number} reopenFrames
 * @property {number} turnLeftFrames
 * @property {number} turnRightFrames
 * @property {number} steadyFrames
 */

/**
 * @typedef {object} LivenessObservation
 * @property {number} leftEAR
 * @property {number} rightEAR
 * @property {number} yawRatio
 * @property {boolean} descriptorReady
 */

const REQUIRED_POSITION_FRAMES = 8;
const REQUIRED_OPEN_FRAMES = 3;
const REQUIRED_CLOSED_FRAMES = 2;
const REQUIRED_REOPEN_FRAMES = 2;
const REQUIRED_TURN_FRAMES = 3;
const REQUIRED_STEADY_FRAMES = 10;
const TURN_DELTA = 0.18;

/** @param {'user' | 'environment'} facingMode @returns {LivenessState} */
export function createLivenessState(facingMode = 'user') {
	return {
		stage: 'position',
		progress: 0,
		prompt: 'Posisikan wajah tegak dan buka mata secara natural.',
		facingMode,
		baselineLeftEAR: 0.25,
		baselineRightEAR: 0.25,
		baselineYaw: 1,
		positionFrames: 0,
		blinkPhase: 'await_open',
		openFrames: 0,
		closedFrames: 0,
		reopenFrames: 0,
		turnLeftFrames: 0,
		turnRightFrames: 0,
		steadyFrames: 0
	};
}

/** @param {LivenessState} state */
export function resetTransientEvidence(state) {
	if (state.stage === 'position') state.positionFrames = 0;
	if (state.stage === 'blink') {
		state.blinkPhase = 'await_open';
		state.openFrames = 0;
		state.closedFrames = 0;
		state.reopenFrames = 0;
	}
	if (state.stage === 'turn_left') state.turnLeftFrames = 0;
	if (state.stage === 'turn_right') state.turnRightFrames = 0;
	if (state.stage === 'steady') state.steadyFrames = 0;
}

/**
 * Advances only from consecutive, directionally valid observations.
 * There is deliberately no elapsed-time fallback: a challenge can only pass
 * after the requested motion is observed and then confirmed.
 * @param {LivenessState} state
 * @param {LivenessObservation} observation
 * @returns {'step' | 'complete' | null}
 */
export function advanceLiveness(state, observation) {
	const { leftEAR, rightEAR, yawRatio, descriptorReady } = observation;

	if (state.stage === 'position') {
		const eyesUsable = leftEAR >= 0.14 && rightEAR >= 0.14 && leftEAR <= 0.5 && rightEAR <= 0.5;
		const roughlyCentered = yawRatio >= 0.72 && yawRatio <= 1.4;
		if (!eyesUsable || !roughlyCentered) {
			state.positionFrames = 0;
			state.progress = 0;
			state.prompt = eyesUsable ? 'Hadapkan wajah lurus ke kamera.' : 'Buka kedua mata dan lihat ke kamera.';
			return null;
		}

		state.positionFrames++;
		const weight = state.positionFrames === 1 ? 1 : 0.24;
		state.baselineLeftEAR = state.positionFrames === 1 ? leftEAR : state.baselineLeftEAR * (1 - weight) + leftEAR * weight;
		state.baselineRightEAR = state.positionFrames === 1 ? rightEAR : state.baselineRightEAR * (1 - weight) + rightEAR * weight;
		state.baselineYaw = state.positionFrames === 1 ? yawRatio : state.baselineYaw * (1 - weight) + yawRatio * weight;
		state.progress = Math.min(20, Math.floor((state.positionFrames / REQUIRED_POSITION_FRAMES) * 20));
		state.prompt = 'Tahan wajah lurus untuk kalibrasi.';
		if (state.positionFrames >= REQUIRED_POSITION_FRAMES) {
			state.stage = 'blink';
			state.progress = 20;
			state.prompt = 'Lihat kamera, lalu kedipkan kedua mata sekali.';
			return 'step';
		}
		return null;
	}

	if (state.stage === 'blink') {
		const leftOpen = leftEAR >= state.baselineLeftEAR * 0.85;
		const rightOpen = rightEAR >= state.baselineRightEAR * 0.85;
		const bothOpen = leftOpen && rightOpen;
		const leftClosed = leftEAR <= Math.min(state.baselineLeftEAR * 0.72, state.baselineLeftEAR - 0.035);
		const rightClosed = rightEAR <= Math.min(state.baselineRightEAR * 0.72, state.baselineRightEAR - 0.035);
		const bothClosed = leftClosed && rightClosed;

		if (state.blinkPhase === 'await_open') {
			state.openFrames = bothOpen ? state.openFrames + 1 : 0;
			state.progress = 20 + Math.min(4, state.openFrames);
			state.prompt = 'Lihat kamera dengan kedua mata terbuka.';
			if (state.openFrames >= REQUIRED_OPEN_FRAMES) {
				state.blinkPhase = 'await_close';
				state.closedFrames = 0;
				state.prompt = 'Sekarang kedipkan kedua mata.';
			}
			return null;
		}

		if (state.blinkPhase === 'await_close') {
			state.closedFrames = bothClosed ? state.closedFrames + 1 : 0;
			state.progress = 24 + Math.min(8, state.closedFrames * 4);
			state.prompt = 'Kedipkan kedua mata sampai tertutup.';
			if (state.closedFrames >= REQUIRED_CLOSED_FRAMES) {
				state.blinkPhase = 'await_reopen';
				state.reopenFrames = 0;
				state.prompt = 'Buka kembali kedua mata.';
			}
			return null;
		}

		state.reopenFrames = bothOpen ? state.reopenFrames + 1 : 0;
		state.progress = 32 + Math.min(8, state.reopenFrames * 4);
		state.prompt = 'Buka kembali kedua mata.';
		if (state.reopenFrames >= REQUIRED_REOPEN_FRAMES) {
			state.stage = 'turn_left';
			state.progress = 40;
			state.prompt = 'Tengokkan kepala perlahan ke kiri.';
			return 'step';
		}
		return null;
	}

	if (state.stage === 'turn_left') {
		const yawDelta = yawRatio - state.baselineYaw;
		const valid = state.facingMode === 'user' ? yawDelta >= TURN_DELTA : yawDelta <= -TURN_DELTA;
		state.turnLeftFrames = valid ? state.turnLeftFrames + 1 : 0;
		state.progress = 40 + Math.min(20, Math.floor((state.turnLeftFrames / REQUIRED_TURN_FRAMES) * 20));
		state.prompt = valid ? 'Tahan sebentar di posisi kiri.' : 'Tengokkan kepala perlahan ke kiri.';
		if (state.turnLeftFrames >= REQUIRED_TURN_FRAMES) {
			state.stage = 'turn_right';
			state.progress = 60;
			state.prompt = 'Sekarang tengokkan kepala ke kanan.';
			return 'step';
		}
		return null;
	}

	if (state.stage === 'turn_right') {
		const yawDelta = yawRatio - state.baselineYaw;
		const valid = state.facingMode === 'user' ? yawDelta <= -TURN_DELTA : yawDelta >= TURN_DELTA;
		state.turnRightFrames = valid ? state.turnRightFrames + 1 : 0;
		state.progress = 60 + Math.min(20, Math.floor((state.turnRightFrames / REQUIRED_TURN_FRAMES) * 20));
		state.prompt = valid ? 'Tahan sebentar di posisi kanan.' : 'Tengokkan kepala perlahan ke kanan.';
		if (state.turnRightFrames >= REQUIRED_TURN_FRAMES) {
			state.stage = 'steady';
			state.progress = 80;
			state.prompt = 'Kembali hadap depan dan tahan posisi.';
			return 'step';
		}
		return null;
	}

	if (state.stage === 'steady') {
		const centered = Math.abs(yawRatio - state.baselineYaw) <= 0.1;
		const eyesOpen = leftEAR >= state.baselineLeftEAR * 0.78 && rightEAR >= state.baselineRightEAR * 0.78;
		const valid = centered && eyesOpen && descriptorReady;
		state.steadyFrames = valid ? state.steadyFrames + 1 : 0;
		state.progress = 80 + Math.min(20, Math.floor((state.steadyFrames / REQUIRED_STEADY_FRAMES) * 20));
		state.prompt = !centered
			? 'Kembali hadap lurus ke kamera.'
			: !eyesOpen
				? 'Buka mata dan lihat ke kamera.'
				: !descriptorReady
					? 'Menyiapkan verifikasi identitas…'
					: 'Tahan posisi tanpa bergerak.';
		if (state.steadyFrames >= REQUIRED_STEADY_FRAMES) {
			state.stage = 'complete';
			state.progress = 100;
			state.prompt = 'Verifikasi gerakan selesai.';
			return 'complete';
		}
	}

	return null;
}
