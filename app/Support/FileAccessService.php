<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
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

        $diskNames = config('filesystems.attachment_disks', ['local', 'public']);
        $diskNames = is_array($diskNames) && $diskNames !== [] ? $diskNames : ['local', 'public'];

        // Attachments are written to the private local disk first. The public
        // disk remains configurable as a legacy fallback for older installs and
        // migrated files.
        foreach ($diskNames as $diskName) {
            if (! is_string($diskName) || $diskName === '') {
                continue;
            }

            $disk = Storage::disk($diskName);

            if (! $disk->exists($path)) {
                continue;
            }

            if ($diskName === 'public') {
                Log::warning('Serving attachment from legacy public disk fallback.', [
                    'path_basename' => basename($path),
                    'audit_action' => $auditAction,
                ]);
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
