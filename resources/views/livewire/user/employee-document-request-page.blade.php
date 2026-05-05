<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="document-request-title" class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Document Requests')"
                title-id="document-request-title"
                class="border-b-0">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50 via-white to-sky-50 text-indigo-700 ring-1 ring-inset ring-indigo-100 shadow-sm dark:from-indigo-900/30 dark:via-gray-800 dark:to-sky-900/20 dark:text-indigo-300 dark:ring-indigo-800/60">
                        <x-heroicon-o-document-text class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    <x-actions.button type="button" wire:click="create" class="w-full sm:w-auto">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Request') }}</span>
                    </x-actions.button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if (session()->has('success'))
                    <div class="mb-4 rounded-xl border border-green-100 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mb-3 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="grid grid-cols-2 divide-x divide-y divide-gray-100 sm:grid-cols-4 sm:divide-y-0 dark:divide-gray-700">
                        <div class="min-w-0 px-2 py-2.5 text-center">
                            <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-200">
                                <x-heroicon-m-folder-open class="h-4 w-4" />
                            </div>
                            <div class="mt-1 text-lg font-semibold leading-none text-gray-950 dark:text-white">{{ $requestStats['total'] }}</div>
                            <div class="mt-1 truncate text-[10px] font-semibold uppercase tracking-wide text-primary-800 dark:text-primary-200">{{ __('Total') }}</div>
                        </div>
                        <div class="min-w-0 px-2 py-2.5 text-center">
                            <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                                <x-heroicon-m-clock class="h-4 w-4" />
                            </div>
                            <div class="mt-1 text-lg font-semibold leading-none text-gray-950 dark:text-white">{{ $requestStats['in_progress'] }}</div>
                            <div class="mt-1 truncate text-[10px] font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-200">{{ __('In Progress') }}</div>
                        </div>
                        <div class="min-w-0 px-2 py-2.5 text-center">
                            <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200">
                                <x-heroicon-m-check-circle class="h-4 w-4" />
                            </div>
                            <div class="mt-1 text-lg font-semibold leading-none text-gray-950 dark:text-white">{{ $requestStats['ready'] }}</div>
                            <div class="mt-1 truncate text-[10px] font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">{{ __('Ready') }}</div>
                        </div>
                        <div class="min-w-0 px-2 py-2.5 text-center">
                            <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-200">
                                <x-heroicon-m-arrow-up-tray class="h-4 w-4" />
                            </div>
                            <div class="mt-1 text-lg font-semibold leading-none text-gray-950 dark:text-white">{{ $requestStats['needs_upload'] }}</div>
                            <div class="mt-1 truncate text-[10px] font-semibold uppercase tracking-wide text-sky-800 dark:text-sky-200">{{ __('Upload') }}</div>
                        </div>
                    </div>
                </div>

                <div class="hidden overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
                    <div class="overflow-x-visible">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Document') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Purpose') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Admin Note') }}</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($requests as $request)
                                    <tr>
                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-semibold">{{ $request->documentTypeLabel() }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request->created_at->diffForHumans() }}</div>
                                            @if ($request->due_date)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Due') }} {{ $request->due_date->format('d M Y') }}</div>
                                            @endif
                                            @if ($request->requester && $request->requested_by !== $request->user_id)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Requested by') }} {{ $request->requester->name }}</div>
                                            @endif
                                        </td>
                                        <td class="max-w-sm px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $request->purpose }}</div>
                                            @if ($request->details)
                                                <div class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $request->details }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === \App\Models\EmployeeDocumentRequest::STATUS_READY ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($request->status === \App\Models\EmployeeDocumentRequest::STATUS_REJECTED ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($request->status === \App\Models\EmployeeDocumentRequest::STATUS_UPLOAD_PROCESSING ? 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200')) }}">
                                                {{ $request->statusLabel() }}
                                            </span>
                                            @if ($request->reviewer)
                                                <div class="mt-1 text-[10px] text-gray-400">{{ __('by') }} {{ $request->reviewer->name }}</div>
                                            @endif
                                        </td>
                                        <td class="max-w-sm px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $request->fulfillment_note ?: $request->rejection_note ?: '-' }}
                                        </td>
                                        <td class="px-5 py-4 text-right text-sm">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                @can('upload', $request)
                                                    <x-actions.icon-button wire:click="prepareUpload({{ $request->id }})" variant="primary" label="{{ __('Upload document') }}: {{ $request->documentTypeLabel() }}" data-e2e="document-upload-open" data-request-id="{{ $request->id }}">
                                                        <x-heroicon-m-arrow-up-tray class="h-5 w-5" />
                                                    </x-actions.icon-button>
                                                @endcan
                                                @if ($request->generated_path)
                                                    <x-actions.icon-button href="{{ route('document-requests.download', $request) }}" variant="neutral" label="{{ __('Download generated document') }}: {{ $request->documentTypeLabel() }}">
                                                        <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                                                    </x-actions.icon-button>
                                                @endif
                                                @if ($request->uploaded_path)
                                                    <x-actions.icon-button href="{{ route('document-requests.uploaded', $request) }}" variant="neutral" label="{{ __('Download uploaded document') }}: {{ $request->documentTypeLabel() }}">
                                                        <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                                                    </x-actions.icon-button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                            <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-200">
                                                    <x-heroicon-o-document-plus class="h-6 w-6" />
                                                </span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ __('No document requests yet.') }}</span>
                                                <span>{{ __('Create a request when you need an employment letter, salary statement, upload request, or another HR/Finance document.') }}</span>
                                                <x-actions.button type="button" wire:click="create" size="sm">
                                                    {{ __('New Request') }}
                                                </x-actions.button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-4 md:hidden">
                    @forelse ($requests as $request)
                        <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $request->documentTypeLabel() }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $request->created_at->diffForHumans() }}</div>
                                @if ($request->due_date)
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Due') }} {{ $request->due_date->format('d M Y') }}</div>
                                @endif
                                @if ($request->requester && $request->requested_by !== $request->user_id)
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Requested by') }} {{ $request->requester->name }}</div>
                                @endif
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === \App\Models\EmployeeDocumentRequest::STATUS_READY ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($request->status === \App\Models\EmployeeDocumentRequest::STATUS_REJECTED ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($request->status === \App\Models\EmployeeDocumentRequest::STATUS_UPLOAD_PROCESSING ? 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200')) }}">
                                    {{ $request->statusLabel() }}
                                </span>
                            </div>

                            <div class="mt-4 rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Purpose') }}</p>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $request->purpose }}</p>
                                @if ($request->details)
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $request->details }}</p>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $request->statusLabel() }}
                                        @if ($request->reviewer)
                                            <div class="mt-1 text-[11px] text-gray-400">{{ __('by') }} {{ $request->reviewer->name }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Admin Note') }}</p>
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $request->fulfillment_note ?: $request->rejection_note ?: '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @can('upload', $request)
                                    <x-actions.button type="button" size="sm" wire:click="prepareUpload({{ $request->id }})" variant="soft-primary" data-e2e="document-upload-open" data-request-id="{{ $request->id }}">
                                        <x-heroicon-m-arrow-up-tray class="h-4 w-4" />
                                        {{ __('Upload') }}
                                    </x-actions.button>
                                @endcan
                                @if ($request->generated_path)
                                    <x-actions.button href="{{ route('document-requests.download', $request) }}" variant="soft-primary" size="sm">
                                        <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                                        {{ __('Generated') }}
                                    </x-actions.button>
                                @endif
                                @if ($request->uploaded_path)
                                    <x-actions.button href="{{ route('document-requests.uploaded', $request) }}" variant="soft-primary" size="sm">
                                        <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                                        {{ __('Uploaded') }}
                                    </x-actions.button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-200">
                                    <x-heroicon-o-document-plus class="h-6 w-6" />
                                </span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ __('No document requests yet.') }}</span>
                                <span>{{ __('Create a request when you need a company document or need to upload an employee document.') }}</span>
                                <x-actions.button type="button" wire:click="create" size="sm">
                                    {{ __('New Request') }}
                                </x-actions.button>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($requests->hasPages())
                    <div class="mt-4">{{ $requests->links() }}</div>
                @endif
            </div>
        </section>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-[90] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="document-request-modal-title">
            <div class="flex min-h-[100dvh] items-start justify-center px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]">
                <div class="fixed inset-0 bg-gray-900/60" wire:click="close"></div>
                <div class="relative w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-xl dark:bg-gray-800"
                    style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));">
                    <h2 id="document-request-modal-title" class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('New Document Request') }}</h2>

                    <form wire:submit="store" class="mt-6 space-y-5">
                        <div>
                            <x-forms.label for="document-type" value="{{ __('Document Type') }}" class="mb-1.5 block" />
                            <x-forms.select id="document-type" wire:model.live="documentType" class="block w-full">
                                @foreach ($documentTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="documentType" class="mt-1" />
                        </div>

                        <div>
                            <x-forms.label for="document-purpose" value="{{ __('Purpose') }}" class="mb-1.5 block" />
                            <x-forms.textarea id="document-purpose" wire:model.live="purpose" rows="3" class="block w-full"
                                placeholder="{{ __('Example: bank account opening, visa application, or housing lease.') }}" />
                            <x-forms.input-error for="purpose" class="mt-1" />
                        </div>

                        <div>
                            <x-forms.label for="document-details" value="{{ __('Additional Details') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                            <x-forms.textarea id="document-details" wire:model.live="details" rows="4" class="block w-full"
                                placeholder="{{ __('Add recipient name, deadline, required language, or other notes.') }}" />
                            <x-forms.input-error for="details" class="mt-1" />
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 dark:border-gray-700 sm:flex-row sm:justify-end">
                            <x-actions.button type="button" wire:click="close" variant="secondary" class="w-full sm:w-auto">
                                {{ __('Cancel') }}
                            </x-actions.button>
                            <x-actions.button type="submit" variant="primary" class="w-full sm:w-auto">
                                {{ __('Submit Request') }}
                            </x-actions.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($uploadingRequestId)
        <div class="fixed inset-0 z-[90] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="document-upload-modal-title">
            <div class="flex min-h-[100dvh] items-start justify-center px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]">
                <div class="fixed inset-0 bg-gray-900/60" wire:click="cancelUpload"></div>
                <div class="relative w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 shadow-xl dark:bg-gray-800"
                    style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));">
                    <h2 id="document-upload-modal-title" class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Upload Document') }}</h2>

                    <form wire:submit="upload" class="mt-6 space-y-5" data-e2e="document-upload-form">
                        <div>
                            <x-forms.label for="document-upload-file" value="{{ __('File') }}" class="mb-1.5 block" />
                            <input id="document-upload-file" wire:model="attachment" type="file" data-e2e="document-upload-file" class="block w-full rounded-xl border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:border-0 file:bg-primary-50 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:file:bg-primary-900/30 dark:file:text-primary-200" />
                            <x-forms.input-error for="attachment" class="mt-1" />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" wire:loading.remove wire:target="attachment,upload">{{ __('Accepted: PDF, image, Word, or Excel. Maximum 10 MB.') }}</p>
                            <p class="mt-2 text-xs font-medium text-sky-700 dark:text-sky-300" wire:loading wire:target="attachment">{{ __('Uploading file...') }}</p>
                            <p class="mt-2 text-xs font-medium text-sky-700 dark:text-sky-300" wire:loading wire:target="upload">{{ __('Processing upload...') }}</p>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 dark:border-gray-700 sm:flex-row sm:justify-end">
                            <x-actions.button type="button" wire:click="cancelUpload" variant="secondary" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="attachment,upload">
                                {{ __('Cancel') }}
                            </x-actions.button>
                            <x-actions.button type="submit" variant="primary" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="attachment,upload" data-e2e="document-upload-submit">
                                <span wire:loading.remove wire:target="upload">{{ __('Upload') }}</span>
                                <span wire:loading wire:target="upload">{{ __('Processing...') }}</span>
                            </x-actions.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
