<?php

namespace App\Livewire\Admin;

use App\Models\EmployeeDocumentTemplate;
use App\Models\Setting;
use App\Support\EmployeeDocumentPdfFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DocumentTemplateLibrary extends Component
{
    public ?int $selectedTemplateId = null;

    public bool $confirmingTemplateDeletion = false;

    public ?int $templateDeletionId = null;

    public function mount(): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->selectedTemplateId = EmployeeDocumentTemplate::query()
            ->latest()
            ->value('id');
    }

    public function selectTemplate(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        $this->selectedTemplateId = $id;
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

        $this->selectedTemplateId = $copy->id;
        session()->flash('success', __('Template duplicated.'));
    }

    public function activateTemplate(int $id): void
    {
        Gate::authorize('manageDocumentTemplates');

        $template = EmployeeDocumentTemplate::with('documentType')->findOrFail($id);
        $template->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        EmployeeDocumentTemplate::query()
            ->where('document_type_id', $template->document_type_id)
            ->whereKeyNot($template->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);

        $template->documentType?->update(['auto_generate_enabled' => true]);
        session()->flash('success', __('Template activated.'));
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

        session()->flash('success', __('Template deactivated.'));
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

        if ($this->selectedTemplateId === $template->id) {
            $this->selectedTemplateId = EmployeeDocumentTemplate::query()->latest()->value('id');
        }

        $this->cancelDeleteTemplate();
    }

    public function downloadPreviewPdf()
    {
        Gate::authorize('manageDocumentTemplates');

        $template = $this->selectedTemplate();

        abort_if(! $template, 404);

        $pdf = app(EmployeeDocumentPdfFactory::class)->make(
            $this->previewTemplateHtml($template->body),
            $template->footer ? $this->previewTemplateText($template->footer) : null,
            $template->paper_size ?: 'a4',
            $template->orientation ?: 'portrait',
            $this->previewDocumentMeta($template->documentType?->name ?? $template->name),
            $template->layout_options ?? [],
        );

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            Str::slug($template->name).'-preview.pdf',
        );
    }

    public function render()
    {
        Gate::authorize('manageDocumentTemplates');

        $templates = EmployeeDocumentTemplate::query()
            ->with('documentType')
            ->withCount('generatedRequests')
            ->latest()
            ->get();

        $selectedTemplate = $this->selectedTemplate();

        return view('livewire.admin.document-template-library', [
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'templatePreviewHtml' => $selectedTemplate ? app(EmployeeDocumentPdfFactory::class)->previewHtml(
                $this->previewTemplateHtml($selectedTemplate->body),
                $selectedTemplate->footer ? $this->previewTemplateText($selectedTemplate->footer) : null,
                $this->previewDocumentMeta($selectedTemplate->documentType?->name ?? $selectedTemplate->name),
                $selectedTemplate->layout_options ?? [],
            ) : '',
        ]);
    }

    private function selectedTemplate(): ?EmployeeDocumentTemplate
    {
        if (! $this->selectedTemplateId) {
            return null;
        }

        return EmployeeDocumentTemplate::query()
            ->with('documentType')
            ->withCount('generatedRequests')
            ->find($this->selectedTemplateId);
    }

    private function previewTemplateHtml(string $template): string
    {
        return $this->sanitizeTemplateHtml($this->previewTemplateText($template));
    }

    /**
     * @return array<string, string>
     */
    private function previewDocumentMeta(string $subject): array
    {
        return [
            'Nomor' => 'DOC/PREVIEW/'.now()->format('Ymd').'/0001',
            'Tanggal' => now()->translatedFormat('d F Y'),
            'Perihal' => $subject,
        ];
    }

    private function previewTemplateText(string $template): string
    {
        $values = $this->sampleTemplateValues();

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function (array $matches) use ($values): string {
            return e($values[$matches[1]] ?? '');
        }, $template) ?? $template;
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
            'request.purpose' => 'pembukaan rekening bank',
            'request.details' => 'Ditujukan kepada Bank Mandiri cabang Jakarta.',
            'request.document_type' => 'Surat Keterangan Kerja',
            'request.due_date' => now()->addWeek()->format('d M Y'),
            'date.today' => now()->format('d M Y'),
        ];
    }

    private function sanitizeTemplateHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><table><thead><tbody><tr><th><td><h1><h2><h3><h4><div><span><small><hr>');
    }
}
