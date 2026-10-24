<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

test('modern stack guard blocks stale framework and frontend markers', function () {
    $blockedMarkers = [
        'Laravel '.(10 + 1),
        'Laravel '.(10 + 2),
        'Livewire '.(1 + 2),
        'Tailwind '.(1 + 2),
    ];

    $allowedFiles = [
        'tests/Feature/ModernStackGuardTest.php',
        'scripts/check-modern-stack.php',
    ];

    foreach (trackedProjectFiles() as $file) {
        if (! shouldScanModernStackFile($file) || in_array($file, $allowedFiles, true)) {
            continue;
        }

        $contents = file_get_contents(base_path($file));

        foreach ($blockedMarkers as $marker) {
            expect($contents)->not->toContain($marker, "{$file} contains {$marker}");
        }
    }
});

test('full page livewire routes use livewire aliases instead of class route mounts', function () {
    foreach (trackedProjectFiles('routes/') as $file) {
        if (! str_ends_with($file, '.php')) {
            continue;
        }

        $contents = file_get_contents(base_path($file));

        expect($contents)->not->toContain('use App\\Livewire\\', "{$file} imports Livewire page classes")
            ->and($contents)->not->toMatch('/Route::livewire\(\s*[^,]+,\s*[^,)]+::class/s', "{$file} mounts Livewire with ::class");
    }
});

test('route files keep business logic in controllers or route macros', function () {
    foreach (trackedProjectFiles('routes/') as $file) {
        if (! str_ends_with($file, '.php')) {
            continue;
        }

        $contents = file_get_contents(base_path($file));

        expect($contents)
            ->not->toMatch('/Route::(?:get|post|put|patch|delete|match|any)\([^;]+function\s*\(/s', "{$file} contains an inline route closure")
            ->not->toMatch('/Route::(?:get|post|put|patch|delete|match|any)\([^;]+fn\s*\(/s', "{$file} contains an inline route arrow function");
    }
});

test('tailwind four css first setup has no legacy config files or directives', function () {
    $legacyConfigFiles = [
        'tailwind'.'.config.js',
        'tailwind'.'.config.cjs',
        'tailwind'.'.config.mjs',
        'postcss'.'.config.js',
        'postcss'.'.config.cjs',
        'postcss'.'.config.mjs',
    ];

    foreach ($legacyConfigFiles as $file) {
        expect(file_exists(base_path($file)))->toBeFalse("{$file} should not exist");
    }

    $css = file_get_contents(resource_path('css/app.css'));
    $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    $devDependencies = $package['devDependencies'] ?? [];
    $tailwindPrefix = '@import "tailwindcss";'."\n"
        .'@import "flatpickr/dist/flatpickr.css";'."\n"
        .'@plugin "@tailwindcss/forms";'."\n"
        .'@plugin "@tailwindcss/typography";'."\n";

    expect($css)
        ->toContain('@import "tailwindcss";')
        ->toStartWith($tailwindPrefix)
        ->toContain('@source "../views/**/*.blade.php";')
        ->toContain('@source "../js/**/*.js";')
        ->toContain('@source "../js/**/*.ts";')
        ->toContain('@theme')
        ->not->toMatch('/@tailwind\s+(base|components|utilities)\b/')
        ->and(array_key_exists('@tailwindcss/vite', $devDependencies))->toBeTrue()
        ->and(array_key_exists('post'.'css', $devDependencies))->toBeFalse()
        ->and(array_key_exists('auto'.'prefixer', $devDependencies))->toBeFalse();
});

