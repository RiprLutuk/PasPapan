<?php

use App\Jobs\ProcessEmployeeDocumentUpload;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requestId = getenv('E2E_REQUEST_ID');

if (! $requestId) {
    fwrite(STDERR, "E2E_REQUEST_ID is required.\n");
    exit(1);
}

(new ProcessEmployeeDocumentUpload((int) $requestId))->handle();

echo json_encode([
    'request_id' => (int) $requestId,
    'processed' => true,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
