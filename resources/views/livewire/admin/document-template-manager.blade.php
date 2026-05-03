<x-admin.page-shell :title="__('Document Templates')" :description="__('Create requestable document templates with a simple guided flow.')">
    @php
        $selectedTypeId = (int) ($documentTemplateForm['document_type_id'] ?? 0);
        $currentType = $documentWorkflowTypes->firstWhere('id', $selectedTypeId);
        $templatesForCurrentType = $documentWorkflowTemplates->where('document_type_id', $selectedTypeId);
        $activeTemplate = $templatesForCurrentType->firstWhere('is_active', true);
    @endphp

    <style>
        .document-template-live-preview {
            --doc-preview-scale: .68;
        }

        @media (min-width: 1536px) {
            .document-template-live-preview {
                --doc-preview-scale: .72;
            }
        }

        .document-template-live-preview .employee-document-preview {
            background: transparent;
            margin: 0 auto;
            overflow: visible;
            padding: 0;
            width: max-content;
            zoom: var(--doc-preview-scale);
        }

        .document-template-live-preview .employee-document-page {
            box-shadow: 0 22px 55px rgba(15, 23, 42, .28);
            margin: 0;
        }

        @supports not (zoom: 1) {
            .document-template-live-preview .employee-document-preview {
                transform: scale(var(--doc-preview-scale));
                transform-origin: top center;
            }
        }
    </style>

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

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_38rem]">
            <section class="space-y-5">
                <x-admin.panel class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800 dark:bg-primary-950/40 dark:text-primary-100">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-700 text-white">1</span>
                                {{ __('Choose Document') }}
                            </div>
                            <h2 class="mt-3 text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $currentType?->name ?? __('Select document type') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Pick which document users/admins can request. Common workflow settings are kept here.') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-actions.button type="button" wire:click="startNewDocumentType" variant="secondary" size="sm">
                                <x-heroicon-m-plus class="h-4 w-4" />
                                {{ __('Create Type') }}
                            </x-actions.button>
                            @if ($currentType)
                                <x-actions.button type="button" wire:click="editSelectedDocumentType" variant="secondary" size="sm">
                                    <x-heroicon-m-pencil-square class="h-4 w-4" />
                                    {{ __('Edit Type') }}
                                </x-actions.button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                        <div>
                            <x-forms.label for="doc-template-type" value="{{ __('Document Type') }}" class="mb-1.5 block" />
                            <x-forms.select id="doc-template-type" wire:model.live="documentTemplateForm.document_type_id" class="w-full">
                                @foreach ($documentWorkflowTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }} · {{ strtoupper($type->category) }}</option>
                                @endforeach
                            </x-forms.select>
                            @if ($currentType)
                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $currentType->code }}</span>
                                    <span class="rounded-full {{ $currentType->employee_requestable ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }} px-2.5 py-1 font-medium">{{ __('Employee') }}</span>
                                    <span class="rounded-full {{ $currentType->admin_requestable ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }} px-2.5 py-1 font-medium">{{ __('Admin') }}</span>
                                    <span class="rounded-full {{ $currentType->auto_generate_enabled ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }} px-2.5 py-1 font-medium">{{ __('PDF') }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Active Template') }}</div>
                            <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $activeTemplate?->name ?? __('Not set') }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $templatesForCurrentType->count() }} {{ __('saved versions') }}
                            </div>
                        </div>
                    </div>

                    @if ($editingDocumentType)
                    <div class="mt-4 rounded-xl border border-primary-100 bg-white p-4 dark:border-primary-900/60 dark:bg-gray-900">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ blank($documentTypeForm['id'] ?? null) ? __('Create Document Type') : __('Edit Document Type') }}
                                </h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Use this only to add a new request category or change who can request it.') }}
                                </p>
                            </div>
                            <x-actions.secondary-button type="button" wire:click="cancelDocumentTypeEditor" size="sm">
                                {{ __('Cancel') }}
                            </x-actions.secondary-button>
                        </div>
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div>
                                <x-forms.label for="doc-type-name" value="{{ __('Name') }}" class="mb-1.5 block" />
                                <x-forms.input id="doc-type-name" wire:model.live="documentTypeForm.name" class="w-full" />
                                <x-forms.input-error for="documentTypeForm.name" class="mt-1" />
                            </div>
                            <div>
                                <x-forms.label for="doc-type-code" value="{{ __('Code') }}" class="mb-1.5 block" />
                                <x-forms.input id="doc-type-code" wire:model.live="documentTypeForm.code" class="w-full" />
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
                            <div>
                                <x-forms.label for="doc-type-desc" value="{{ __('Description') }}" class="mb-1.5 block" />
                                <x-forms.input id="doc-type-desc" wire:model.live="documentTypeForm.description" class="w-full" />
                            </div>
                            <div class="grid gap-2 text-sm text-gray-700 dark:text-gray-200 lg:col-span-2 sm:grid-cols-2 xl:grid-cols-5">
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTypeForm.employee_requestable" /> <span>{{ __('Employee request') }}</span></label>
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTypeForm.admin_requestable" /> <span>{{ __('Admin request') }}</span></label>
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTypeForm.requires_employee_upload" /> <span>{{ __('Need upload') }}</span></label>
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTypeForm.auto_generate_enabled" /> <span>{{ __('Generate PDF') }}</span></label>
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTypeForm.is_active" /> <span>{{ __('Active') }}</span></label>
                            </div>
                            <div class="lg:col-span-2 flex justify-end">
                                <x-actions.button type="button" wire:click="saveDocumentType" size="sm" variant="secondary">
                                    {{ blank($documentTypeForm['id'] ?? null) ? __('Create Type') : __('Save Type') }}
                                </x-actions.button>
                            </div>
                        </div>
                    </div>
                    @endif
                </x-admin.panel>

                <x-admin.panel class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800 dark:bg-primary-950/40 dark:text-primary-100">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-700 text-white">2</span>
                                {{ __('Write Template') }}
                            </div>
                            <h2 class="mt-3 text-lg font-semibold text-gray-950 dark:text-white">{{ __('Content and format') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Use presets for common letters, or switch to HTML only when custom formatting is needed.') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-actions.button type="button" wire:click="startNewDocumentTemplate" variant="secondary" size="sm">
                                <x-heroicon-m-plus class="h-4 w-4" />
                                {{ __('New Template') }}
                            </x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('letter')" variant="soft-primary" size="sm">{{ __('Letter') }}</x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('salary')" variant="soft-primary" size="sm">{{ __('Salary') }}</x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('upload')" variant="soft-primary" size="sm">{{ __('Upload') }}</x-actions.button>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ blank($documentTemplateForm['id'] ?? null) ? __('Creating new template') : __('Editing saved template') }}
                        </span>
                        <span class="mx-1 text-gray-400">·</span>
                        <span>{{ __('Live Preview updates as you type; save only when the draft is ready.') }}</span>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2 xl:grid-cols-6">
                        <div class="xl:col-span-3">
                            <x-forms.label for="doc-template-name" value="{{ __('Template Name') }}" class="mb-1.5 block" />
                            <x-forms.input id="doc-template-name" wire:model.live="documentTemplateForm.name" placeholder="{{ __('Standard employment letter') }}" class="w-full" />
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
                        <label class="flex items-end gap-2 pb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <x-forms.checkbox wire:model.live="documentTemplateForm.is_active" />
                            <span>{{ __('Set active') }}</span>
                        </label>
                    </div>

                    <div class="mt-5 flex w-full max-w-md rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-900">
                        <button type="button" wire:click="setTemplateEditorMode('builder')"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $templateEditorMode === 'builder' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-800 dark:text-primary-200' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100' }}">
                            {{ __('Builder') }}
                        </button>
                        <button type="button" wire:click="setTemplateEditorMode('html')"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $templateEditorMode === 'html' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-800 dark:text-primary-200' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100' }}">
                            {{ __('HTML') }}
                        </button>
                    </div>

                    <div class="mt-5 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Header & Footer') }}</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('These fields control the letterhead and fixed footer for this template.') }}
                                </p>
                            </div>
                            <div class="grid gap-2 text-xs font-medium text-gray-700 dark:text-gray-200 sm:grid-cols-3">
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTemplateForm.layout_options.show_logo" /> <span>{{ __('Logo') }}</span></label>
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTemplateForm.layout_options.show_accents" /> <span>{{ __('Accent') }}</span></label>
                                <label class="flex items-center gap-2"><x-forms.checkbox wire:model.live="documentTemplateForm.layout_options.show_document_meta" /> <span>{{ __('No/Date') }}</span></label>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div>
                                <x-forms.label for="template-header-company" value="{{ __('Header Company Name') }}" class="mb-1.5 block" />
                                <x-forms.input id="template-header-company" wire:model.live.debounce.300ms="documentTemplateForm.layout_options.header_company_name" class="w-full" />
                            </div>
                            <div>
                                <x-forms.label for="template-header-contact" value="{{ __('Header Contact') }}" class="mb-1.5 block" />
                                <x-forms.input id="template-header-contact" wire:model.live.debounce.300ms="documentTemplateForm.layout_options.header_contact" class="w-full" />
                            </div>
                            <div class="lg:col-span-2">
                                <x-forms.label for="template-header-address" value="{{ __('Header Address') }}" class="mb-1.5 block" />
                                <x-forms.input id="template-header-address" wire:model.live.debounce.300ms="documentTemplateForm.layout_options.header_address" class="w-full" />
                            </div>
                            <div>
                                <x-forms.label for="template-header-tagline" value="{{ __('Header Tagline') }}" class="mb-1.5 block" />
                                <x-forms.input id="template-header-tagline" wire:model.live.debounce.300ms="documentTemplateForm.layout_options.header_tagline" class="w-full" />
                            </div>
                            <div>
                                <x-forms.label for="template-footer" value="{{ __('Footer Text') }}" class="mb-1.5 block" />
                                <x-forms.input id="template-footer" wire:model.live.debounce.300ms="documentTemplateForm.footer" class="w-full" />
                            </div>
                        </div>
                    </div>

                    @if ($templateEditorMode === 'builder')
                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            <div class="lg:col-span-2">
                                <x-forms.label for="template-heading" value="{{ __('Title') }}" class="mb-1.5 block" />
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
                            <div class="lg:col-span-2">
                                <x-forms.label for="template-main" value="{{ __('Main Statement') }}" class="mb-1.5 block" />
                                <x-forms.textarea id="template-main" wire:model.live.debounce.300ms="templateBuilderForm.main_paragraph" rows="4" class="w-full" />
                            </div>
                            <div class="lg:col-span-2">
                                <x-forms.label for="template-details" value="{{ __('Purpose / Details') }}" class="mb-1.5 block" />
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
                        </div>
                    @else
                        <div class="mt-5 space-y-4">
                            <div>
                                <x-forms.label for="doc-template-body" value="{{ __('Body HTML') }}" class="mb-1.5 block" />
                                <x-forms.textarea id="doc-template-body" wire:model.live.debounce.500ms="documentTemplateForm.body" rows="16" class="block w-full font-mono text-xs" />
                                <x-forms.input-error for="documentTemplateForm.body" class="mt-1" />
                            </div>
                        </div>
                    @endif

                    <details class="mt-5 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        <summary class="cursor-pointer text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Available placeholders') }}</summary>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($documentTemplateVariables as $variable)
                                <div class="rounded-lg bg-white p-2 text-xs dark:bg-gray-800">
                                    <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $variable['label'] }}</div>
                                    <code class="mt-1 block text-primary-700 dark:text-primary-200">{{ $variable['placeholder'] }}</code>
                                </div>
                            @endforeach
                        </div>
                    </details>
                </x-admin.panel>

                <x-admin.panel class="p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800 dark:bg-primary-950/40 dark:text-primary-100">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-700 text-white">3</span>
                                {{ __('Review & Save') }}
                            </div>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Download a preview when needed, then save. If set active, this template becomes the generated PDF for this document type.') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-actions.button type="button" wire:click="downloadPreviewPdf" variant="secondary">
                                <x-heroicon-m-document-arrow-down class="h-4 w-4" />
                                {{ __('Download PDF') }}
                            </x-actions.button>
                            <x-actions.secondary-button type="button" wire:click="resetDocumentTemplateForm">
                                {{ __('Reset') }}
                            </x-actions.secondary-button>
                            <x-actions.button type="button" wire:click="saveDocumentTemplate">
                                <x-heroicon-m-check class="h-4 w-4" />
                                {{ blank($documentTemplateForm['id'] ?? null) ? __('Create Template') : __('Save Template') }}
                            </x-actions.button>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
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
                                    {{ strtoupper($template->paper_size) }} · {{ $template->orientation }}
                                </div>
                            </button>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400 sm:col-span-2 xl:col-span-3">
                                {{ __('No saved templates for this document type yet.') }}
                            </div>
                        @endforelse
                    </div>
                </x-admin.panel>
            </section>

            <aside class="xl:sticky xl:top-24 xl:self-start">
                <x-admin.panel class="overflow-hidden p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Live Preview') }}</h3>
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200">
                                    {{ __('Realtime') }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Updates while editing, using sample employee data') }}</p>
                        </div>
                        <x-actions.button type="button" wire:click="downloadPreviewPdf" variant="secondary" size="sm">
                            <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                            {{ __('PDF') }}
                        </x-actions.button>
                    </div>
                    <div class="relative">
                        <div wire:loading.flex
                            wire:target="templateBuilderForm,documentTemplateForm.name,documentTemplateForm.body,documentTemplateForm.footer,documentTemplateForm.paper_size,documentTemplateForm.orientation,documentTemplateForm.layout_options"
                            class="absolute right-3 top-3 z-10 items-center rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-primary-700 shadow-sm ring-1 ring-primary-100 dark:bg-gray-900/95 dark:text-primary-200 dark:ring-primary-900">
                            {{ __('Updating preview...') }}
                        </div>
                        <div class="document-template-live-preview max-h-[calc(100vh-9rem)] overflow-auto bg-slate-950 p-4">
                            {!! $templatePreviewHtml !!}
                        </div>
                    </div>
                </x-admin.panel>
            </aside>
        </div>
    </div>
</x-admin.page-shell>
