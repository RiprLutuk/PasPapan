<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBackupRun extends Model
{
    protected $fillable = [
        'type',
        'status',
        'requested_by_user_id',
        'queue',
        'file_disk',
        'file_path',
        'file_name',
        'size_bytes',
        'error_message',
        'meta',
        'started_at',
        'completed_at',
        'failed_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'meta' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
