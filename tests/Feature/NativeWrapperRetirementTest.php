<?php

use Illuminate\Support\Facades\Route;

test('retired native wrappers and dependencies cannot return accidentally', function () {
    foreach (['android', 'ios', 'capacitor-www', 'capacitor.config.ts', '.github/workflows/apk-smoke.yml', '.github/workflows/ios-preflight.yml'] as $path) {
        expect(file_exists(base_path($path)))->toBeFalse();
    }

    $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    foreach (array_keys(array_merge($package['dependencies'], $package['devDependencies'])) as $dependency) {
        expect($dependency)->not->toContain('capacitor');
    }
    foreach (array_keys($package['scripts']) as $script) {
        expect($script)->not->toMatch('/^(apk:|ios:|screenshots:apk)/');
    }
});

test('native only document upload helper is no longer routed', function () {
    expect(collect(Route::getRoutes()->getRoutes())->first(
        fn ($route) => $route->uri() === '__e2e-document-upload',
    ))->toBeNull();
});

test('legacy browser pwa assets remain available during migration', function () {
    foreach (['manifest.json', 'sw.js', 'offline.html', 'images/icons/icon-192x192.png', 'images/icons/icon-512x512.png'] as $path) {
        expect(is_file(public_path($path)))->toBeTrue();
    }
});
