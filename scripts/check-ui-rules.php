#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);

chdir($root);

$whitelistEntries = loadWhitelist($root.'/scripts/ui-rule-whitelist.php');
$whitelistHitCount = 0;

$blockingFindings = [];
$warningFindings = [];
$baselinedWarningCount = 0;
$warningCaps = [];
$maxWarningsPerFile = [
    'hardcoded_ui_text' => 3,
    'missing_form_label' => 3,
    'icon_only_button_accessibility' => 3,
    'mobile_layout_red_flag' => 5,
];

$translationUsage = [];

$idTranslations = loadJsonTranslations($root.'/lang/id.json');
$enTranslations = loadJsonTranslations($root.'/lang/en.json');

$bladeFiles = findFiles($root.'/resources/views', static fn (string $path): bool => str_ends_with($path, '.blade.php'));
$livewireFiles = findFiles($root.'/app/Livewire', static fn (string $path): bool => str_ends_with($path, '.php') && ! str_ends_with($path, '.Source.php'));

foreach ($bladeFiles as $file) {
    $relativePath = relativePath($root, $file);
    $content = (string) file_get_contents($file);
    $isNormalUiBlade = isNormalUiBladeFile($relativePath);

    if ($isNormalUiBlade) {
        if (preg_match_all('/<svg\b/i', $content, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $firstOffset = $matches[0][0][1];
            if (! isWhitelisted($whitelistEntries, 'raw_inline_svg', $relativePath, null, $whitelistHitCount)) {
                $blockingFindings[] = makeFinding(
                    'error',
                    'raw_inline_svg',
                    $relativePath,
                    lineNumberFromOffset($content, $firstOffset),
                    'Raw inline <svg> found. Use a Heroicon component or add an explicit whitelist entry.',
                );
            }
        }

        if (preg_match_all('/<table\b/i', $content, $matches, PREG_OFFSET_CAPTURE) > 0 && ! isAllowedTablePath($relativePath)) {
            $firstOffset = $matches[0][0][1];
            if (! isWhitelisted($whitelistEntries, 'table_usage', $relativePath, null, $whitelistHitCount)) {
                $blockingFindings[] = makeFinding(
                    'error',
                    'table_usage',
                    $relativePath,
                    lineNumberFromOffset($content, $firstOffset),
                    'Table markup found in normal app UI. Prefer card or stacked mobile layouts.',
                );
            }
        }

        collectHardcodedTextWarnings($content, $relativePath, $warningFindings, $warningCaps, $maxWarningsPerFile);
        collectFormAccessibilityWarnings($content, $relativePath, $warningFindings, $warningCaps, $maxWarningsPerFile);
        collectIconButtonWarnings($content, $relativePath, $warningFindings, $warningCaps, $maxWarningsPerFile);
        collectMobileLayoutWarnings($content, $relativePath, $warningFindings, $warningCaps, $maxWarningsPerFile);
    }

    collectTranslationKeyUsage($content, $relativePath, $translationUsage);
}

foreach ($livewireFiles as $file) {
    $relativePath = relativePath($root, $file);
    $content = (string) file_get_contents($file);

    collectTranslationKeyUsage($content, $relativePath, $translationUsage);
    collectLivewireHardcodedTextWarnings($content, $relativePath, $warningFindings, $warningCaps, $maxWarningsPerFile);
}

foreach ($translationUsage as $key => $locations) {
    $missingLanguages = [];

    if (! array_key_exists($key, $idTranslations)) {
        $missingLanguages[] = 'id';
    }

    if (! array_key_exists($key, $enTranslations)) {
        $missingLanguages[] = 'en';
    }

    if ($missingLanguages === []) {
        continue;
    }

    if (isWhitelisted($whitelistEntries, 'translation_key_missing', $locations[0]['file'], $key, $whitelistHitCount)) {
        continue;
    }

    $blockingFindings[] = makeFinding(
        'error',
        'translation_key_missing',
        $locations[0]['file'],
        $locations[0]['line'],
        sprintf(
            'Translation key "%s" is missing in lang/%s.json.',
            $key,
            implode(' + lang/', $missingLanguages),
        ),
    );
}

$missingInEnglish = array_values(array_diff(array_keys($idTranslations), array_keys($enTranslations)));

if ($missingInEnglish !== []) {
    $warningFindings[] = makeFinding(
        'warning',
        'translation_file_drift',
        'lang/en.json',
        1,
        sprintf(
            'Translation file drift detected: %d key(s) exist in lang/id.json but not in lang/en.json. Blocking enforcement currently targets UI-used literal keys.',
            count($missingInEnglish),
        ),
    );
}

$warningFindings = filterWhitelistedWarnings($warningFindings, $whitelistEntries, $whitelistHitCount, $baselinedWarningCount);

usort($blockingFindings, 'compareFindings');
usort($warningFindings, 'compareFindings');

$totalBlocking = count($blockingFindings);
$totalWarnings = count($warningFindings);

echo 'UI Rules Check'.PHP_EOL;
echo '=============='.PHP_EOL;
echo sprintf('Scanned %d Blade file(s) and %d Livewire file(s).', count($bladeFiles), count($livewireFiles)).PHP_EOL;
echo sprintf('Whitelist entries loaded: %d. Matched: %d.', count($whitelistEntries), $whitelistHitCount).PHP_EOL;
echo PHP_EOL;

if ($totalBlocking > 0) {
    echo 'Blocking Issues'.PHP_EOL;
    echo '---------------'.PHP_EOL;

    foreach ($blockingFindings as $finding) {
        echo formatFinding($finding).PHP_EOL;
    }

    echo PHP_EOL;
}

if ($totalWarnings > 0) {
    echo 'Warnings'.PHP_EOL;
    echo '--------'.PHP_EOL;

    foreach ($warningFindings as $finding) {
        echo formatFinding($finding).PHP_EOL;
    }

    echo PHP_EOL;
}

if ($baselinedWarningCount > 0) {
    echo sprintf('Baselined Warnings: %d existing warning(s) matched the exact legacy baseline.', $baselinedWarningCount).PHP_EOL;
    echo PHP_EOL;
}

if ($totalBlocking === 0) {
    echo sprintf('PASS: no blocking UI rule violations found. %d active warning(s).', $totalWarnings).PHP_EOL;
} else {
    echo sprintf('FAIL: %d blocking issue(s) found and %d active warning(s).', $totalBlocking, $totalWarnings).PHP_EOL;
}

echo PHP_EOL;
echo 'How to fix'.PHP_EOL;
echo '----------'.PHP_EOL;
echo '- Replace raw inline SVG with Heroicon Blade components.'.PHP_EOL;
echo '- Replace mobile-facing tables with cards or stacked layouts when possible.'.PHP_EOL;
echo '- Add missing keys to both lang/id.json and lang/en.json.'.PHP_EOL;
echo '- Add labels, aria-labels, or responsive classes where warnings point to likely issues.'.PHP_EOL;
echo '- If an exception is intentional, document it in scripts/ui-rule-whitelist.php.'.PHP_EOL;

exit($totalBlocking > 0 ? 1 : 0);

function loadWhitelist(string $path): array
{
    if (! file_exists($path)) {
        return [];
    }

    $entries = require $path;

    if (! is_array($entries)) {
        fwrite(STDERR, "Whitelist file must return an array.\n");
        exit(1);
    }

    return array_values(array_filter($entries, static fn (mixed $entry): bool => is_array($entry) && isset($entry['rule'])));
}

function loadJsonTranslations(string $path): array
{
    $json = file_get_contents($path);

    if ($json === false) {
        fwrite(STDERR, sprintf("Unable to read translation file: %s\n", $path));
        exit(1);
    }

    try {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fwrite(STDERR, sprintf("Invalid JSON in %s: %s\n", $path, $exception->getMessage()));
        exit(1);
    }

    if (! is_array($decoded)) {
        fwrite(STDERR, sprintf("Translation file must decode to an array: %s\n", $path));
        exit(1);
    }

    return $decoded;
}

function findFiles(string $directory, callable $filter): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());

        if ($filter($path)) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

