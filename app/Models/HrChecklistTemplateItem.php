<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrChecklistTemplateItem extends Model
{
    public const ASSIGNEE_HR = 'hr';

    public const ASSIGNEE_EMPLOYEE = 'employee';

    public const ASSIGNEE_MANAGER = 'manager';

    protected $fillable = [
        'template_id',
        'title',
        'description',
        'category',
        'default_assignee_type',
        'due_offset_days',
        'is_required',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'due_offset_days' => 'integer',
        'is_required' => 'boolean',
        'metadata' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(HrChecklistTemplate::class, 'template_id');
    }

    public static function assigneeTypes(): array
    {
        return [
            self::ASSIGNEE_HR => __('HR'),
            self::ASSIGNEE_EMPLOYEE => __('Employee'),
            self::ASSIGNEE_MANAGER => __('Manager'),
        ];
    }
}
