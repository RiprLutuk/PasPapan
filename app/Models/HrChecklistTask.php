<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrChecklistTask extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'case_id',
        'template_item_id',
        'assigned_to',
        'title',
        'description',
        'category',
        'due_date',
        'status',
        'completed_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(HrChecklistCase::class, 'case_id');
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(HrChecklistTemplateItem::class, 'template_item_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_DONE => __('Done'),
            self::STATUS_SKIPPED => __('Skipped'),
            self::STATUS_BLOCKED => __('Blocked'),
        ];
    }

    public static function closedStatuses(): array
    {
        return [
            self::STATUS_DONE,
            self::STATUS_SKIPPED,
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? __(str($this->status)->headline()->toString());
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->due_date !== null
            && now()->startOfDay()->greaterThan($this->due_date);
    }
}
