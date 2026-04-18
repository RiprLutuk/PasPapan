<x-admin.page-shell
    :title="__('Overtime Management')"
    :description="__('Review and manage overtime submissions from your team.')"
    wire:poll.10s
>
    <x-slot name="toolbar">
        <div class="max-w-xs">
            <label for="overtime-status-filter" class="sr-only">{{ __('Filter overtime submissions by status') }}</label>
            <select id="overtime-status-filter" wire:model.live="statusFilter" class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700">
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="all">{{ __('All') }}</option>
            </select>
        </div>
    </x-slot>

        <x-admin.panel>

            <div class="p-0">
                @if($overtimes->isEmpty())
                    <div class="p-8">
                        <x-admin.empty-state :title="__('No Overtime Requests')" :description="__('No overtime requests found for this filter.')" class="min-h-[300px] border-0 bg-transparent shadow-none dark:bg-transparent">
                            <x-slot name="icon">
                                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-700/50">
                                    <svg class="h-10 w-10 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </x-slot>
                        </x-admin.empty-state>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($overtimes as $overtime)
                            <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-xl flex items-center justify-center 
                                        @if($overtime->status === 'approved') bg-green-100 dark:bg-green-900/30
                                        @elseif($overtime->status === 'rejected') bg-red-100 dark:bg-red-900/30
                                        @else bg-yellow-100 dark:bg-yellow-900/30 @endif">
                                        <span class="text-xl">
                                            @if($overtime->status === 'approved') ✅
                                            @elseif($overtime->status === 'rejected') ❌
                                            @else ⏳ @endif
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $overtime->user->name }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $overtime->user->division?->name ?? '-' }} • {{ $overtime->user->jobTitle?->name ?? '-' }}
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                            {{ $overtime->date->format('d M Y') }} • 
                                            {{ $overtime->start_time->format('H:i') }} - {{ $overtime->end_time->format('H:i') }}
                                            <span class="text-indigo-600 dark:text-indigo-400 font-semibold">({{ $overtime->duration_text }})</span>
                                        </p>
                                        @if($overtime->reason)
                                            <p class="text-[10px] text-gray-400 italic mt-0.5 line-clamp-1">{{ $overtime->reason }}</p>
                                        @endif
                                        @if($overtime->rejection_reason)
                                            <p class="text-[10px] text-red-500 mt-0.5">{{ __('Reason') }}: {{ $overtime->rejection_reason }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 sm:flex-shrink-0">
                                    @if($overtime->status === 'pending')
                                        <div class="flex justify-end gap-2">
                                            <x-actions.icon-button wire:click="approve('{{ $overtime->id }}')" variant="success" label="{{ __('Approve overtime request') }}">
                                                <x-heroicon-m-check-circle class="h-6 w-6" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button wire:click="confirmReject('{{ $overtime->id }}')" variant="danger" label="{{ __('Reject overtime request') }}">
                                                <x-heroicon-m-x-circle class="h-6 w-6" />
                                            </x-actions.icon-button>
                                        </div>
                                    @else
                                        <x-admin.status-badge :tone="$overtime->status === 'approved' ? 'success' : 'danger'" pill="true">
                                            {{ __(ucfirst($overtime->status)) }}
                                        </x-admin.status-badge>
                                        @if($overtime->approvedBy)
                                            <span class="text-[10px] text-gray-400">{{ __('by') }} {{ $overtime->approvedBy->name }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-200/60 bg-gray-50/70 px-4 py-4 dark:border-gray-700/60 dark:bg-gray-900/40">
                        {{ $overtimes->links() }}
                    </div>
                @endif
            </div>
        </x-admin.panel>

    {{-- Rejection Modal --}}
    <x-overlays.dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">
            {{ __('Reject Overtime Request') }}
        </x-slot>

        <x-slot name="content">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('Please provide a reason for rejection:') }}
            </p>
            <x-forms.textarea wire:model="rejectionReason" rows="3" class="w-full" placeholder="{{ __('Reason...') }}" />
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