function relativePath(string $root, string $path): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $path = str_replace('\\', '/', $path);

    return ltrim(substr($path, strlen($root)), '/');
}

function isNormalUiBladeFile(string $relativePath): bool
{
    return ! matchesAnyPattern($relativePath, [
        'resources/views/vendor/mail/*',
        'resources/views/emails/*',
        'resources/views/pdf/*',
    ]);
}

function isAllowedTablePath(string $relativePath): bool
{
    return matchesAnyPattern($relativePath, [
        'resources/views/vendor/mail/*',
        'resources/views/emails/*',
        'resources/views/pdf/*',
        'resources/views/admin/import-export/export-*.blade.php',
        'resources/views/admin/reports/*',
        'resources/views/admin/attendances/report.blade.php',
    ]);
}

function matchesAnyPattern(string $path, array $patterns): bool
{
    foreach ($patterns as $pattern) {
        if (fnmatch($pattern, $path)) {
            return true;
        }
    }

    return false;
}

function collectTranslationKeyUsage(string $content, string $relativePath, array &$translationUsage): void
{
    $patterns = [
        '/__\(\s*\'((?:\\\\.|[^\'])*)\'/s',
        '/__\(\s*"((?:\\\\.|[^"])*)"/s',
        '/@lang\(\s*\'((?:\\\\.|[^\'])*)\'/s',
        '/@lang\(\s*"((?:\\\\.|[^"])*)"/s',
        '/trans(?:_choice)?\(\s*\'((?:\\\\.|[^\'])*)\'/s',
        '/trans(?:_choice)?\(\s*"((?:\\\\.|[^"])*)"/s',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
            continue;
        }

        foreach ($matches[1] as [$rawKey, $offset]) {
            $key = stripcslashes($rawKey);
            $translationUsage[$key] ??= [];
            $translationUsage[$key][] = [
                'file' => $relativePath,
                'line' => lineNumberFromOffset($content, $offset),
            ];
        }
    }
}

