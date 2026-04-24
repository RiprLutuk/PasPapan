<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocumentRequest extends Model
{
    public const TYPE_EMPLOYMENT_CERTIFICATE = 'employment_certificate';

    public const TYPE_SALARY_STATEMENT = 'salary_statement';

    public const TYPE_VISA_LETTER = 'visa_letter';

    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'document_type',
        'purpose',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
        'fulfillment_note',
        'rejection_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array<string, string>
     */
    public static function documentTypes(): array
    {
        return [
            self::TYPE_EMPLOYMENT_CERTIFICATE => __('Employment Certificate'),
            self::TYPE_SALARY_STATEMENT => __('Salary Statement'),
            self::TYPE_VISA_LETTER => __('Visa / Bank Letter'),
            self::TYPE_OTHER => __('Other Document'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_READY => __('Ready'),
            self::STATUS_REJECTED => __('Rejected'),
        ];
    }

    public function documentTypeLabel(): string
    {
        return self::documentTypes()[$this->document_type] ?? ucfirst(str_replace('_', ' ', (string) $this->document_type));
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }
}
