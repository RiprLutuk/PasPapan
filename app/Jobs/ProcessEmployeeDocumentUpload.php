<?php

namespace App\Jobs;

use App\Models\EmployeeDocumentRequest;
use App\Notifications\EmployeeDocumentRequestStatusUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessEmployeeDocumentUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $requestId) {}

    public function handle(): void
    {
        $request = EmployeeDocumentRequest::query()->find($this->requestId);
        if (! $request || $request->status !== EmployeeDocumentRequest::STATUS_UPLOAD_PROCESSING) {
            return;
        }

        $upload = data_get($request->metadata, 'employee_upload', []);
        $stagedPath = is_array($upload) ? ($upload['staged_path'] ?? null) : null;
        if (! is_string($stagedPath) || ! Storage::disk('local')->exists($stagedPath)) {
            return;
        }

        $originalName = is_array($upload) ? (string) ($upload['original_name'] ?? 'document') : 'document';
        $path = 'employee-documents/uploads/'.basename($stagedPath);
        Storage::disk('local')->move($stagedPath, $path);

        $metadata = $request->metadata ?? [];
        unset($metadata['employee_upload']);

        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_UPLOADED,
            'uploaded_path' => $path,
            'uploaded_original_name' => $originalName,
            'uploaded_at' => now(),
            'metadata' => $metadata,
        ]);

        $request->loadMissing('user');
        $request->user?->notify(new EmployeeDocumentRequestStatusUpdated($request));
    }
}
