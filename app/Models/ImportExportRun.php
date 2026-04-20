<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportExportRun extends Model
{
    protected $fillable = [
        'resource',
        'operation',
        'status',
        'requested_by_user_id',
        'queue',
        'source_disk',
        'source_path',
        'source_name',
        'file_disk',
        'file_path',
        'file_name',
        'mime_type',
        'size_bytes',
        'progress_percentage',
        'processed_rows',
        'total_rows',
        'meta',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'progress_percentage' => 'integer',
            'processed_rows' => 'integer',
            'total_rows' => 'integer',
            'meta' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function markRunning(array $attributes = []): void
    {
        $this->forceFill(array_merge([
            'status' => 'running',
            'started_at' => now(),
        ], $attributes))->save();
    }

    public function markCompleted(array $attributes = []): void
    {
        $this->forceFill(array_merge([
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ], $attributes))->save();
    }

    public function markFailed(string $message, array $attributes = []): void
    {
        $this->forceFill(array_merge([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $message,
        ], $attributes))->save();
    }
}
