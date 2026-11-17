<?php

namespace App\Support;

use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\EmployeeDocumentRequestStatusUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeDocumentRequestService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(User $user, array $payload): EmployeeDocumentRequest
    {
        $documentType = $this->documentTypeForCode((string) $payload['document_type']);

        return EmployeeDocumentRequest::create([
            'user_id' => $user->id,
            'document_type_id' => $documentType?->id,
            'document_type' => $documentType?->code ?? $payload['document_type'],
            'requested_by' => $user->id,
            'request_source' => EmployeeDocumentRequest::SOURCE_EMPLOYEE,
            'purpose' => $payload['purpose'],
            'details' => $payload['details'] ?: null,
            'status' => EmployeeDocumentRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function requestFromAdmin(User $actor, User $target, array $payload): EmployeeDocumentRequest
    {
        $documentType = $this->documentTypeForCode((string) $payload['document_type']);
        $status = $documentType?->requires_employee_upload
            ? EmployeeDocumentRequest::STATUS_REQUESTED
            : EmployeeDocumentRequest::STATUS_PENDING;

        $request = EmployeeDocumentRequest::create([
            'user_id' => $target->id,
            'document_type_id' => $documentType?->id,
            'document_type' => $documentType?->code ?? $payload['document_type'],
            'requested_by' => $actor->id,
            'request_source' => EmployeeDocumentRequest::SOURCE_ADMIN,
            'purpose' => $payload['purpose'],
            'details' => $payload['details'] ?: null,
            'due_date' => $payload['due_date'] ?: null,
            'status' => $status,
        ]);

        $this->notifyStatusUpdated($request);

        return $request;
    }

    public function upload(EmployeeDocumentRequest $request, User $actor, UploadedFile $file): string
    {
        if ($request->user_id !== $actor->id) {
            abort(403);
        }

        $path = $file->store('employee-documents/uploads', 'local');

        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_UPLOADED,
            'uploaded_path' => $path,
            'uploaded_original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now(),
        ]);

        return __('Document uploaded successfully.');
    }

    public function markReady(EmployeeDocumentRequest $request, User $actor, ?string $note = null): string
    {
        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_READY,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'fulfillment_note' => $note,
            'rejection_note' => null,
        ]);

        $this->notifyStatusUpdated($request);

        return __('Document request marked as ready.');
    }

    public function generate(EmployeeDocumentRequest $request, User $actor): string
    {
        $request->loadMissing(['user.division', 'user.jobTitle', 'documentType']);
        $documentType = $request->documentType ?? $this->documentTypeForCode((string) $request->document_type);
        $template = $documentType?->activeTemplate();

        if (! $documentType?->auto_generate_enabled || ! $template) {
            return __('No active template is configured for this document type.');
        }

        $body = $this->renderTemplate($template, $request);
        $footer = $template->footer ? $this->renderTemplateText($template->footer, $request) : null;
        $layoutOptions = collect($template->layout_options ?? [])
            ->map(fn ($value) => is_string($value) ? $this->renderTemplateText($value, $request) : $value)
            ->all();
        $pdf = app(EmployeeDocumentPdfFactory::class)->make(
            $body,
            $footer,
            $template->paper_size ?: 'a4',
            $template->orientation ?: 'portrait',
            $this->documentMeta($request),
            $layoutOptions,
        );

        $filename = sprintf(
            'employee-documents/generated/%s-%s-%s.pdf',
            now()->format('YmdHis'),
            Str::slug($request->user?->name ?? 'employee'),
            Str::slug($documentType->code)
        );

        Storage::disk('local')->put($filename, $pdf->output());

        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_GENERATED,
            'generated_path' => $filename,
            'generated_template_id' => $template->id,
            'generated_at' => now(),
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'fulfillment_note' => __('Generated from template: :template', ['template' => $template->name]),
            'rejection_note' => null,
        ]);

        $this->notifyStatusUpdated($request);

        return __('Document generated successfully.');
    }

    public function reject(EmployeeDocumentRequest $request, User $actor, ?string $note = null): string
    {
        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_note' => $note,
        ]);

        $this->notifyStatusUpdated($request);

        return __('Document request rejected.');
    }

    public function managementQuery(string $statusFilter = 'pending', string $typeFilter = 'all', string $search = ''): Builder
    {
        return EmployeeDocumentRequest::query()
            ->with(['user.division', 'user.jobTitle', 'requester', 'reviewer', 'documentType', 'generatedTemplate'])
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn (Builder $query) => $query->where('document_type', $typeFilter))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('purpose', 'like', '%'.$search.'%')
                        ->orWhere('details', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest();
    }

    /**
     * @return array<string, string>
     */
    public function activeDocumentTypeOptions(bool $forEmployee = false, bool $forAdmin = false): array
    {
        if (! Schema::hasTable('employee_document_types')) {
            return EmployeeDocumentRequest::documentTypes();
        }

        $this->seedDefaultTypes();

        $query = EmployeeDocumentType::query()->where('is_active', true);

        if ($forEmployee) {
            $query->where('employee_requestable', true);
        }

        if ($forAdmin) {
            $query->where('admin_requestable', true);
        }

        $types = collect($query->orderBy('category')->orderBy('name')->pluck('name', 'code')->all())
            ->map(fn (string $name): string => __($name))
            ->all();

        return $types !== [] ? $types : EmployeeDocumentRequest::documentTypes();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function templateVariables(EmployeeDocumentRequest $request): array
    {
        return [
            'employee.name' => $request->user?->name,
            'employee.nip' => $request->user?->nip,
            'employee.email' => $request->user?->email,
            'employee.phone' => $request->user?->phone,
            'employee.division' => $request->user?->division?->name,
            'employee.job_title' => $request->user?->jobTitle?->name,
            'company.name' => Setting::getValue('app.company_name', config('app.name')),
            'company.address' => Setting::getValue('app.company_address', ''),
            'company.support_contact' => Setting::getValue('app.support_contact', ''),
            'request.id' => $request->id,
            'request.purpose' => $request->purpose,
            'request.details' => $request->details,
            'request.document_type' => $request->documentTypeLabel(),
            'request.due_date' => $request->due_date?->format('d M Y'),
            'date.today' => now()->format('d M Y'),
        ];
    }

    public function seedDefaultTypes(): void
    {
        if (! Schema::hasTable('employee_document_types')) {
            return;
        }

        foreach ($this->defaultDocumentTypes() as $type) {
            EmployeeDocumentType::query()->firstOrCreate(['code' => $type['code']], $type);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultDocumentTypes(): array
    {
        return [
            [
                'code' => EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE,
                'name' => 'Employment Certificate',
                'category' => 'hr',
                'description' => 'System-generated employment letter.',
                'employee_requestable' => true,
                'admin_requestable' => true,
                'requires_employee_upload' => false,
                'auto_generate_enabled' => true,
            ],
            [
                'code' => 'paklaring',
                'name' => 'Paklaring',
                'category' => 'hr',
                'description' => 'Employment experience letter for former or resigning employees.',
                'employee_requestable' => true,
                'admin_requestable' => true,
                'requires_employee_upload' => false,
                'auto_generate_enabled' => true,
            ],
            [
                'code' => EmployeeDocumentRequest::TYPE_SALARY_STATEMENT,
                'name' => 'Salary Statement',
                'category' => 'finance',
                'description' => 'Payroll or salary supporting document.',
                'employee_requestable' => true,
                'admin_requestable' => true,
                'requires_employee_upload' => false,
                'auto_generate_enabled' => true,
            ],
            [
                'code' => 'npwp',
                'name' => 'NPWP',
                'category' => 'finance',
                'description' => 'Employee tax ID document upload request.',
                'employee_requestable' => false,
                'admin_requestable' => true,
                'requires_employee_upload' => true,
                'auto_generate_enabled' => false,
            ],
            [
                'code' => 'bank_account',
                'name' => 'Bank Account',
                'category' => 'finance',
                'description' => 'Employee bank account proof or update request.',
                'employee_requestable' => false,
                'admin_requestable' => true,
                'requires_employee_upload' => true,
                'auto_generate_enabled' => false,
            ],
            [
                'code' => EmployeeDocumentRequest::TYPE_VISA_LETTER,
                'name' => 'Visa / Bank Letter',
                'category' => 'hr',
                'description' => 'Employment supporting letter for external use.',
                'employee_requestable' => true,
                'admin_requestable' => true,
                'requires_employee_upload' => false,
                'auto_generate_enabled' => true,
            ],
            [
                'code' => EmployeeDocumentRequest::TYPE_OTHER,
                'name' => 'Other Document',
                'category' => 'hr',
                'description' => 'Manual document request.',
                'employee_requestable' => true,
                'admin_requestable' => true,
                'requires_employee_upload' => false,
                'auto_generate_enabled' => false,
            ],
        ];
    }

    private function documentTypeForCode(string $code): ?EmployeeDocumentType
    {
        $this->seedDefaultTypes();

        return EmployeeDocumentType::query()
            ->where('code', $code)
            ->first();
    }

    private function renderTemplate(EmployeeDocumentTemplate $template, EmployeeDocumentRequest $request): string
    {
        return $this->sanitizeHtml($this->renderTemplateText($template->body, $request));
    }

    /**
     * @return array<string, string>
     */
    private function documentMeta(EmployeeDocumentRequest $request): array
    {
        $typeCode = Str::upper(Str::slug((string) ($request->documentType?->code ?? $request->document_type), '-'));

        return [
            'Nomor' => sprintf('DOC/%s/%s/%s', $typeCode ?: 'GENERAL', now()->format('Ymd'), str_pad((string) $request->id, 4, '0', STR_PAD_LEFT)),
            'Tanggal' => now()->translatedFormat('d F Y'),
            'Perihal' => $request->documentTypeLabel(),
        ];
    }

    private function renderTemplateText(string $template, EmployeeDocumentRequest $request): string
    {
        $values = $this->templateVariables($request);

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function (array $matches) use ($values): string {
            $value = $values[$matches[1]] ?? '';

            return e((string) $value);
        }, $template) ?? $template;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><table><thead><tbody><tr><th><td><h1><h2><h3><h4><div><span><small><hr>');
    }

    private function notifyStatusUpdated(EmployeeDocumentRequest $request): void
    {
        $request->refresh()->loadMissing('user');
        $request->user?->notify(new EmployeeDocumentRequestStatusUpdated($request));
    }
}
