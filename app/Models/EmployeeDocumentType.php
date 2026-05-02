<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_active',
        'employee_requestable',
        'admin_requestable',
        'requires_employee_upload',
        'auto_generate_enabled',
        'allowed_requester_groups',
        'allowed_reviewer_groups',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'employee_requestable' => 'boolean',
        'admin_requestable' => 'boolean',
        'requires_employee_upload' => 'boolean',
        'auto_generate_enabled' => 'boolean',
        'allowed_requester_groups' => 'array',
        'allowed_reviewer_groups' => 'array',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(EmployeeDocumentTemplate::class, 'document_type_id');
    }

    public function activeTemplate(): ?EmployeeDocumentTemplate
    {
        return $this->templates()
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function requests(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class, 'document_type_id');
    }
}
