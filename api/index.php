<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = dirname(__DIR__);
$storagePath = $_ENV['APP_STORAGE_PATH']
    ?? $_SERVER['APP_STORAGE_PATH']
    ?? getenv('APP_STORAGE_PATH')
    ?: '/tmp/storage';

$_ENV['APP_STORAGE_PATH'] = $storagePath;
$_SERVER['APP_STORAGE_PATH'] = $storagePath;
putenv('APP_STORAGE_PATH='.$storagePath);

foreach ([
    $storagePath.'/app',
    $storagePath.'/app/livewire-tmp',
    $storagePath.'/app/import-export/uploads',
    $storagePath.'/app/import-export/exports',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/testing',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

$_SERVER['DOCUMENT_ROOT'] = $basePath.'/public';

if (file_exists($maintenance = $storagePath.'/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

(require_once $basePath.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
