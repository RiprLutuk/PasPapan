<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (PHP_VERSION_ID >= 80500) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
