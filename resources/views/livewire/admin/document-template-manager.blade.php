<x-admin.page-shell :title="__('Document Templates')" :description="__('Build requestable document types and PDF templates with a live document preview.')">
    @php
        $selectedTypeId = (int) ($documentTemplateForm['document_type_id'] ?? 0);
        $currentType = $documentWorkflowTypes->firstWhere('id', $selectedTypeId);
        $templatesForCurrentType = $documentWorkflowTemplates->where('document_type_id', $selectedTypeId);
        $activeTemplate = $templatesForCurrentType->firstWhere('is_active', true);
        $isEditingTemplate = filled($documentTemplateForm['id'] ?? null);
    @endphp

    <div class="space-y-5">
        @if (session()->has('success'))
            <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('warning'))
            <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                {{ session('warning') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-800 dark:bg-primary-950/40 dark:text-primary-100">
                        <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                        {{ __('Enterprise Builder') }}
                    </span>
                    @if ($currentType)
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $currentType->name }}
                        </span>
                    @endif
                    @if ($isEditingTemplate)
                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-950/40 dark:text-sky-200">
                            {{ __('Editing') }}
                        </span>
                    @endif
                </div>
                <h2 class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $documentTemplateForm['name'] ?: __('Untitled document template') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Choose a document type, compose the wording, check the PDF preview, then save it as the active generated template.') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-actions.button href="{{ route('admin.document-templates.library') }}" variant="secondary" size="sm">
                    <x-heroicon-m-rectangle-stack class="h-4 w-4" />
                    {{ __('Library') }}
                </x-actions.button>
                <x-actions.button type="button" wire:click="downloadPreviewPdf" variant="secondary" size="sm">
                    <x-heroicon-m-document-arrow-down class="h-4 w-4" />
                    {{ __('Preview PDF') }}
                </x-actions.button>
                <x-actions.secondary-button type="button" wire:click="resetDocumentTemplateForm" class="min-h-[2.25rem] px-3 py-1.5 text-sm">
                    {{ __('Reset') }}
                </x-actions.secondary-button>
                <x-actions.button type="button" wire:click="saveDocumentTemplate" size="sm">
                    <x-heroicon-m-check class="h-4 w-4" />
                    {{ __('Save Template') }}
                </x-actions.button>
            </div>
        </div>

        <div class="grid gap-5 2xl:grid-cols-[19rem_minmax(0,1fr)_43rem]">
            <aside class="space-y-5">
                <x-admin.panel class="overflow-hidden p-0">
                    <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Document Types') }}</h3>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Workflow catalog') }}</p>
                            </div>
                            <x-actions.icon-button type="button" wire:click="resetDocumentTypeForm" variant="neutral" label="{{ __('New document type') }}">
                                <x-heroicon-m-plus class="h-5 w-5" />
                            </x-actions.icon-button>
                        </div>
                    </div>

                    <div class="max-h-[28rem] divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                        @foreach ($documentWorkflowTypes as $type)
                            <button type="button" wire:click="editDocumentType({{ $type->id }})"
                                class="block w-full px-4 py-3 text-left transition hover:bg-primary-50/70 dark:hover:bg-primary-950/20 {{ (int) ($documentTypeForm['id'] ?? 0) === $type->id ? 'bg-primary-50 dark:bg-primary-950/30' : '' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $type->name }}</div>
                                        <div class="mt-1 flex flex-wrap gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                            <span>{{ $type->code }}</span>
                                            <span>·</span>
                                            <span>{{ strtoupper($type->category) }}</span>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-primary-700 ring-1 ring-primary-100 dark:bg-gray-900 dark:text-primary-200 dark:ring-primary-900/60">
                                        {{ $type->templates_count }}
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </x-admin.panel>

                <x-admin.panel class="p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Type Settings') }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Availability and generation rules') }}</p>
                        </div>
                        <x-actions.button type="button" wire:click="saveDocumentType" size="sm" variant="secondary">
                            {{ __('Save') }}
                        </x-actions.button>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div>
                            <x-forms.label for="doc-type-name" value="{{ __('Name') }}" class="mb-1.5 block" />
                            <x-forms.input id="doc-type-name" wire:model.live="documentTypeForm.name" placeholder="{{ __('Employment Certificate') }}" class="w-full" />
                            <x-forms.input-error for="documentTypeForm.name" class="mt-1" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-1">
                            <div>
                                <x-forms.label for="doc-type-code" value="{{ __('Code') }}" class="mb-1.5 block" />
                                <x-forms.input id="doc-type-code" wire:model.live="documentTypeForm.code" placeholder="employment_certificate" class="w-full" />
                                <x-forms.input-error for="documentTypeForm.code" class="mt-1" />
                            </div>
                            <div>
                                <x-forms.label for="doc-type-category" value="{{ __('Category') }}" class="mb-1.5 block" />
                                <x-forms.select id="doc-type-category" wire:model.live="documentTypeForm.category" class="w-full">
                                    <option value="hr">HR</option>
                                    <option value="finance">Finance</option>
                                    <option value="payroll">Payroll</option>
                                    <option value="legal">Legal</option>
                                </x-forms.select>
                            </div>
                        </div>
                        <div>
                            <x-forms.label for="doc-type-desc" value="{{ __('Description') }}" class="mb-1.5 block" />
                            <x-forms.textarea id="doc-type-desc" wire:model.live="documentTypeForm.description" rows="2" placeholder="{{ __('Internal admin note') }}" class="w-full" />
                        </div>

                        <div class="grid gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <label class="flex items-center gap-2">
                                <x-forms.checkbox wire:model.live="documentTypeForm.employee_requestable" />
                                <span>{{ __('Employee can request') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <x-forms.checkbox wire:model.live="documentTypeForm.admin_requestable" />
                                <span>{{ __('Admin can request') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <x-forms.checkbox wire:model.live="documentTypeForm.requires_employee_upload" />
                                <span>{{ __('Employee must upload file') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <x-forms.checkbox wire:model.live="documentTypeForm.auto_generate_enabled" />
                                <span>{{ __('Allow PDF generation') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <x-forms.checkbox wire:model.live="documentTypeForm.is_active" />
                                <span>{{ __('Active') }}</span>
                            </label>
                        </div>
                    </div>
                </x-admin.panel>
            </aside>

            <section class="space-y-5">
                <x-admin.panel class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('Template Workspace') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Start from a preset, then adjust content blocks or HTML.') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-actions.button type="button" wire:click="useTemplatePreset('letter')" variant="soft-primary" size="sm">
                                <x-heroicon-m-document-text class="h-4 w-4" />
                                {{ __('Letter') }}
                            </x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('salary')" variant="soft-primary" size="sm">
                                <x-heroicon-m-banknotes class="h-4 w-4" />
                                {{ __('Salary') }}
                            </x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('upload')" variant="soft-primary" size="sm">
                                <x-heroicon-m-arrow-up-tray class="h-4 w-4" />
                                {{ __('Upload') }}
                            </x-actions.button>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2 xl:grid-cols-6">
                        <div class="xl:col-span-2">
                            <x-forms.label for="doc-template-type" value="{{ __('Document Type') }}" class="mb-1.5 block" />
                            <x-forms.select id="doc-template-type" wire:model.live="documentTemplateForm.document_type_id" class="w-full">
                                @foreach ($documentWorkflowTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>
                        <div class="xl:col-span-2">
                            <x-forms.label for="doc-template-name" value="{{ __('Template Name') }}" class="mb-1.5 block" />
                            <x-forms.input id="doc-template-name" wire:model.live="documentTemplateForm.name" placeholder="{{ __('Default letter') }}" class="w-full" />
                            <x-forms.input-error for="documentTemplateForm.name" class="mt-1" />
                        </div>
                        <div>
                            <x-forms.label for="doc-template-paper" value="{{ __('Paper') }}" class="mb-1.5 block" />
                            <x-forms.select id="doc-template-paper" wire:model.live="documentTemplateForm.paper_size" class="w-full">
                                <option value="a4">A4</option>
                                <option value="letter">Letter</option>
                                <option value="legal">Legal</option>
                            </x-forms.select>
                        </div>
                        <div>
                            <x-forms.label for="doc-template-orientation" value="{{ __('Orientation') }}" class="mb-1.5 block" />
                            <x-forms.select id="doc-template-orientation" wire:model.live="documentTemplateForm.orientation" class="w-full">
                                <option value="portrait">{{ __('Portrait') }}</option>
                                <option value="landscape">{{ __('Landscape') }}</option>
                            </x-forms.select>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <x-forms.checkbox wire:model.live="documentTemplateForm.is_active" />
                            <span>{{ __('Publish as active template') }}</span>
                        </label>
                        @if ($activeTemplate)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Current active') }}: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $activeTemplate->name }}</span>
                            </span>
                        @endif
                    </div>
                </x-admin.panel>

                <x-admin.panel class="p-0">
                    <div class="border-b border-gray-100 p-4 dark:border-gray-800">
                        <div class="flex w-full rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-900">
                            <button type="button" wire:click="setTemplateEditorMode('builder')"
                                class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $templateEditorMode === 'builder' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-800 dark:text-primary-200' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100' }}">
                                {{ __('Builder') }}
                            </button>
                            <button type="button" wire:click="setTemplateEditorMode('html')"
                                class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $templateEditorMode === 'html' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-800 dark:text-primary-200' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100' }}">
                                {{ __('HTML') }}
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        @if ($templateEditorMode === 'builder')
                            <div class="grid gap-4 xl:grid-cols-2">
                                <div class="xl:col-span-2">
                                    <x-forms.label for="template-heading" value="{{ __('Document Title') }}" class="mb-1.5 block" />
                                    <x-forms.input id="template-heading" wire:model.live.debounce.300ms="templateBuilderForm.heading" class="w-full text-base font-semibold" />
                                </div>
                                <div>
                                    <x-forms.label for="template-opening" value="{{ __('Opening') }}" class="mb-1.5 block" />
                                    <x-forms.textarea id="template-opening" wire:model.live.debounce.300ms="templateBuilderForm.opening" rows="3" class="w-full" />
                                </div>
                                <div>
                                    <x-forms.label for="template-closing" value="{{ __('Closing') }}" class="mb-1.5 block" />
                                    <x-forms.textarea id="template-closing" wire:model.live.debounce.300ms="templateBuilderForm.closing" rows="3" class="w-full" />
                                </div>
                                <div class="xl:col-span-2">
                                    <x-forms.label for="template-main" value="{{ __('Employee Statement') }}" class="mb-1.5 block" />
                                    <x-forms.textarea id="template-main" wire:model.live.debounce.300ms="templateBuilderForm.main_paragraph" rows="4" class="w-full" />
                                </div>
                                <div class="xl:col-span-2">
                                    <x-forms.label for="template-details" value="{{ __('Purpose / Detail Statement') }}" class="mb-1.5 block" />
                                    <x-forms.textarea id="template-details" wire:model.live.debounce.300ms="templateBuilderForm.details_paragraph" rows="4" class="w-full" />
                                </div>
                                <div>
                                    <x-forms.label for="template-signature-title" value="{{ __('Signature Label') }}" class="mb-1.5 block" />
                                    <x-forms.input id="template-signature-title" wire:model.live.debounce.300ms="templateBuilderForm.signature_title" class="w-full" />
                                </div>
                                <div>
                                    <x-forms.label for="template-signature-name" value="{{ __('Signer') }}" class="mb-1.5 block" />
                                    <x-forms.input id="template-signature-name" wire:model.live.debounce.300ms="templateBuilderForm.signature_name" class="w-full" />
                                </div>
                                <div class="xl:col-span-2">
                                    <x-forms.label for="template-footer" value="{{ __('Footer') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                                    <x-forms.input id="template-footer" wire:model.live.debounce.300ms="templateBuilderForm.footer" class="w-full" />
                                </div>
                            </div>
                        @else
                            <div class="space-y-4">
                                <div>
                                    <x-forms.label for="doc-template-body" value="{{ __('Body HTML') }}" class="mb-1.5 block" />
                                    <x-forms.textarea id="doc-template-body" wire:model.live.debounce.500ms="documentTemplateForm.body" rows="18" class="block w-full font-mono text-xs" />
                                    <x-forms.input-error for="documentTemplateForm.body" class="mt-1" />
                                </div>
                                <div>
                                    <x-forms.label for="doc-template-footer" value="{{ __('Footer') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                                    <x-forms.textarea id="doc-template-footer" wire:model.live.debounce.500ms="documentTemplateForm.footer" rows="2" class="block w-full text-xs" />
                                </div>
                            </div>
                        @endif
                    </div>
                </x-admin.panel>

                <x-admin.panel class="p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Template Versions') }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Recent templates for the selected document type') }}</p>
                        </div>
                        <x-actions.button href="{{ route('admin.document-templates.library') }}" variant="secondary" size="sm">
                            {{ __('Manage All') }}
                        </x-actions.button>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @forelse ($templatesForCurrentType->take(6) as $template)
                            <button type="button" wire:click="editDocumentTemplate({{ $template->id }})"
                                class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-left transition hover:border-primary-200 hover:bg-primary-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-800 dark:hover:bg-primary-950/20">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $template->name }}</span>
                                    @if ($template->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">{{ __('Active') }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ strtoupper($template->paper_size) }} · {{ $template->orientation }} · {{ $template->generated_requests_count }} {{ __('generated') }}
                                </div>
                            </button>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400 sm:col-span-2">
                                {{ __('No saved templates for this document type yet.') }}
                            </div>
                        @endforelse
                    </div>
                </x-admin.panel>
            </section>

            <aside class="space-y-5 2xl:sticky 2xl:top-24 2xl:self-start">
                <x-admin.panel class="overflow-hidden p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Live Preview') }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Sample data, exact PDF shell') }}</p>
                        </div>
                        <x-actions.button type="button" wire:click="downloadPreviewPdf" variant="secondary" size="sm">
                            <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                            {{ __('PDF') }}
                        </x-actions.button>
                    </div>
                    <div class="max-h-[72vh] overflow-auto bg-slate-950 p-3">
                        {!! $templatePreviewHtml !!}
                    </div>
                </x-admin.panel>

                <x-admin.panel class="p-4">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Variables') }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Click-copy support depends on the browser; these placeholders work in builder and HTML mode.') }}</p>
                    <div class="mt-3 grid gap-2">
                        @foreach ($documentTemplateVariables as $variable)
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-2 dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $variable['label'] }}</span>
                                    <code class="rounded bg-white px-1.5 py-0.5 text-[11px] text-primary-700 dark:bg-gray-800 dark:text-primary-200">{{ $variable['placeholder'] }}</code>
                                </div>
                                <div class="mt-1 truncate text-[11px] text-gray-500 dark:text-gray-400">{{ $variable['example'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-admin.panel>
            </aside>
        </div>
    </div>
</x-admin.page-shell>
