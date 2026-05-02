<x-admin.page-shell :title="__('Template Library')" :description="__('Review saved templates, check PDF preview, and manage active template versions.')">
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

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-actions.button href="{{ route('admin.document-templates') }}" variant="secondary">
                <x-heroicon-m-arrow-left class="h-4 w-4" />
                {{ __('Back to Builder') }}
            </x-actions.button>
            @if ($selectedTemplate)
                <x-actions.button type="button" wire:click="downloadPreviewPdf" variant="primary">
                    <x-heroicon-m-document-arrow-down class="h-4 w-4" />
                    {{ __('Download Preview PDF') }}
                </x-actions.button>
            @endif
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <x-admin.panel class="p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Saved Templates') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Select a template to preview or manage it.') }}</p>

                <div class="mt-5 overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($templates as $template)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3">
                                        <button type="button" wire:click="selectTemplate({{ $template->id }})" class="block w-full text-left">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-gray-900 dark:text-white">{{ $template->name }}</span>
                                                @if ($template->is_active)
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('Active') }}</span>
                                                @else
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ __('Draft') }}</span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $template->documentType?->name }} · {{ strtoupper($template->paper_size) }} · {{ $template->orientation }} ·
                                                {{ trans_choice(':count generated document|:count generated documents', $template->generated_requests_count, ['count' => $template->generated_requests_count]) }}
                                            </div>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <x-actions.icon-button href="{{ route('admin.document-templates', ['template' => $template->id]) }}" variant="primary" label="{{ __('Edit document template') }}: {{ $template->name }}">
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button wire:click="duplicateTemplate({{ $template->id }})" variant="neutral" label="{{ __('Duplicate template') }}: {{ $template->name }}">
                                                <x-heroicon-m-document-duplicate class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            @if ($template->is_active)
                                                <x-actions.icon-button wire:click="deactivateTemplate({{ $template->id }})" variant="warning" label="{{ __('Deactivate template') }}: {{ $template->name }}">
                                                    <x-heroicon-m-pause class="h-5 w-5" />
                                                </x-actions.icon-button>
                                            @else
                                                <x-actions.icon-button wire:click="activateTemplate({{ $template->id }})" variant="success" label="{{ __('Make active template') }}: {{ $template->name }}">
                                                    <x-heroicon-m-check-circle class="h-5 w-5" />
                                                </x-actions.icon-button>
                                            @endif
                                            <x-actions.icon-button wire:click="confirmDeleteTemplate({{ $template->id }})" variant="danger" label="{{ __('Delete template') }}: {{ $template->name }}">
                                                <x-heroicon-m-trash class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No templates yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.panel>

            <x-admin.panel class="p-6">
                @if ($selectedTemplate)
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $selectedTemplate->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $selectedTemplate->documentType?->name }} · {{ strtoupper($selectedTemplate->paper_size) }} · {{ $selectedTemplate->orientation }}
                            </p>
                        </div>
                        <x-actions.button href="{{ route('admin.document-templates', ['template' => $selectedTemplate->id]) }}" variant="secondary" size="sm">
                            <x-heroicon-m-pencil-square class="h-4 w-4" />
                            {{ __('Edit') }}
                        </x-actions.button>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-950">
                        <div class="mx-auto min-h-[520px] max-w-3xl bg-white px-10 py-10 text-sm leading-7 text-gray-900 dark:bg-white dark:text-gray-900">
                            {!! $templatePreviewBody !!}

                            @if ($templatePreviewFooter)
                                <div class="mt-10 border-t border-gray-200 pt-3 text-center text-xs text-gray-500">
                                    {{ $templatePreviewFooter }}
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No template selected.') }}
                    </div>
                @endif
            </x-admin.panel>
        </div>
    </div>

    <x-overlays.confirmation-modal wire:model.live="confirmingTemplateDeletion">
        <x-slot name="title">{{ __('Delete Template') }}</x-slot>
        <x-slot name="content">
            {{ __('Unused templates will be deleted. Templates already used by generated documents will be deactivated to preserve document history.') }}
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button type="button" wire:click="cancelDeleteTemplate" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" type="button" wire:click="deleteTemplate" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>
</x-admin.page-shell>
