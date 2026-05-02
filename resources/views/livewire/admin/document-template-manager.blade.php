<x-admin.page-shell :title="__('Document Templates')" :description="__('Configure document request types, generated PDF wording, and the employee-facing document workflow.')">
    <div class="space-y-6">
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

        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <x-admin.panel class="p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Document Types') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Set which documents can be requested, uploaded, or generated.') }}</p>
                    </div>
                    <x-actions.button type="button" wire:click="resetDocumentTypeForm" variant="secondary" size="sm">
                        <x-heroicon-m-plus class="h-4 w-4" />
                        {{ __('New Type') }}
                    </x-actions.button>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <x-forms.label for="doc-type-name" value="{{ __('Name') }}" class="mb-1.5 block" />
                        <x-forms.input id="doc-type-name" wire:model.live="documentTypeForm.name" placeholder="{{ __('Bank Account') }}" class="w-full" />
                        <x-forms.input-error for="documentTypeForm.name" class="mt-1" />
                    </div>
                    <div>
                        <x-forms.label for="doc-type-code" value="{{ __('Code') }}" class="mb-1.5 block" />
                        <x-forms.input id="doc-type-code" wire:model.live="documentTypeForm.code" placeholder="bank_account" class="w-full" />
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
                        <x-forms.input id="doc-type-desc" wire:model.live="documentTypeForm.description" placeholder="{{ __('Internal admin note') }}" class="w-full" />
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-forms.checkbox wire:model.live="documentTypeForm.employee_requestable" />
                        <span>{{ __('Employee can request') }}</span>
                    </label>
                    <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-forms.checkbox wire:model.live="documentTypeForm.admin_requestable" />
                        <span>{{ __('Admin can request') }}</span>
                    </label>
                    <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-forms.checkbox wire:model.live="documentTypeForm.requires_employee_upload" />
                        <span>{{ __('Employee must upload file') }}</span>
                    </label>
                    <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-forms.checkbox wire:model.live="documentTypeForm.auto_generate_enabled" />
                        <span>{{ __('Allow PDF generation') }}</span>
                    </label>
                    <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-forms.checkbox wire:model.live="documentTypeForm.is_active" />
                        <span>{{ __('Active') }}</span>
                    </label>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <x-actions.secondary-button type="button" wire:click="resetDocumentTypeForm">{{ __('Reset') }}</x-actions.secondary-button>
                    <x-actions.button type="button" wire:click="saveDocumentType">{{ __('Save Type') }}</x-actions.button>
                </div>

                <div class="mt-6 overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($documentWorkflowTypes as $type)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $type->name }}</div>
                                        <div class="mt-1 flex flex-wrap gap-1 text-xs text-gray-500">
                                            <span>{{ $type->code }}</span>
                                            <span>·</span>
                                            <span>{{ strtoupper($type->category) }}</span>
                                            <span>·</span>
                                            <span>{{ $type->templates_count }} {{ __('templates') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-actions.icon-button wire:click="editDocumentType({{ $type->id }})" variant="primary" label="{{ __('Edit document type') }}: {{ $type->name }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.panel>

            <x-admin.panel class="p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('PDF Template Builder') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Use presets and fields for normal admins, or switch to HTML for advanced formatting.') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-actions.button href="{{ route('admin.document-templates.library') }}" variant="secondary" size="sm">
                                <x-heroicon-m-eye class="h-4 w-4" />
                                {{ __('View Library') }}
                            </x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('letter')" variant="soft-primary" size="sm">{{ __('Letter') }}</x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('salary')" variant="soft-primary" size="sm">{{ __('Salary') }}</x-actions.button>
                            <x-actions.button type="button" wire:click="useTemplatePreset('upload')" variant="soft-primary" size="sm">{{ __('Upload Request') }}</x-actions.button>
                        </div>
                    </div>

                    <div class="mt-5 grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div class="lg:col-span-2">
                            <x-forms.label for="doc-template-type" value="{{ __('Document Type') }}" class="mb-1.5 block" />
                            <x-forms.select id="doc-template-type" wire:model.live="documentTemplateForm.document_type_id" class="w-full">
                                @foreach ($documentWorkflowTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>
                        <div>
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
                        <label class="flex items-end gap-2 pb-2 text-sm text-gray-700 dark:text-gray-200">
                            <x-forms.checkbox wire:model.live="documentTemplateForm.is_active" />
                            <span>{{ __('Active template') }}</span>
                        </label>
                    </div>

                    <div class="mt-5 flex w-full rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-900">
                        <button type="button" wire:click="setTemplateEditorMode('builder')"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $templateEditorMode === 'builder' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-800 dark:text-primary-200' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100' }}">
                            {{ __('Builder') }}
                        </button>
                        <button type="button" wire:click="setTemplateEditorMode('html')"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $templateEditorMode === 'html' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-800 dark:text-primary-200' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100' }}">
                            {{ __('HTML') }}
                        </button>
                    </div>

                    @if ($templateEditorMode === 'builder')
                        <div class="mt-5 grid min-w-0 gap-4 xl:grid-cols-2">
                            <div class="xl:col-span-2">
                                <x-forms.label for="template-heading" value="{{ __('Title') }}" class="mb-1.5 block" />
                                <x-forms.input id="template-heading" wire:model.live.debounce.300ms="templateBuilderForm.heading" class="w-full" />
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
                                <x-forms.label for="template-main" value="{{ __('Main Paragraph') }}" class="mb-1.5 block" />
                                <x-forms.textarea id="template-main" wire:model.live.debounce.300ms="templateBuilderForm.main_paragraph" rows="4" class="w-full" />
                            </div>
                            <div class="xl:col-span-2">
                                <x-forms.label for="template-details" value="{{ __('Purpose / Details Paragraph') }}" class="mb-1.5 block" />
                                <x-forms.textarea id="template-details" wire:model.live.debounce.300ms="templateBuilderForm.details_paragraph" rows="4" class="w-full" />
                            </div>
                            <div class="grid gap-3 xl:col-span-2 md:grid-cols-3">
                                <div>
                                    <x-forms.label for="template-place-date" value="{{ __('Place / Date') }}" class="mb-1.5 block" />
                                    <x-forms.input id="template-place-date" wire:model.live.debounce.300ms="templateBuilderForm.place_date" class="w-full" />
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
                            <div class="xl:col-span-2">
                                <x-forms.label for="template-footer" value="{{ __('Footer') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                                <x-forms.input id="template-footer" wire:model.live.debounce.300ms="templateBuilderForm.footer" class="w-full" />
                            </div>
                        </div>
                    @else
                        <div class="mt-5">
                            <x-forms.label for="doc-template-body" value="{{ __('Body HTML') }}" class="mb-1.5 block" />
                            <x-forms.textarea id="doc-template-body" wire:model.live.debounce.500ms="documentTemplateForm.body" rows="14" class="block w-full font-mono text-xs" />
                            <x-forms.input-error for="documentTemplateForm.body" class="mt-1" />
                        </div>
                        <div class="mt-3">
                            <x-forms.label for="doc-template-footer" value="{{ __('Footer') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                            <x-forms.textarea id="doc-template-footer" wire:model.live.debounce.500ms="documentTemplateForm.footer" rows="2" class="block w-full text-xs" />
                        </div>
                    @endif

                    <div class="mt-5 flex justify-end gap-2">
                        <x-actions.secondary-button type="button" wire:click="resetDocumentTemplateForm">{{ __('Reset') }}</x-actions.secondary-button>
                        <x-actions.button type="button" wire:click="saveDocumentTemplate">{{ __('Save Template') }}</x-actions.button>
                    </div>
            </x-admin.panel>
        </div>
    </div>
</x-admin.page-shell>
