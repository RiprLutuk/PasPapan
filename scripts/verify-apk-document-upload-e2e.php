<?php

use App\Models\EmployeeDocumentRequest;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requestId = getenv('E2E_REQUEST_ID');

if (! $requestId) {
    fwrite(STDERR, "E2E_REQUEST_ID is required.\n");
    exit(1);
}

$request = EmployeeDocumentRequest::query()->find($requestId);

if (! $request) {
    fwrite(STDERR, "Document request {$requestId} was not found.\n");
    exit(1);
}

$uploadedPath = (string) $request->uploaded_path;

if ($request->status !== EmployeeDocumentRequest::STATUS_UPLOADED || $uploadedPath === '') {
    fwrite(STDERR, "Document upload did not finish. Status: {$request->status}.\n");
    exit(1);
}

if (! Storage::disk('local')->exists($uploadedPath)) {
    fwrite(STDERR, "Uploaded file is missing from local disk: {$uploadedPath}.\n");
    exit(1);
}

echo json_encode([
    'request_id' => $request->id,
    'status' => $request->status,
    'uploaded_path' => $uploadedPath,
    'uploaded_original_name' => $request->uploaded_original_name,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
