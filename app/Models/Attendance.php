<?php

namespace App\Models;

use App\Support\ExtendedCarbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Attendance extends Model
{
    use HasFactory;
    use HasTimestamps;

    public function scopePending($query)
    {
        return $query->where('approval_status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include attendances of users managed by the given Admin.
     */
    public function scopeManagedBy($query, $admin)
    {
        if ($admin->isSuperadmin) {
            return $query;
        }

        return $query->whereHas('user', function ($q) use ($admin) {
            $q->managedBy($admin);
        });
    }

    protected $fillable = [
        'user_id',
        'barcode_id',
        'date',
        'time_in',
        'time_out',
        'shift_id',
        'latitude_in',
        'longitude_in',
        'accuracy_in',
        'gps_variance_in',
        'latitude_out',
        'longitude_out',
        'accuracy_out',
        'gps_variance_out',
        'is_suspicious',
        'suspicious_reason',
        'status',
        'note',
        'attachment',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_note',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'date' => 'datetime:Y-m-d',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'latitude_in' => 'float',
            'longitude_in' => 'float',
            'latitude_out' => 'float',
            'longitude_out' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function barcode()
    {
        return $this->belongsTo(Barcode::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    function getLatLngAttribute(): array|null
    {
        if (is_null($this->latitude_in) || is_null($this->longitude_in)) {
            return null;
        }
        return [
            'lat' => $this->latitude_in,
            'lng' => $this->longitude_in
        ];
    }

    public function getCheckInLocationAttribute()
    {
        if ($this->latitude_in && $this->longitude_in) {
            return [
                'lat' => $this->latitude_in,
                'lng' => $this->longitude_in,
            ];
        }
        return null;
    }

    public function getCheckOutLocationAttribute()
    {
        if ($this->latitude_out && $this->longitude_out) {
            return [
                'lat' => $this->latitude_out,
                'lng' => $this->longitude_out,
            ];
        }
        return null;
    }

    public static function filter(
        $date = null,
        $week = null,
        $month = null,
        $year = null,
        $userId = null,
        $division = null,
        $jobTitle = null,
        $education = null
    ) {
        return self::when($date, function (Builder $query) use ($date) {
            $query->where('date', Carbon::parse($date)->toDateString());
        })->when($week && !$date, function (Builder $query) use ($week) {
            $start = Carbon::parse($week)->startOfWeek();
            $end = Carbon::parse($week)->endOfWeek();
            $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        })->when($month && !$week && !$date, function (Builder $query) use ($month) {
            $date = Carbon::parse($month);
            $query->whereMonth('date', $date->month)->whereYear('date', $date->year);
        })->when($year && !$month && !$week && !$date, function (Builder $query) use ($year) {
            $query->whereYear('date', $year);
        })->when($userId, function (Builder $query) use ($userId) {
            $query->where('user_id', $userId);
        })->when($division && !$userId, function (Builder $query) use ($division) {
            $query->whereHas('user', function (Builder $query) use ($division) {
                $query->where('division_id', $division);
            });
        })->when($jobTitle && !$userId, function (Builder $query) use ($jobTitle) {
            $query->whereHas('user', function (Builder $query) use ($jobTitle) {
                $query->where('job_title_id', $jobTitle);
            });
        })->when($education && !$userId, function (Builder $query) use ($education) {
            $query->whereHas('user', function (Builder $query) use ($education) {
                $query->where('education_id', $education);
            });
        });
    }

    public function attachmentUrl(): Attribute
    {
        return Attribute::get(function (): array|string|null {
            if (!$this->attachment) {
                return null;
            }

            // Open Core: Delegate to Service
            $service = app(\App\Contracts\AttendanceServiceInterface::class);
            return $service->getAttachmentUrl($this);
        });
    }

    public static function clearUserAttendanceCache(Authenticatable $user, Carbon $date)
    {
        if (is_null($user)) return false;
        $date = new ExtendedCarbon($date);
        $monthYear = "$date->month-$date->year";
        $userId = $user->getAuthIdentifier(); // Fix lint error
        $week = $date->yearWeekString();
        $ymd = $date->format('Y-m-d');

        try {
            Cache::forget("attendance-$userId-$monthYear");
            Cache::forget("attendance-$userId-$week");
            Cache::forget("attendance-$userId-$ymd");
            return true;
        } catch (\Throwable $_) {
            return false;
        }
    }
}
