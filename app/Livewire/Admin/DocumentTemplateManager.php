<?php

namespace App\Livewire\Admin;

use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Models\Setting;
use App\Support\EmployeeDocumentPdfFactory;
use App\Support\EmployeeDocumentRequestService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DocumentTemplateManager extends Component
{
    public array $documentTypeForm = [];

    public array $documentTemplateForm = [];

    public array $templateBuilderForm = [];

    public string $templateEditorMode = 'builder';

    public bool $editingDocumentType = false;

    public bool $confirmingTemplateDeletion = false;

    public ?int $templateDeletionId = null;

    protected EmployeeDocumentRequestService $documentWorkflow;

    public function boot(EmployeeDocumentRequestService $documentWorkflow): void
    {
        $this->documentWorkflow = $documentWorkflow;
    }

    public function mount(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->documentWorkflow->seedDefaultTypes();
        $this->resetDocumentTypeForm();
        $this->resetDocumentTemplateForm();

        $templateId = request()->integer('template');

        if ($templateId > 0) {
            $this->editDocumentTemplate($templateId);
        }
    }

    public function saveDocumentType(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->documentTypeForm['code'] = Str::slug((string) ($this->documentTypeForm['code'] ?? ''), '_');

        $validated = $this->validate([
            'documentTypeForm.id' => ['nullable', 'integer', 'exists:employee_document_types,id'],
            'documentTypeForm.code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('employee_document_types', 'code')->ignore($this->documentTypeForm['id'] ?? null),
            ],
            'documentTypeForm.name' => ['required', 'string', 'max:255'],
            'documentTypeForm.category' => ['required', 'string', 'max:64'],
            'documentTypeForm.description' => ['nullable', 'string', 'max:1000'],
            'documentTypeForm.is_active' => ['boolean'],
            'documentTypeForm.employee_requestable' => ['boolean'],
            'documentTypeForm.admin_requestable' => ['boolean'],
            'documentTypeForm.requires_employee_upload' => ['boolean'],
            'documentTypeForm.auto_generate_enabled' => ['boolean'],
        ]);

        $type = EmployeeDocumentType::query()->updateOrCreate(
            ['id' => $validated['documentTypeForm']['id'] ?? null],
            $validated['documentTypeForm'],
        );

        $this->documentTemplateForm['document_type_id'] = $type->id;
        $this->editingDocumentType = false;
        $this->resetDocumentTypeForm();
        $this->dispatch('saved');
    }

    public function startNewDocumentType(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->resetDocumentTypeForm();
        $this->editingDocumentType = true;
    }

    public function editSelectedDocumentType(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $typeId = (int) ($this->documentTemplateForm['document_type_id'] ?? 0);

        if ($typeId > 0) {
            $this->editDocumentType($typeId);
        }
    }

    public function editDocumentType(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        $type = EmployeeDocumentType::findOrFail($id);
        $this->documentTypeForm = [
            'id' => $type->id,
            'code' => $type->code,
            'name' => $type->name,
            'category' => $type->category,
            'description' => $type->description,
            'is_active' => $type->is_active,
            'employee_requestable' => $type->employee_requestable,
            'admin_requestable' => $type->admin_requestable,
            'requires_employee_upload' => $type->requires_employee_upload,
            'auto_generate_enabled' => $type->auto_generate_enabled,
        ];
        $this->editingDocumentType = true;
    }

    public function resetDocumentTypeForm(): void
    {
        $this->documentTypeForm = [
            'id' => null,
            'code' => '',
            'name' => '',
            'category' => 'hr',
            'description' => '',
            'is_active' => true,
            'employee_requestable' => true,
            'admin_requestable' => true,
            'requires_employee_upload' => false,
            'auto_generate_enabled' => false,
        ];
    }

    public function cancelDocumentTypeEditor(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->editingDocumentType = false;
        $this->resetDocumentTypeForm();
    }

    public function saveDocumentTemplate(): void
    {
        Gate::authorize('manageDocumentTemplates');

        if ($this->templateEditorMode === 'builder') {
            $this->applyTemplateBuilder();
        }

        $validated = $this->validate([
            'documentTemplateForm.id' => ['nullable', 'integer', 'exists:employee_document_templates,id'],
            'documentTemplateForm.document_type_id' => ['required', 'integer', 'exists:employee_document_types,id'],
            'documentTemplateForm.name' => ['required', 'string', 'max:255'],
            'documentTemplateForm.paper_size' => ['required', Rule::in(['a4', 'letter', 'legal'])],
            'documentTemplateForm.orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'documentTemplateForm.body' => ['required', 'string', 'min:20', 'max:30000'],
            'documentTemplateForm.footer' => ['nullable', 'string', 'max:5000'],
            'documentTemplateForm.layout_options' => ['nullable', 'array'],
            'documentTemplateForm.layout_options.show_logo' => ['boolean'],
            'documentTemplateForm.layout_options.show_accents' => ['boolean'],
            'documentTemplateForm.layout_options.show_document_meta' => ['boolean'],
            'documentTemplateForm.layout_options.header_company_name' => ['nullable', 'string', 'max:255'],
            'documentTemplateForm.layout_options.header_address' => ['nullable', 'string', 'max:1000'],
            'documentTemplateForm.layout_options.header_contact' => ['nullable', 'string', 'max:500'],
            'documentTemplateForm.layout_options.header_tagline' => ['nullable', 'string', 'max:255'],
            'documentTemplateForm.is_active' => ['boolean'],
        ]);

        $payload = $validated['documentTemplateForm'];
        $payload['layout_options'] = $this->sanitizeLayoutOptions($payload['layout_options'] ?? []);
        $payload['updated_by'] = auth()->id();

        if (blank($payload['id'] ?? null)) {
            $payload['created_by'] = auth()->id();
        }

        $template = EmployeeDocumentTemplate::query()->updateOrCreate(
            ['id' => $payload['id'] ?? null],
            $payload,
        );

        if ($template->is_active) {
            $this->deactivateSiblingTemplates($template);
            $template->documentType?->update(['auto_generate_enabled' => true]);
        }

        $this->resetDocumentTemplateForm((int) $template->document_type_id);
        $this->dispatch('saved');
    }

    public function editDocumentTemplate(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        $template = EmployeeDocumentTemplate::findOrFail($id);
        $this->documentTemplateForm = [
            'id' => $template->id,
            'document_type_id' => $template->document_type_id,
            'name' => $template->name,
            'paper_size' => $template->paper_size,
            'orientation' => $template->orientation,
            'body' => $template->body,
            'footer' => $template->footer,
            'layout_options' => $this->layoutOptions($template->layout_options ?? []),
            'is_active' => $template->is_active,
        ];
        $this->templateEditorMode = 'html';
    }

    public function resetDocumentTemplateForm(?int $documentTypeId = null): void
    {
        $selectedTypeId = $documentTypeId ?: (int) ($this->documentTemplateForm['document_type_id'] ?? 0);
        $type = EmployeeDocumentType::query()
            ->when($selectedTypeId > 0, fn ($query) => $query->whereKey($selectedTypeId))
            ->first();

        $type ??= EmployeeDocumentType::query()->orderBy('name')->first();

        $this->templateEditorMode = 'builder';
        $this->templateBuilderForm = $this->templatePresetPayload('letter');
        $this->documentTemplateForm = [
            'id' => null,
            'document_type_id' => $type?->id,
            'name' => '',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'body' => '',
            'footer' => $this->templateBuilderForm['footer'],
            'layout_options' => $this->layoutOptions(),
            'is_active' => true,
        ];
        $this->applyTemplateBuilder();
    }

    public function startNewDocumentTemplate(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->resetDocumentTemplateForm((int) ($this->documentTemplateForm['document_type_id'] ?? 0));
    }

    public function setTemplateEditorMode(string $mode): void
    {
        Gate::authorize('manageDocumentTemplates');

        if (! in_array($mode, ['builder', 'html'], true)) {
            return;
        }

        $this->templateEditorMode = $mode;

        if ($mode === 'builder') {
            $this->applyTemplateBuilder();
        }
    }

    public function useTemplatePreset(string $preset): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->templateEditorMode = 'builder';
        $this->templateBuilderForm = $this->templatePresetPayload($preset);
        $this->documentTemplateForm['name'] = match ($preset) {
            'salary' => __('Salary Statement'),
            'upload' => __('Employee Upload Request'),
            default => __('Employment Letter'),
        };
        $this->documentTemplateForm['footer'] = $this->templateBuilderForm['footer'];
        $this->applyTemplateBuilder();
    }

    public function duplicateTemplate(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        $template = EmployeeDocumentTemplate::findOrFail($id);
        $copy = $template->replicate(['is_active', 'created_by', 'updated_by']);
        $copy->name = __('Copy of :name', ['name' => $template->name]);
        $copy->is_active = false;
        $copy->created_by = auth()->id();
        $copy->updated_by = auth()->id();
        $copy->save();

        $this->editDocumentTemplate($copy->id);
        $this->dispatch('saved');
    }

    public function activateTemplate(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        $template = EmployeeDocumentTemplate::with('documentType')->findOrFail($id);
        $template->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);
        $this->deactivateSiblingTemplates($template);
        $template->documentType?->update(['auto_generate_enabled' => true]);

        $this->dispatch('saved');
    }

    public function deactivateTemplate(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        EmployeeDocumentTemplate::query()
            ->whereKey($id)
            ->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);

        $this->dispatch('saved');
    }

    public function confirmDeleteTemplate(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->templateDeletionId = $id;
        $this->confirmingTemplateDeletion = true;
    }

    public function cancelDeleteTemplate(): void
    {
        $this->templateDeletionId = null;
        $this->confirmingTemplateDeletion = false;
    }

    public function deleteTemplate(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $template = EmployeeDocumentTemplate::query()
            ->withCount('generatedRequests')
            ->findOrFail($this->templateDeletionId);

        if ($template->generated_requests_count > 0) {
            $template->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);
            session()->flash('warning', __('Template has been used by generated documents, so it was deactivated instead of deleted.'));
        } else {
            $template->delete();
            session()->flash('success', __('Template deleted.'));
        }

        if (($this->documentTemplateForm['id'] ?? null) === $template->id) {
            $this->resetDocumentTemplateForm();
        }

        $this->cancelDeleteTemplate();
    }

    public function downloadPreviewPdf()
    {
        Gate::authorize('manageDocumentTemplates');

        if ($this->templateEditorMode === 'builder') {
            $this->applyTemplateBuilder();
        }

        $body = $this->previewTemplateHtml((string) ($this->documentTemplateForm['body'] ?? ''));
        $footer = $this->previewTemplateText((string) ($this->documentTemplateForm['footer'] ?? ''));
        $pdf = app(EmployeeDocumentPdfFactory::class)->make(
            $body,
            $footer,
            (string) ($this->documentTemplateForm['paper_size'] ?? 'a4'),
            (string) ($this->documentTemplateForm['orientation'] ?? 'portrait'),
            $this->previewDocumentMeta(),
            $this->previewLayoutOptions($this->documentTemplateForm['layout_options'] ?? []),
        );

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            Str::slug((string) ($this->documentTemplateForm['name'] ?? 'document-template')).'-preview.pdf',
        );
    }

    public function updatedTemplateBuilderForm(): void
    {
        if ($this->templateEditorMode === 'builder') {
            $this->applyTemplateBuilder();
        }
    }

    public function updatedDocumentTemplateFormDocumentTypeId($value): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->editingDocumentType = false;
        $this->resetDocumentTypeForm();
        $this->resetDocumentTemplateForm((int) $value);
    }

    public function applyTemplateBuilder(): void
    {
        $this->documentTemplateForm['body'] = $this->buildTemplateBody();
    }

    public function render()
    {
        Gate::authorize('manageDocumentTemplates');

        $previewBody = $this->previewTemplateHtml((string) ($this->documentTemplateForm['body'] ?? ''));
        $previewFooter = $this->previewTemplateText((string) ($this->documentTemplateForm['footer'] ?? ''));
        $previewMeta = $this->previewDocumentMeta();
        $layoutOptions = $this->previewLayoutOptions($this->documentTemplateForm['layout_options'] ?? []);

        return view('livewire.admin.document-template-manager', [
            'documentWorkflowTypes' => EmployeeDocumentType::query()
                ->withCount('templates')
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
            'documentWorkflowTemplates' => EmployeeDocumentTemplate::query()
                ->with('documentType')
                ->withCount('generatedRequests')
                ->latest()
                ->limit(30)
                ->get(),
            'documentTemplateVariables' => $this->templateVariableDefinitions(),
            'templatePreviewBody' => $previewBody,
            'templatePreviewFooter' => $previewFooter,
            'templatePreviewHtml' => app(EmployeeDocumentPdfFactory::class)->previewHtml($previewBody, $previewFooter ?: null, $previewMeta, $layoutOptions),
        ]);
    }

    private function buildTemplateBody(): string
    {
        $heading = $this->templateLine('heading');
        $opening = $this->templateParagraph('opening');
        $main = $this->templateParagraph('main_paragraph');
        $details = $this->templateParagraph('details_paragraph');
        $closing = $this->templateParagraph('closing');
        $placeDate = $this->templateLine('place_date');
        $signatureTitle = $this->templateLine('signature_title');
        $signatureName = $this->templateLine('signature_name');

        return <<<HTML
<h2 style="text-align:center;">{$heading}</h2>
<p>{$opening}</p>
<p>{$main}</p>
<p>{$details}</p>
<p>{$closing}</p>
<p style="margin-top:32px;">{$placeDate}</p>
<p style="margin-top:28px;">{$signatureTitle}</p>
<p style="margin-top:48px;"><strong>{$signatureName}</strong></p>
HTML;
    }

    /**
     * @return array<string, string>
     */
    private function previewDocumentMeta(): array
    {
        $subject = EmployeeDocumentType::query()
            ->whereKey((int) ($this->documentTemplateForm['document_type_id'] ?? 0))
            ->value('name');

        return [
            __('Number') => 'DOC/PREVIEW/'.now()->format('Ymd').'/0001',
            __('Date') => now()->translatedFormat('d F Y'),
            __('Subject') => $subject ?: (string) ($this->documentTemplateForm['name'] ?? __('Employee Document')),
        ];
    }

    private function templateLine(string $key): string
    {
        return e(trim((string) ($this->templateBuilderForm[$key] ?? '')));
    }

    private function templateParagraph(string $key): string
    {
        return nl2br(e(trim((string) ($this->templateBuilderForm[$key] ?? ''))), false);
    }

    /**
     * @return array<string, string>
     */
    private function templatePresetPayload(string $preset): array
    {
        return match ($preset) {
            'salary' => [
                'heading' => __('INCOME STATEMENT LETTER'),
                'opening' => __('To whom it may concern,'),
                'main_paragraph' => __('This is to certify that {{ employee.name }} with employee ID {{ employee.nip }} is an employee of {{ company.name }} as {{ employee.job_title }} in {{ employee.division }}.'),
                'details_paragraph' => __('This statement is issued for {{ request.purpose }}. {{ request.details }}'),
                'closing' => __('This information is prepared based on employment and payroll records in the company system.'),
                'place_date' => '{{ date.today }}',
                'signature_title' => __('Finance / HR Department'),
                'signature_name' => '{{ company.name }}',
                'footer' => '{{ company.name }} · {{ company.support_contact }}',
            ],
            'upload' => [
                'heading' => __('EMPLOYEE DOCUMENT REQUEST'),
                'opening' => __('Hello {{ employee.name }},'),
                'main_paragraph' => __('Please upload {{ request.document_type }} through the Document Requests menu.'),
                'details_paragraph' => __('Request purpose: {{ request.purpose }}. Due date: {{ request.due_date }}. {{ request.details }}'),
                'closing' => __('Make sure the document is clear, valid, and matches the administration requirement.'),
                'place_date' => '{{ date.today }}',
                'signature_title' => __('HR / Finance Department'),
                'signature_name' => '{{ company.name }}',
                'footer' => '{{ company.name }} · {{ company.support_contact }}',
            ],
            default => [
                'heading' => '{{ request.document_type }}',
                'opening' => __('To whom it may concern,'),
                'main_paragraph' => __('This is to certify that {{ employee.name }} with employee ID {{ employee.nip }} works at {{ company.name }} as {{ employee.job_title }} in {{ employee.division }}.'),
                'details_paragraph' => __('This document is issued for: {{ request.purpose }}. {{ request.details }}'),
                'closing' => __('This document is prepared to be used as necessary.'),
                'place_date' => '{{ date.today }}',
                'signature_title' => __('Sincerely,'),
                'signature_name' => '{{ company.name }}',
                'footer' => '{{ company.name }} · {{ company.support_contact }}',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function layoutOptions(array $options = []): array
    {
        return $this->sanitizeLayoutOptions(array_replace($this->defaultLayoutOptions(), $options));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultLayoutOptions(): array
    {
        return [
            'show_logo' => true,
            'show_accents' => true,
            'show_document_meta' => true,
            'header_company_name' => Setting::getValue('app.company_name', config('app.name')),
            'header_address' => Setting::getValue('app.company_address', ''),
            'header_contact' => Setting::getValue('app.support_contact', config('mail.from.address')),
            'header_tagline' => __('Enterprise Workforce System'),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function sanitizeLayoutOptions(array $options): array
    {
        return [
            'show_logo' => (bool) ($options['show_logo'] ?? true),
            'show_accents' => (bool) ($options['show_accents'] ?? true),
            'show_document_meta' => (bool) ($options['show_document_meta'] ?? true),
            'header_company_name' => trim((string) ($options['header_company_name'] ?? '')),
            'header_address' => trim((string) ($options['header_address'] ?? '')),
            'header_contact' => trim((string) ($options['header_contact'] ?? '')),
            'header_tagline' => trim((string) ($options['header_tagline'] ?? '')),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, placeholder: string, example: string}>
     */
    private function templateVariableDefinitions(): array
    {
        $labels = [
            'employee.name' => __('Employee name'),
            'employee.nip' => __('Employee ID'),
            'employee.email' => __('Employee email'),
            'employee.phone' => __('Employee phone'),
            'employee.division' => __('Division'),
            'employee.job_title' => __('Job title'),
            'company.name' => __('Company name'),
            'company.address' => __('Company address'),
            'company.support_contact' => __('Support contact'),
            'request.id' => __('Request ID'),
            'request.purpose' => __('Purpose'),
            'request.details' => __('Request details'),
            'request.document_type' => __('Document type'),
            'request.due_date' => __('Due date'),
            'date.today' => __('Today'),
        ];

        return collect($this->sampleTemplateValues())
            ->map(fn (string $example, string $key): array => [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'placeholder' => sprintf('{{ %s }}', $key),
                'example' => $example,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function sampleTemplateValues(): array
    {
        return [
            'employee.name' => 'Andi Pratama',
            'employee.nip' => 'EMP-001',
            'employee.email' => 'andi@example.com',
            'employee.phone' => '0812-0000-0000',
            'employee.division' => 'Finance',
            'employee.job_title' => 'Finance Staff',
            'company.name' => Setting::getValue('app.company_name', config('app.name')),
            'company.address' => Setting::getValue('app.company_address', ''),
            'company.support_contact' => Setting::getValue('app.support_contact', 'hr@example.com'),
            'request.id' => 'REQ-0001',
            'request.purpose' => __('bank account opening'),
            'request.details' => __('Addressed to Bank Mandiri Jakarta branch.'),
            'request.document_type' => __('Employment Certificate'),
            'request.due_date' => now()->addWeek()->format('d M Y'),
            'date.today' => now()->format('d M Y'),
        ];
    }

    private function previewTemplateHtml(string $template): string
    {
        return $this->sanitizeTemplateHtml($this->previewTemplateText($template));
    }

    private function previewTemplateText(string $template): string
    {
        $values = $this->sampleTemplateValues();

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function (array $matches) use ($values): string {
            return e($values[$matches[1]] ?? '');
        }, $template) ?? $template;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function previewLayoutOptions(array $options): array
    {
        return collect($this->layoutOptions($options))
            ->map(fn ($value) => is_string($value) ? $this->previewTemplateText($value) : $value)
            ->all();
    }

    private function sanitizeTemplateHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><table><thead><tbody><tr><th><td><h1><h2><h3><h4><div><span><small><hr>');
    }

    private function deactivateSiblingTemplates(EmployeeDocumentTemplate $template): void
    {
        EmployeeDocumentTemplate::query()
            ->where('document_type_id', $template->document_type_id)
            ->whereKeyNot($template->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);
    }
}
