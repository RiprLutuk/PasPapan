export type AppearanceMode = 'system' | 'light' | 'dark';

export const APPEARANCE_KEY = 'paspapan:appearance';

export function storedAppearance(): AppearanceMode {
	if (typeof localStorage === 'undefined') return 'system';
	const value = localStorage.getItem(APPEARANCE_KEY);
	return value === 'light' || value === 'dark' ? value : 'system';
}

export function resolvedAppearance(mode: AppearanceMode): 'light' | 'dark' {
	if (mode !== 'system') return mode;
	return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function applyAppearance(mode: AppearanceMode) {
	const resolved = resolvedAppearance(mode);
	const root = document.documentElement;
	root.classList.add('theme-switching');
	root.dataset.theme = resolved;
	root.style.colorScheme = resolved;
	document.querySelector<HTMLMetaElement>('meta[name="theme-color"]')?.setAttribute('content', resolved === 'dark' ? '#0f1112' : '#44733a');
	localStorage.setItem(APPEARANCE_KEY, mode);
	requestAnimationFrame(() => requestAnimationFrame(() => root.classList.remove('theme-switching')));
}