function collectHardcodedTextWarnings(string $content, string $relativePath, array &$warningFindings, array &$warningCaps, array $maxWarningsPerFile): void
{
    $sanitized = preg_replace('/<script\b.*?<\/script>/is', '', $content) ?? $content;
    $sanitized = preg_replace('/<style\b.*?<\/style>/is', '', $sanitized) ?? $sanitized;
    $sanitized = preg_replace('/\{\{--.*?--\}\}/s', '', $sanitized) ?? $sanitized;
    $sanitized = preg_replace('/<!--.*?-->/s', '', $sanitized) ?? $sanitized;

    if (preg_match_all('/>([^<]+)</s', $sanitized, $matches, PREG_OFFSET_CAPTURE) !== false) {
        foreach ($matches[1] as [$segment, $offset]) {
            $text = normalizeCandidateText($segment);

            if (! looksLikeHardcodedUiText($text)) {
                continue;
            }

            addCappedWarning(
                $warningFindings,
                $warningCaps,
                $maxWarningsPerFile,
                'hardcoded_ui_text',
                $relativePath,
                lineNumberFromOffset($sanitized, $offset),
                sprintf('Possible hardcoded UI text: "%s"', clipText($text)),
            );
        }
    }

    if (preg_match_all('/\b(?:placeholder|title|alt|aria-label)\s*=\s*(["\'])(.*?)\1/is', $sanitized, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) !== false) {
        foreach ($matches as $match) {
            $value = normalizeCandidateText($match[2][0]);

            if (! looksLikeHardcodedUiText($value)) {
                continue;
            }

            addCappedWarning(
                $warningFindings,
                $warningCaps,
                $maxWarningsPerFile,
                'hardcoded_ui_text',
                $relativePath,
                lineNumberFromOffset($sanitized, $match[2][1]),
                sprintf('Possible hardcoded UI attribute text: "%s"', clipText($value)),
            );
        }
    }
}

function collectLivewireHardcodedTextWarnings(string $content, string $relativePath, array &$warningFindings, array &$warningCaps, array $maxWarningsPerFile): void
{
    $patterns = [
        '/session\(\)->flash\([^,]+,\s*(["\'])([^"\']*[A-Za-z][^"\']*)\1/s',
        '/->dispatch\([^;]*(?:message|title|text|description)\s*:\s*(["\'])([^"\']*[A-Za-z][^"\']*)\1/s',
        '/->addError\([^,]+,\s*(["\'])([^"\']*[A-Za-z][^"\']*)\1/s',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            continue;
        }

        foreach ($matches as $match) {
            $snippet = normalizeCandidateText($match[2][0]);

            if (! looksLikeHardcodedUiText($snippet)) {
                continue;
            }

            addCappedWarning(
                $warningFindings,
                $warningCaps,
                $maxWarningsPerFile,
                'hardcoded_ui_text',
                $relativePath,
                lineNumberFromOffset($content, $match[2][1]),
                sprintf('Possible hardcoded Livewire UI text: "%s"', clipText($snippet)),
            );
        }
    }
}

