<div class="py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xl dark:bg-gray-800 sm:rounded-lg">
            <div class="p-6 lg:p-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Pending Leave Requests') }}</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ __('Review and approve employee leave requests.') }}
                        </p>
                    </div>
                </div>

                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-0">{{ __('Employee') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">{{ __('Date') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">{{ __('Type') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">{{ __('Note') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">{{ __('Attachment') }}</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($groupedLeaves as $groupKey => $group)
                                        @php
                                            $firstLeave = $group->first();
                                            $lastLeave = $group->last();
                                            $leaveIds = $group->pluck('id')->toArray();
                                            // Format Date Range: "12 Jan 2024" or "12 - 14 Jan 2024"
                                            if ($group->count() > 1) {
                                                if ($firstLeave->date->format('M Y') == $lastLeave->date->format('M Y')) {
                                                    $dateDisplay = $firstLeave->date->format('d') . ' - ' . $lastLeave->date->format('d M Y') . ' (' . $group->count() . ' days)';
                                                } else {
                                                    $dateDisplay = $firstLeave->date->format('d M') . ' - ' . $lastLeave->date->format('d M Y') . ' (' . $group->count() . ' days)';
                                                }
                                            } else {
                                                $dateDisplay = $firstLeave->date->format('d M Y');
                                            }
                                        @endphp
                                        <tr>
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-0">
                                                {{ $firstLeave->user->name }}
                                                <div class="text-xs text-gray-500">{{ $firstLeave->user->division->name ?? '-' }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $dateDisplay }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $firstLeave->status === 'sick' ? 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' : 'bg-blue-50 text-blue-700 ring-blue-700/10' }}">
                                                    {{ ucfirst($firstLeave->status) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                                {{ $firstLeave->note }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                @if ($firstLeave->attachment)
                                                    <a href="{{ $firstLeave->attachment_url }}" target="_blank" class="text-primary-600 hover:text-primary-900 underline">{{ __('View') }}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                                <div class="flex justify-end gap-2">
                                                    <button wire:click="approve({{ json_encode($leaveIds) }})" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                                        {{ __('Approve') }}
                                                    </button>
                                                    <button wire:click="confirmReject({{ json_encode($leaveIds) }})" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                                        {{ __('Reject') }}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                                {{ __('No pending leave requests.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Showing all pending requests grouped by user and type.') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <x-dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">
            {{ __('Reject Leave Request') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please provide a reason for rejecting this leave request.') }}

            <div class="mt-4">
                <x-textarea wire:model="rejectionNote" placeholder="{{ __('Rejection Reason') }}"
                            class="block w-full" />
                <x-input-error for="rejectionNote" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingRejection')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="reject" wire:loading.attr="disabled">
                {{ __('Reject Request') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>
</div>
