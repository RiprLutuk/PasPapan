<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class EmployeeDocumentRequest extends Model
{
    public const TYPE_EMPLOYMENT_CERTIFICATE = 'employment_certificate';

    public const TYPE_SALARY_STATEMENT = 'salary_statement';

    public const TYPE_VISA_LETTER = 'visa_letter';

    public const TYPE_OTHER = 'other';

    public const SOURCE_EMPLOYEE = 'employee';

    public const SOURCE_ADMIN = 'admin';

    public const SOURCE_SYSTEM = 'system';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_READY = 'ready';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'document_type_id',
        'document_type',
        'requested_by',
        'request_source',
        'purpose',
        'details',
        'due_date',
        'status',
        'uploaded_path',
        'uploaded_original_name',
        'uploaded_at',
        'generated_path',
        'generated_template_id',
        'generated_at',
        'metadata',
        'reviewed_by',
        'reviewed_at',
        'fulfillment_note',
        'rejection_note',
    ];

    protected $casts = [
        'due_date' => 'date',
        'uploaded_at' => 'datetime',
        'generated_at' => 'datetime',
        'metadata' => 'array',
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocumentType::class, 'document_type_id');
    }

    public function generatedTemplate(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocumentTemplate::class, 'generated_template_id');
    }

    /**
     * @return array<string, string>
     */
    public static function documentTypes(): array
    {
        $configured = Schema::hasTable('employee_document_types')
            ? EmployeeDocumentType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->all()
            : [];

        if ($configured !== []) {
            return collect($configured)
                ->map(fn (string $name): string => __($name))
                ->all();
        }

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
            self::STATUS_REQUESTED => __('Requested'),
            self::STATUS_UPLOADED => __('Uploaded'),
            self::STATUS_GENERATED => __('Generated'),
            self::STATUS_READY => __('Ready'),
            self::STATUS_REJECTED => __('Rejected'),
            self::STATUS_EXPIRED => __('Expired'),
        ];
    }

    public function documentTypeLabel(): string
    {
        return ($this->documentType?->name ? __($this->documentType->name) : null)
            ?? self::documentTypes()[$this->document_type]
            ?? ucfirst(str_replace('_', ' ', (string) $this->document_type));
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }
}
