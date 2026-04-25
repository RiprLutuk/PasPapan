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
                    <button type="button" wire:click="create"
                        class="wcag-touch-target inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:w-auto">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Request') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if (session()->has('success'))
                    <div class="mb-4 rounded-xl border border-green-100 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="hidden overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Document') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Purpose') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Admin Note') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($requests as $request)
                                    <tr>
                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-semibold">{{ $request->documentTypeLabel() }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="max-w-sm px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $request->purpose }}</div>
                                            @if ($request->details)
                                                <div class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $request->details }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($request->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                                {{ $request->statusLabel() }}
                                            </span>
                                            @if ($request->reviewer)
                                                <div class="mt-1 text-[10px] text-gray-400">{{ __('by') }} {{ $request->reviewer->name }}</div>
                                            @endif
                                        </td>
                                        <td class="max-w-sm px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $request->fulfillment_note ?: $request->rejection_note ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No document requests found.') }}
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
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($request->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
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
                        </article>
                    @empty
                        <div class="rounded-xl border border-gray-100 bg-white p-8 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            {{ __('No document requests found.') }}
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
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="document-request-modal-title">
            <div class="flex min-h-screen items-center justify-center px-4 py-6">
                <div class="fixed inset-0 bg-gray-900/60" wire:click="close"></div>
                <div class="relative w-full max-w-2xl rounded-3xl bg-white p-6 shadow-xl dark:bg-gray-800">
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
</div>
