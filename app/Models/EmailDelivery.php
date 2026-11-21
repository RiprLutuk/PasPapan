<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDelivery extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_RETRYING = 'retrying';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id', 'source_notification_id', 'dedupe_key', 'category',
        'recipient_name', 'recipient_email', 'subject', 'body', 'action_url',
        'status', 'attempts', 'available_at', 'sent_at', 'failed_at', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
