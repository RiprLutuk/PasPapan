<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_ADMIN = 'pending_admin';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_MISSING_CHECK_IN = 'missing_check_in';

    public const TYPE_MISSING_CHECK_OUT = 'missing_check_out';

    public const TYPE_WRONG_TIME = 'wrong_time';

    public const TYPE_WRONG_SHIFT = 'wrong_shift';

    protected $fillable = [
        'user_id',
        'attendance_id',
        'attendance_date',
        'request_type',
        'requested_time_in',
        'requested_time_out',
        'requested_shift_id',
        'current_snapshot',
        'reason',
        'status',
        'head_approved_by',
        'head_approved_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_note',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'requested_time_in' => 'datetime',
            'requested_time_out' => 'datetime',
            'current_snapshot' => 'array',
            'head_approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function requestedShift()
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function requestTypes(): array
    {
        return [
            self::TYPE_MISSING_CHECK_IN => __('Missing Check In'),
            self::TYPE_MISSING_CHECK_OUT => __('Missing Check Out'),
            self::TYPE_WRONG_TIME => __('Wrong Time'),
            self::TYPE_WRONG_SHIFT => __('Wrong Shift'),
        ];
    }

    public function requestTypeLabel(): string
    {
        return self::requestTypes()[$this->request_type] ?? __(str($this->request_type)->headline()->toString());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('Pending Supervisor Review'),
            self::STATUS_PENDING_ADMIN => __('Waiting Admin Review'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_REJECTED => __('Rejected'),
            default => __(str($this->status)->headline()->toString()),
        };
    }
}
