<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileAccessService
{
    public function __construct(
        protected AttachmentPathValidator $attachmentPathValidator,
    ) {}

    public function streamRelativePath(string $path, string $auditAction, ?string $description = null): StreamedResponse
    {
        return $this->serve($path, $auditAction, $description, false);
    }

    public function downloadRelativePath(string $path, string $auditAction, ?string $description = null): StreamedResponse
    {
        return $this->serve($path, $auditAction, $description, true);
    }

    protected function serve(string $path, string $auditAction, ?string $description, bool $download): StreamedResponse
    {
        if (! $this->attachmentPathValidator->isSafeRelativePath($path)) {
            ActivityLog::record("{$auditAction} Blocked", $this->describe($path, $description, 'invalid-path'));
            throw new NotFoundHttpException;
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if (! $disk->exists($path)) {
                continue;
            }

            ActivityLog::record($auditAction, $this->describe($path, $description, $diskName));

            return $download
                ? $disk->download($path)
                : $disk->response($path);
        }

        ActivityLog::record("{$auditAction} Missing", $this->describe($path, $description, 'missing'));
        throw new NotFoundHttpException;
    }

    protected function describe(string $path, ?string $description, string $location): string
    {
        $prefix = $description ? trim($description).'. ' : '';

        return $prefix.'File `'.basename($path).'` via '.$location.' handle.';
    }
}
