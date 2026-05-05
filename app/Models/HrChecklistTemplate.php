<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrChecklistTemplate extends Model
{
    public const TYPE_ONBOARDING = 'onboarding';

    public const TYPE_OFFBOARDING = 'offboarding';

    protected $fillable = [
        'type',
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(HrChecklistTemplateItem::class, 'template_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(HrChecklistCase::class, 'template_id');
    }

    public static function types(): array
    {
        return [
            self::TYPE_ONBOARDING => __('Onboarding'),
            self::TYPE_OFFBOARDING => __('Offboarding'),
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? __(str($this->type)->headline()->toString());
    }
}
