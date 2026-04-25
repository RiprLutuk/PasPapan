<x-admin.page-shell :title="__('Shift Swap Approvals')" :description="__('Review and approve employee shift swap requests.')" wire:poll.15s>
    <x-slot name="toolbar">
        <x-admin.page-tools>
            <div class="md:col-span-2 xl:col-span-8">
                <x-forms.label for="shift-swap-search" value="{{ __('Search shift swap requests') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                    </span>
                    <x-forms.input id="shift-swap-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search employee, NIP, division, shift, or reason...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-4">
                <x-forms.label for="shift-swap-status-filter" value="{{ __('Approval Status') }}" class="mb-1.5 block" />
                <x-forms.select id="shift-swap-status-filter" wire:model.live="statusFilter" class="w-full">
                    @foreach ($statuses as $status => $label)
                        <option value="{{ $status }}">{{ $label }}</option>
                    @endforeach
                    <option value="all">{{ __('All statuses') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <x-admin.panel>
        @if ($requests->isEmpty())
            <div class="p-8">
                <x-admin.empty-state :title="__('No shift swap requests')" :description="__('No shift swap requests found for this filter.')"
                    class="min-h-[300px] border-0 bg-transparent shadow-none dark:bg-transparent">
                    <x-slot name="icon">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-700/50">
                            <x-heroicon-o-arrows-right-left class="h-10 w-10 text-gray-300 dark:text-gray-500" />
                        </div>
                    </x-slot>
                </x-admin.empty-state>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($requests as $swapRequest)
                    @php
                        $date = $swapRequest->effectiveScheduleDate();
                        $statusTone = match ($swapRequest->status) {
                            \App\Models\ShiftSwapRequest::STATUS_APPROVED => 'success',
                            \App\Models\ShiftSwapRequest::STATUS_REJECTED => 'danger',
                            default => 'warning',
                        };
                    @endphp

                    <div class="flex flex-col gap-4 p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/50 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="truncate text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $swapRequest->user?->name ?? '-' }}
                                </h4>
                                <x-admin.status-badge :tone="$statusTone" pill="true">
                                    {{ $swapRequest->statusLabel() }}
                                </x-admin.status-badge>
                            </div>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $swapRequest->user?->nip ?? '-' }} /
                                {{ $swapRequest->user?->division?->name ?? '-' }} /
                                {{ $swapRequest->user?->jobTitle?->name ?? '-' }}
                            </p>

                            <div class="mt-3 grid gap-3 text-xs text-gray-600 dark:text-gray-300 md:grid-cols-3">
                                <div>
                                    <span class="block text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500">{{ __('Schedule Date') }}</span>
                                    <span>{{ $date?->format('d M Y') ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500">{{ __('Current Shift') }}</span>
                                    <span>{{ $swapRequest->currentShift?->name ?? $swapRequest->schedule?->shift?->name ?? __('No current schedule') }}</span>
                                </div>
                                <div>
                                    <span class="block text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500">{{ __('Requested Shift') }}</span>
                                    <span>{{ $swapRequest->requestedShift?->name ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-3 text-xs text-gray-600 dark:text-gray-300 md:grid-cols-2">
                                <p>
                                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ __('Replacement') }}:</span>
                                    {{ $swapRequest->replacementUser?->name ?? __('Not specified') }}
                                </p>
                                @if ($swapRequest->reviewer)
                                    <p>
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ __('Reviewed by') }}:</span>
                                        {{ $swapRequest->reviewer->name }}
                                    </p>
                                @endif
                            </div>

                            @if ($swapRequest->reason)
                                <p class="mt-3 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $swapRequest->reason }}
                                </p>
                            @endif

                            @if ($swapRequest->rejection_note)
                                <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                    {{ __('Rejection note') }}: {{ $swapRequest->rejection_note }}
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center justify-end gap-2 lg:flex-shrink-0">
                            @if ($swapRequest->status === \App\Models\ShiftSwapRequest::STATUS_PENDING)
                                <x-actions.icon-button wire:click="approve({{ $swapRequest->id }})" variant="success"
                                    label="{{ __('Approve shift swap request') }}">
                                    <x-heroicon-m-check-circle class="h-6 w-6" />
                                </x-actions.icon-button>
                                <x-actions.icon-button wire:click="confirmReject({{ $swapRequest->id }})" variant="danger"
                                    label="{{ __('Reject shift swap request') }}">
                                    <x-heroicon-m-x-circle class="h-6 w-6" />
                                </x-actions.icon-button>
                            @else
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $swapRequest->reviewed_at?->format('d M Y H:i') ?? '-' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200/60 bg-gray-50/70 px-4 py-4 dark:border-gray-700/60 dark:bg-gray-900/40">
                {{ $requests->links() }}
            </div>
        @endif
    </x-admin.panel>

    <x-overlays.dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">
            {{ __('Reject Shift Swap Request') }}
        </x-slot>

        <x-slot name="content">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Add a rejection note for this employee.') }}
            </p>
            <x-forms.textarea wire:model="rejectionNote" rows="3" class="w-full" placeholder="{{ __('Reason...') }}" />
            <x-forms.input-error for="rejectionNote" class="mt-2" />
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="cancelReject" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.danger-button class="ms-3" wire:click="reject" wire:loading.attr="disabled">
                {{ __('Reject') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