function collectFormAccessibilityWarnings(string $content, string $relativePath, array &$warningFindings, array &$warningCaps, array $maxWarningsPerFile): void
{
    if (str_starts_with($relativePath, 'resources/views/components/forms/')) {
        return;
    }

    if (preg_match_all('/<(input|select|textarea)\b[^>]*>/is', $content, $matches, PREG_OFFSET_CAPTURE) === false) {
        return;
    }

    foreach ($matches[0] as [$tagMarkup, $offset]) {
        $tag = strtolower((string) preg_replace('/^<([a-z]+).*/is', '$1', $tagMarkup));

        if ($tag === 'input') {
            $type = extractAttributeValue($tagMarkup, 'type');

            if (in_array($type, ['hidden', 'submit', 'button', 'reset', 'image'], true)) {
                continue;
            }
        }

        if (
            str_contains($tagMarkup, 'aria-label=') ||
            str_contains($tagMarkup, 'aria-labelledby=')
        ) {
            continue;
        }

        $id = extractAttributeValue($tagMarkup, 'id');
        $nearbyContext = substr($content, max(0, $offset - 600), 1200) ?: '';

        if ($id !== null && preg_match('/<(?:label|x-forms\.label|x-label|x-input-label)\b[^>]*\bfor=["\']'.preg_quote($id, '/').'["\']/i', $nearbyContext) === 1) {
            continue;
        }

        if (preg_match('/<label\b/i', $nearbyContext) === 1 || preg_match('/<legend\b/i', $nearbyContext) === 1) {
            continue;
        }

        addCappedWarning(
            $warningFindings,
            $warningCaps,
            $maxWarningsPerFile,
            'missing_form_label',
            $relativePath,
            lineNumberFromOffset($content, $offset),
            sprintf('Possible missing label or aria-label for <%s>.', $tag),
        );
    }
}

function collectIconButtonWarnings(string $content, string $relativePath, array &$warningFindings, array &$warningCaps, array $maxWarningsPerFile): void
{
    if (preg_match_all('/<button\b([^>]*)>(.*?)<\/button>/is', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
        return;
    }

    foreach ($matches as $match) {
        $attributes = $match[1][0];
        $body = $match[2][0];

        if (
            str_contains($attributes, 'aria-label=') ||
            str_contains($attributes, 'aria-labelledby=') ||
            str_contains($attributes, 'title=')
        ) {
            continue;
        }

        $bodyWithoutBlade = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@[\w:-]+(?:\(.*?\))?/s', ' ', $body) ?? $body;
        $visibleText = trim(strip_tags($bodyWithoutBlade));
        $visibleText = preg_replace('/\s+/', ' ', $visibleText) ?? $visibleText;

        if ($visibleText !== '' && preg_match('/[A-Za-z]/', $visibleText) === 1) {
            continue;
        }

        $hasIconMarkup = preg_match('/<(svg|x-heroicon|x-dynamic-component)\b/i', $body) === 1;

        if (! $hasIconMarkup) {
            continue;
        }

        addCappedWarning(
            $warningFindings,
            $warningCaps,
            $maxWarningsPerFile,
            'icon_only_button_accessibility',
            $relativePath,
            lineNumberFromOffset($content, $match[0][1]),
            'Possible icon-only button without aria-label or visible text.',
        );
    }
}

function collectMobileLayoutWarnings(string $content, string $relativePath, array &$warningFindings, array &$warningCaps, array $maxWarningsPerFile): void
{
    $rules = [
        '/(?:^|[\s"\'])min-w-\[(?:[5-9]\d{2}|1\d{3,})px\]/m' => 'Large fixed min-width may cause horizontal scroll on mobile.',
        '/(?:^|[\s"\'])w-\[(?:[5-9]\d{2}|1\d{3,})px\]/m' => 'Large fixed width may break mobile layouts.',
        '/(?:^|[^:\w-])grid-cols-(?:4|5|6|7|8|9|10|11|12)\b/m' => 'High default grid column count may be too dense for mobile.',
        '/(?:^|[\s"\'])overflow-x-auto(?:$|[\s"\'])/m' => 'overflow-x-auto can hide underlying mobile layout issues.',
    ];

    foreach ($rules as $pattern => $message) {
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
            continue;
        }

        foreach ($matches[0] as [$match, $offset]) {
            addCappedWarning(
                $warningFindings,
                $warningCaps,
                $maxWarningsPerFile,
                'mobile_layout_red_flag',
                $relativePath,
                lineNumberFromOffset($content, $offset),
                $message,
            );
        }
    }
}

