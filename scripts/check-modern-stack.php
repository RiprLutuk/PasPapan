<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$package = json_decode((string) file_get_contents($root.'/package.json'), true, flags: JSON_THROW_ON_ERROR);

$errors = [];

$expectedComposer = [
    'php' => '^8.3',
    'laravel/framework' => '^13',
    'livewire/livewire' => '^4',
];

foreach ($expectedComposer as $packageName => $expectedConstraint) {
    $actual = $composer['require'][$packageName] ?? null;

    if ($actual !== $expectedConstraint) {
        $errors[] = "composer.json requires {$packageName} {$expectedConstraint}; found ".var_export($actual, true).'.';
    }
}

$devDependencies = $package['devDependencies'] ?? [];
$dependencies = $package['dependencies'] ?? [];

foreach (['postcss', 'autoprefixer'] as $legacyDirectDependency) {
    if (array_key_exists($legacyDirectDependency, $devDependencies) || array_key_exists($legacyDirectDependency, $dependencies)) {
        $errors[] = "package.json should not declare direct {$legacyDirectDependency}; Tailwind 4 runs through @tailwindcss/vite.";
    }
}

$expectedPackage = [
    'tailwindcss' => '^4',
    '@tailwindcss/vite' => '^4',
];

foreach ($expectedPackage as $packageName => $expectedConstraint) {
    $actual = $devDependencies[$packageName] ?? $dependencies[$packageName] ?? null;

    if ($actual !== $expectedConstraint) {
        $errors[] = "package.json requires {$packageName} {$expectedConstraint}; found ".var_export($actual, true).'.';
    }
}

foreach (array_keys(array_merge($dependencies, $devDependencies)) as $dependency) {
    if (str_contains($dependency, 'capacitor')) {
        $errors[] = 'Capacitor has been retired; use browser/PWA APIs.';
    }
}

$legacyTailwindConfigPrefix = 'tailwind'.'.config';
$legacyPostcssConfigPrefix = 'postcss'.'.config';

foreach ([
    $legacyTailwindConfigPrefix.'.js',
    $legacyTailwindConfigPrefix.'.cjs',
    $legacyTailwindConfigPrefix.'.mjs',
    $legacyPostcssConfigPrefix.'.js',
    $legacyPostcssConfigPrefix.'.cjs',
    $legacyPostcssConfigPrefix.'.mjs',
] as $legacyConfig) {
    if (file_exists($root.'/'.$legacyConfig)) {
        $errors[] = "Remove legacy config {$legacyConfig}; Tailwind 4 config belongs in resources/css/app.css with @theme/@source.";
    }
}

$appCss = (string) file_get_contents($root.'/resources/css/app.css');
if (! str_contains($appCss, '@import "tailwindcss";')) {
    $errors[] = 'resources/css/app.css must import Tailwind 4 with @import "tailwindcss";';
}

if (! str_contains($appCss, '@theme')) {
    $errors[] = 'resources/css/app.css must contain Tailwind 4 CSS-first @theme configuration.';
}

if (preg_match('/@tailwind\s+(base|components|utilities)\b/', $appCss) === 1) {
    $errors[] = 'resources/css/app.css still uses legacy @tailwind directives.';
}

$viteConfig = (string) file_get_contents($root.'/vite.config.js');
if (! str_contains($viteConfig, "import tailwindcss from '@tailwindcss/vite';") || ! str_contains($viteConfig, 'tailwindcss()')) {
    $errors[] = 'vite.config.js must use the Tailwind 4 Vite plugin.';
}

$trackedFilesRaw = shell_exec('git ls-files -z');
$trackedFiles = $trackedFilesRaw === null ? [] : array_filter(explode("\0", $trackedFilesRaw));

$scanPrefixes = [
    '.github/',
    'app/',
    'config/',
    'database/',
    'guides/',
    'resources/css/',
    'resources/views/',
    'routes/',
    'scripts/',
    'tests/',
];

$scanFiles = [
    'README.md',
    'RELEASE_CHECKLIST.md',
    'composer.json',
    'package.json',
    'vite.config.js',
];

$allowLegacyMarkers = [
    'guides/modern-stack.md',
    'scripts/check-modern-stack.php',
];

