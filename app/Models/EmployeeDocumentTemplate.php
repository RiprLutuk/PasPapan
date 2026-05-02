<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDocumentTemplate extends Model
{
    protected $fillable = [
        'document_type_id',
        'name',
        'paper_size',
        'orientation',
        'body',
        'footer',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocumentType::class, 'document_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function generatedRequests(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class, 'generated_template_id');
    }
}