function normalizeCandidateText(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);

    return $text;
}

function looksLikeHardcodedUiText(string $text): bool
{
    if ($text === '') {
        return false;
    }

    if (
        str_contains($text, '{{') ||
        str_contains($text, '{!!') ||
        str_contains($text, '__(') ||
        str_contains($text, '@lang(') ||
        str_contains($text, 'trans(') ||
        str_contains($text, 'route(') ||
        str_contains($text, 'wire:') ||
        str_contains($text, 'x-')
    ) {
        return false;
    }

    if (
        preg_match('/[@$]/', $text) === 1 ||
        preg_match('/(->|=>|::|\\\\)/', $text) === 1 ||
        preg_match('/[()[\]{}]/', $text) === 1 ||
        str_contains($text, '.blade.php')
    ) {
        return false;
    }

    if (preg_match('/\b(?:session|route|config|request|public_path|file_exists|collect|array_map|Carbon|Auth|Laravel|merge)\b/', $text) === 1) {
        return false;
    }

    if (preg_match('/[A-Za-z]/', $text) !== 1) {
        return false;
    }

    if (preg_match('/^[#.:_\/@\-+0-9\s]+$/', $text) === 1) {
        return false;
    }

    if (preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)$/i', $text) === 1) {
        return false;
    }

    return true;
}

function extractAttributeValue(string $markup, string $attribute): ?string
{
    if (preg_match('/\b'.preg_quote($attribute, '/').'\s*=\s*(["\'])(.*?)\1/is', $markup, $matches) !== 1) {
        return null;
    }

    return trim($matches[2]);
}

function addCappedWarning(array &$warningFindings, array &$warningCaps, array $maxWarningsPerFile, string $rule, string $file, int $line, string $message): void
{
    $capKey = $rule.'|'.$file;
    $warningCaps[$capKey] ??= 0;
    $cap = $maxWarningsPerFile[$rule] ?? PHP_INT_MAX;

    if ($warningCaps[$capKey] >= $cap) {
        return;
    }

    $warningCaps[$capKey]++;
    $warningFindings[] = makeFinding('warning', $rule, $file, $line, $message);
}

function filterWhitelistedWarnings(array $warningFindings, array $whitelistEntries, int &$whitelistHitCount, int &$baselinedWarningCount): array
{
    $filtered = [];

    foreach ($warningFindings as $finding) {
        if (isWhitelisted($whitelistEntries, $finding['rule'], $finding['file'], warningWhitelistKey($finding), $whitelistHitCount)) {
            $baselinedWarningCount++;

            continue;
        }

        $filtered[] = $finding;
    }

    return $filtered;
}

function warningWhitelistKey(array $finding): string
{
    return sprintf('%d|%s', $finding['line'], $finding['message']);
}

function makeFinding(string $severity, string $rule, string $file, int $line, string $message): array
{
    return [
        'severity' => $severity,
        'rule' => $rule,
        'file' => $file,
        'line' => $line,
        'message' => $message,
    ];
}

function compareFindings(array $left, array $right): int
{
    return [$left['file'], $left['line'], $left['rule']] <=> [$right['file'], $right['line'], $right['rule']];
}

function formatFinding(array $finding): string
{
    return sprintf(
        '[%s] %s:%d %s (%s)',
        strtoupper($finding['severity']),
        $finding['file'],
        $finding['line'],
        $finding['message'],
        $finding['rule'],
    );
}

function lineNumberFromOffset(string $content, int $offset): int
{
    return substr_count(substr($content, 0, $offset), "\n") + 1;
}

function clipText(string $text, int $limit = 90): string
{
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit - 3)).'...';
}

function isWhitelisted(array $entries, string $rule, string $file, ?string $key, int &$whitelistHitCount): bool
{
    foreach ($entries as $entry) {
        if (($entry['rule'] ?? null) !== $rule) {
            continue;
        }

        $entryFile = $entry['file'] ?? '*';

        if ($entryFile !== '*' && ! fnmatch($entryFile, $file)) {
            continue;
        }

        if (array_key_exists('key', $entry) && $entry['key'] !== $key) {
            continue;
        }

        $whitelistHitCount++;

        return true;
    }

    return false;
}