test('laravel thirteen and livewire four upgrade configuration stays current', function () {
    $deprecatedMiddlewareMarkers = [
        'Verify'.'CsrfToken',
        'Validate'.'CsrfToken',
    ];

    foreach (trackedProjectFiles() as $file) {
        if (! shouldScanModernStackFile($file) || $file === 'tests/Feature/ModernStackGuardTest.php') {
            continue;
        }

        $contents = file_get_contents(base_path($file));

        foreach ($deprecatedMiddlewareMarkers as $marker) {
            expect($contents)->not->toContain($marker, "{$file} references deprecated CSRF middleware {$marker}");
        }
    }

    $vercelRoute = collect(Route::getRoutes())
        ->first(fn ($route): bool => $route->uri() === '__vercel-migrate' && in_array('POST', $route->methods(), true));

    $excludedMiddleware = $vercelRoute !== null && method_exists($vercelRoute, 'excludedMiddleware')
        ? $vercelRoute->excludedMiddleware()
        : [];

    expect(config('sanctum.middleware.validate_csrf_token'))->toBe(PreventRequestForgery::class)
        ->and($excludedMiddleware)->toContain(PreventRequestForgery::class)
        ->and(config('cache.serializable_classes'))->toBeFalse()
        ->and(config('livewire.component_layout'))->toBe('layouts::app')
        ->and(config('livewire.component_placeholder'))->toBeNull()
        ->and(config('livewire.smart_wire_keys'))->toBeTrue()
        ->and(config('livewire.csp_safe'))->toBeFalse()
        ->and(config('livewire.component_locations'))->toContain(resource_path('views/livewire'))
        ->and(config('livewire.component_namespaces'))->toHaveKey('layouts')
        ->and(config('livewire.make_command.type'))->toBe('class')
        ->and(config('livewire.make_command.with.test'))->toBeTrue()
        ->and(config('livewire.temporary_file_upload.rules'))->toBe('file|max:12288')
        ->and(config('livewire.temporary_file_upload.directory'))->toBe('livewire-tmp')
        ->and(config('livewire.temporary_file_upload.middleware'))->toBe('throttle:60,1')
        ->and(config('livewire.temporary_file_upload.cleanup'))->toBeTrue();

    $exampleEnv = file_get_contents(base_path('.env.example'));

    expect($exampleEnv)
        ->toContain('CACHE_PREFIX=paspapan_cache_')
        ->toContain('REDIS_PREFIX=paspapan_database_')
        ->toContain('SESSION_COOKIE=paspapan_session')
        ->toContain('LIVEWIRE_TEMPORARY_FILE_UPLOAD_RULES=file|max:12288')
        ->toContain('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DIRECTORY=livewire-tmp')
        ->toContain('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MIDDLEWARE=throttle:60,1');
});

test('blade views use livewire four component tags and tailwind four safe utilities', function () {
    foreach (trackedProjectFiles('resources/views/') as $file) {
        if (! str_ends_with($file, '.blade.php')) {
            continue;
        }

        $contents = file_get_contents(base_path($file));

        expect($contents)
            ->not->toContain('@livewire(', "{$file} should use Livewire component tags instead of @livewire directives")
            ->not->toContain('flex-shrink-0', "{$file} should use Tailwind 4 shrink-0 utility")
            ->not->toContain('ring-opacity-', "{$file} should use slash opacity utilities");
    }
});

test('feature lock modal backdrop stays behind the interactive form', function () {
    $contents = file_get_contents(resource_path('views/components/shared/feature-lock-modal.blade.php'));

    expect($contents)
        ->toContain('fixed inset-0 z-0')
        ->toContain('absolute inset-0 z-0')
        ->toContain('relative z-10 mx-auto')
        ->toContain('normalizeWhatsapp(value)')
        ->toContain('isValidDomain(value)')
        ->toContain('x-bind:disabled="!isFormValid()"')
        ->toContain('x-on:blur="touch(\'email\')"')
        ->toContain('x-on:blur="touch(\'whatsapp\')"')
        ->toContain('x-show="showError(\'jumlahKaryawan\')"')
        ->not->toMatch('/x-model="(?:nama|email|perusahaan|whatsapp|domain|jumlahKaryawan|catatan)"[^>]*\sdisabled\b/');
});

test('pull to refresh uses animated pill surface and safe mobile guards', function () {
    $script = file_get_contents(public_path('js/pulltorefresh.js'));
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($script)
        ->toContain('@keyframes __PREFIX__pill')
        ->toContain('calc(var(--ptr-progress) * 100%)')
        ->toContain('.__PREFIX__refresh .__PREFIX__surface')
        ->toContain('data-ptr-state')
        ->toContain('Pull to sync this page')
        ->not->toContain('__PREFIX__spinner')
        ->not->toContain('conic-gradient')
        ->not->toContain('__PREFIX__rail-fill')
        ->and($layout)
        ->toContain('PullToRefresh.init')
        ->toContain('is-native-scanning')
        ->toContain('document.body.dataset.pullToRefreshReady')
        ->toContain('hasVisibleDialog')
        ->toContain('@js(__(\'Pull to sync this page\'))');
});

/**
 * @return list<string>
 */
function trackedProjectFiles(?string $prefix = null): array
{
    $output = shell_exec('git ls-files');
    $files = $output === null ? [] : array_values(array_filter(
        explode("\n", $output),
        fn (string $file): bool => $file !== '' && is_file(base_path($file)),
    ));

    if ($prefix === null) {
        return $files;
    }

    return array_values(array_filter($files, fn (string $file): bool => Str::startsWith($file, $prefix)));
}

function shouldScanModernStackFile(string $file): bool
{
    if (in_array($file, ['README.md', 'RELEASE_CHECKLIST.md', 'composer.json', 'package.json', 'vite.config.js'], true)) {
        return true;
    }

    foreach (['.github/', 'app/', 'config/', 'database/', 'guides/', 'resources/css/', 'resources/views/', 'routes/', 'scripts/', 'tests/'] as $prefix) {
        if (Str::startsWith($file, $prefix)) {
            return true;
        }
    }

    return false;
}
