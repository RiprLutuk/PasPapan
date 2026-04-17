<x-admin-page-shell
    :title="__('Overtime Management')"
    :description="__('Review and manage overtime submissions from your team.')"
    wire:poll.10s
>
    <x-slot name="actions">
        <div class="flex items-center gap-2">
            <select wire:model.live="statusFilter" class="text-sm rounded-xl border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="all">{{ __('All') }}</option>
            </select>
        </div>
    </x-slot>

        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">

            <div class="p-0">
                @if($overtimes->isEmpty())
                    <div class="p-8 text-center flex flex-col items-center justify-center min-h-[300px]">
                        <div class="w-20 h-20 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('No Overtime Requests') }}</h3>
                        <p class="text-gray-500 dark:text-gray-400">{{ __('No overtime requests found for this filter.') }}</p>
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
                                            <button wire:click="approve('{{ $overtime->id }}')" class="text-gray-400 hover:text-green-600 transition-colors" title="{{ __('Approve') }}">
                                                <x-heroicon-m-check-circle class="h-6 w-6" />
                                            </button>
                                            <button wire:click="confirmReject('{{ $overtime->id }}')" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Reject') }}">
                                                <x-heroicon-m-x-circle class="h-6 w-6" />
                                            </button>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium
                                            @if($overtime->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400
                                            @else bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400 @endif">
                                            {{ __(ucfirst($overtime->status)) }}
                                        </span>
                                        @if($overtime->approvedBy)
                                            <span class="text-[10px] text-gray-400">{{ __('by') }} {{ $overtime->approvedBy->name }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $overtimes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Rejection Modal --}}
    <x-dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">
            {{ __('Reject Overtime Request') }}
        </x-slot>

        <x-slot name="content">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('Please provide a reason for rejection:') }}
            </p>
            <textarea wire:model="rejectionReason" rows="3" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" placeholder="{{ __('Reason...') }}"></textarea>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelReject" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="reject" wire:loading.attr="disabled">
                {{ __('Reject') }}
            </x-danger-button>
            </x-slot>
        </x-dialog-modal>
</x-admin-page-shell>
