<div class="py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xl dark:bg-gray-800 sm:rounded-lg">
            <div class="p-6 lg:p-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Pending Leave Requests</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            Review and approve employee leave requests.
                        </p>
                    </div>
                </div>

                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-0">Employee</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Date</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Type</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Note</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Attachment</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($leaves as $leave)
                                        <tr>
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-0">
                                                {{ $leave->user->name }}
                                                <div class="text-xs text-gray-500">{{ $leave->user->division->name ?? '-' }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $leave->date->format('d M Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $leave->status === 'sick' ? 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' : 'bg-blue-50 text-blue-700 ring-blue-700/10' }}">
                                                    {{ ucfirst($leave->status) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                                {{ $leave->note }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                @if ($leave->attachment)
                                                    <a href="{{ $leave->attachment_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 underline">View</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                                <div class="flex justify-end gap-2">
                                                    <button wire:click="approve('{{ $leave->id }}')" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                                        Approve
                                                    </button>
                                                    <button wire:click="confirmReject('{{ $leave->id }}')" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                                        Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                                No pending leave requests.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    {{ $leaves->links() }}
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
