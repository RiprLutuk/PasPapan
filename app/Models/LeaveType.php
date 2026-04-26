<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    public const CATEGORY_ANNUAL = 'annual';

    public const CATEGORY_SICK = 'sick';

    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'counts_against_quota',
        'requires_attachment',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'counts_against_quota' => 'boolean',
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function attendanceStatus(): string
    {
        return $this->category === self::CATEGORY_SICK ? 'sick' : 'excused';
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_ANNUAL => __('Annual leave quota'),
            self::CATEGORY_SICK => __('Sick leave'),
            self::CATEGORY_OTHER => __('Special leave / permission'),
        ];
    }
}
