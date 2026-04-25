<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAsset extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_LOST = 'lost';

    public const STATUS_RETIRED = 'retired';

    public const STATUS_SOLD = 'sold';

    public const STATUS_AUCTIONED = 'auctioned';

    public const STATUS_DISPOSED = 'disposed';

    public const UNASSIGNED_ALLOWED_STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_MAINTENANCE,
        self::STATUS_LOST,
        self::STATUS_RETIRED,
        self::STATUS_SOLD,
        self::STATUS_AUCTIONED,
        self::STATUS_DISPOSED,
    ];

    protected $fillable = [
        'name',
        'serial_number',
        'type',
        'purchase_date',
        'purchase_cost',
        'expiration_date',
        'user_id',
        'date_assigned',
        'return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_assigned' => 'date',
        'return_date' => 'date',
        'purchase_date' => 'date',
        'expiration_date' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    /**
     * Get the user that was assigned this asset.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function histories()
    {
        return $this->hasMany(CompanyAssetHistory::class)->orderBy('date', 'desc');
    }

    public function isExpired()
    {
        if (! $this->expiration_date) {
            return false;
        }

        return now()->startOfDay()->greaterThan($this->expiration_date);
    }

    public function isExpiringSoon()
    {
        if (! $this->expiration_date || $this->isExpired()) {
            return false;
        }

        return now()->startOfDay()->diffInDays($this->expiration_date, false) <= 30;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_AVAILABLE => __('Ready'),
            self::STATUS_ASSIGNED => __('Assigned'),
            self::STATUS_MAINTENANCE => __('In Maintenance'),
            self::STATUS_LOST => __('Lost / Missing'),
            self::STATUS_RETIRED => __('Retired'),
            self::STATUS_SOLD => __('Sold'),
            self::STATUS_AUCTIONED => __('Auctioned'),
            self::STATUS_DISPOSED => __('Disposed / Scrapped'),
            default => filled($status) ? __(str($status)->headline()->toString()) : __('Unknown'),
        };
    }

    public function displayStatus(): string
    {
        return self::statusLabel($this->status);
    }
}