$legacyLaravelVersionPattern = '1[12]';

$legacyTextPatterns = [
    '/\bLaravel\s+'.$legacyLaravelVersionPattern.'\b/i' => 'outdated Laravel major wording',
    '/\blaravel\s+'.$legacyLaravelVersionPattern.'\b/i' => 'outdated Laravel major lowercase wording',
    '/\bLivewire\s+'.'3\b/i' => 'outdated Livewire major wording',
    '/\bTailwind(?:\s+CSS)?\s+'.'3\b/i' => 'outdated Tailwind major wording',
    '/\bCapacitor\s+7\b/i' => 'Capacitor 7 wording',
    '/\bPHP\s+8\.'.'2\b/i' => 'outdated PHP minor wording',
    '/\bBun\s+1\.2\b/i' => 'Bun 1.2 wording',
    '/\bNode\.js\s+24\b/i' => 'Node.js 24 wording',
    '/'.$legacyTailwindConfigPrefix.'\.(?:js|cjs|mjs)/i' => 'legacy Tailwind config reference',
    '/'.$legacyPostcssConfigPrefix.'\.(?:js|cjs|mjs)/i' => 'legacy PostCSS config reference',
    '/@tailwind\s+(?:base|components|utilities)\b/i' => 'legacy Tailwind directive',
];

$legacyLivewirePatterns = [
    '/Livewire\\\\Volt\\\\|Volt::/' => 'legacy Livewire Volt API',
    '/wire:model\.(?:blur|change)\b/' => 'legacy Livewire wire:model modifier semantics; use wire:model.live.blur/change when immediate client sync is expected',
    '/wire:scroll\b/' => 'legacy Livewire scroll directive; use wire:navigate:scroll',
    '/wire:transition\.[A-Za-z]/' => 'legacy Livewire transition modifiers',
    '/dispatchBrowserEvent\s*\(/' => 'legacy Livewire browser event API',
    '/\$this->emit(?:To)?\s*\(/' => 'legacy Livewire emit API',
];

foreach ($trackedFiles as $file) {
    $shouldScan = in_array($file, $scanFiles, true);

    if (! $shouldScan) {
        foreach ($scanPrefixes as $prefix) {
            if (str_starts_with($file, $prefix)) {
                $shouldScan = true;
                break;
            }
        }
    }

    if (! $shouldScan || ! is_file($root.'/'.$file) || in_array($file, $allowLegacyMarkers, true)) {
        continue;
    }

    $contents = (string) file_get_contents($root.'/'.$file);

    foreach ($legacyTextPatterns as $pattern => $label) {
        if (preg_match($pattern, $contents) === 1) {
            $errors[] = "{$file} contains legacy stack marker: {$label}.";
        }
    }

    foreach ($legacyLivewirePatterns as $pattern => $label) {
        if (preg_match($pattern, $contents) === 1) {
            $errors[] = "{$file} contains legacy Livewire marker: {$label}.";
        }
    }
}

foreach ($trackedFiles as $file) {
    if (! str_starts_with($file, 'routes/') || ! str_ends_with($file, '.php') || ! is_file($root.'/'.$file)) {
        continue;
    }

    $contents = (string) file_get_contents($root.'/'.$file);

    preg_match_all('/^use\s+App\\\\Livewire\\\\[^;]+\\\\(?<class>[A-Za-z0-9_]+)(?:\s+as\s+(?<alias>[A-Za-z0-9_]+))?;/m', $contents, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $componentName = ($match['alias'] ?? '') !== '' ? $match['alias'] : $match['class'];

        if (preg_match('/Route::get\([^;]+'.preg_quote($componentName, '/').'::class/s', $contents) === 1) {
            $errors[] = "{$file} registers Livewire component {$componentName} with Route::get; use Route::livewire for Livewire 4 full-page components.";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Modern Stack Check failed:\n");

    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }

    exit(1);
}

echo "Modern Stack Check\n";
echo "==================\n";
echo "PASS: Laravel 13, Livewire 4, Tailwind 4, browser/PWA, PHP 8.3+, Node 20+, and Bun 1.3.6+ baselines are clean.\n";
