<x-admin.page-shell :title="__('Employee Document Requests')" :description="__('Review employee requests for employment letters, salary statements, and other HR documents.')">
    <div class="space-y-4">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <x-forms.label for="document-request-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                <x-forms.input id="document-request-search" type="search" wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Employee, NIP, or purpose') }}" class="w-full min-h-[44px]" />
            </div>
            <div>
                <x-forms.label for="document-request-status" value="{{ __('Status') }}" class="mb-1.5 block" />
                <x-forms.select id="document-request-status" wire:model.live="statusFilter">
                    <option value="all">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-forms.select>
            </div>
            <div>
                <x-forms.label for="document-request-type" value="{{ __('Document Type') }}" class="mb-1.5 block" />
                <x-forms.select id="document-request-type" wire:model.live="typeFilter">
                    <option value="all">{{ __('All types') }}</option>
                    @foreach ($documentTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-forms.select>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/40">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">{{ __('Employee') }}</th>
                            <th class="px-4 py-3">{{ __('Document') }}</th>
                            <th class="px-4 py-3">{{ __('Purpose') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($requests as $request)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-semibold">{{ $request->user->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $request->user->nip }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $request->user->division->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-semibold">{{ $request->documentTypeLabel() }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $request->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="max-w-md px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-medium">{{ $request->purpose }}</div>
                                    @if ($request->details)
                                        <div class="mt-1 whitespace-pre-line text-xs text-slate-500 dark:text-slate-400">{{ $request->details }}</div>
                                    @endif
                                    @if ($request->fulfillment_note || $request->rejection_note)
                                        <div class="mt-2 rounded-lg bg-slate-50 p-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $request->fulfillment_note ?: $request->rejection_note }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $request->status === 'ready'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                            : ($request->status === 'rejected'
                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                        {{ $request->statusLabel() }}
                                    </span>
                                    @if ($request->reviewer)
                                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('By :name', ['name' => $request->reviewer->name]) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($request->status === 'pending')
                                        <div class="flex flex-col gap-2">
                                            <x-actions.button type="button" size="sm" wire:click="confirmReady({{ $request->id }})">
                                                {{ __('Mark Ready') }}
                                            </x-actions.button>
                                            <x-actions.secondary-button type="button" size="sm" wire:click="confirmReject({{ $request->id }})">
                                                {{ __('Reject') }}
                                            </x-actions.secondary-button>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Completed') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No document requests found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($requests->hasPages())
            <div>{{ $requests->links() }}</div>
        @endif
    </div>

    <x-overlays.dialog-modal wire:model.live="confirmingReady">
        <x-slot name="title">{{ __('Mark Document Ready') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-3">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Add delivery or pickup details for the employee.') }}
                </p>
                <x-forms.textarea wire:model.live="reviewNote" rows="4"
                    placeholder="{{ __('Example: document is ready for pickup at HR desk after 14:00.') }}" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full justify-end gap-2">
                <x-actions.secondary-button type="button" wire:click="cancelReview">{{ __('Cancel') }}</x-actions.secondary-button>
                <x-actions.button type="button" wire:click="markReady">{{ __('Mark Ready') }}</x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">{{ __('Reject Document Request') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-3">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Add an optional note so the employee understands why this request was rejected.') }}
                </p>
                <x-forms.textarea wire:model.live="reviewNote" rows="4"
                    placeholder="{{ __('Example: please resubmit with a clearer purpose and deadline.') }}" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full justify-end gap-2">
                <x-actions.secondary-button type="button" wire:click="cancelReview">{{ __('Cancel') }}</x-actions.secondary-button>
                <x-actions.button type="button" wire:click="reject">{{ __('Reject Request') }}</x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
