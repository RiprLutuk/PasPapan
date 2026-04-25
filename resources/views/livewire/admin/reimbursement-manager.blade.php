<x-admin.page-shell :title="__('Reimbursement Requests')" :description="__('Manage and approve employee expense claims.')">
    <x-slot name="toolbar">
        <x-admin.page-tools>
            <div class="md:col-span-2 xl:col-span-8">
                <x-forms.label for="reimbursement-search" value="{{ __('Search reimbursement requests') }}"
                    class="mb-1.5 block" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <x-forms.input id="reimbursement-search" wire:model.live.debounce.300ms="search" type="search"
                        placeholder="{{ __('Search employee, type, or description...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-4">
                <x-forms.label for="reimbursement-status-filter" value="{{ __('Approval Status') }}"
                    class="mb-1.5 block" />
                <x-forms.select id="reimbursement-status-filter" wire:model.live="statusFilter" class="w-full">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <x-admin.panel>
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-medium">{{ __('Employee') }}</th>
                        <th scope="col" class="px-6 py-4 font-medium">{{ __('Date') }}</th>
                        <th scope="col" class="px-6 py-4 font-medium">{{ __('Type') }}</th>
                        <th scope="col" class="px-6 py-4 font-medium">{{ __('Amount') }}</th>
                        <th scope="col" class="px-6 py-4 font-medium">{{ __('Description') }}</th>
                        <th scope="col" class="px-6 py-4 font-medium">{{ __('Attachment') }}</th>
                        <th scope="col" class="px-6 py-4 font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($reimbursements as $claim)
                        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <img src="{{ $claim->user->profile_photo_url }}"
                                            alt="{{ $claim->user->name }}" class="h-full w-full object-cover">
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $claim->user->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $claim->user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($claim->date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 capitalize text-gray-600 dark:text-gray-300">
                                {{ __($claim->type) }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($claim->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300 max-w-xs truncate">
                                {{ $claim->description }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                @if ($claim->attachment)
                                    <a href="{{ route('reimbursement.attachment.download', $claim) }}" target="_blank"
                                        rel="noopener noreferrer"
                                        class="wcag-touch-target flex items-center gap-1 rounded text-primary-600 transition-colors hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                        <x-heroicon-m-paper-clip class="h-4 w-4" />
                                        <span>{{ __('View') }}</span>
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs">{{ __('No File') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <x-admin.status-badge :tone="$claim->status === 'approved' ? 'success' : ($claim->status === 'rejected' ? 'danger' : ($claim->status === 'pending_finance' ? 'accent' : 'warning'))">
                                    {{ __($claim->status === 'pending_finance' ? 'Menunggu Finance' : ucfirst($claim->status)) }}
                                </x-admin.status-badge>
                                @if ($claim->status !== 'pending')
                                    <div class="mt-1 flex flex-col gap-0.5 w-[140px]">
                                        @if ($claim->head_approved_by)
                                            <span
                                                class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                <svg class="w-3 h-3 text-purple-500 flex-shrink-0" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                    </path>
                                                </svg>
                                                <span class="truncate">Head:
                                                    {{ $claim->headApprover->name ?? '-' }}</span>
                                            </span>
                                        @endif
                                        @if ($claim->finance_approved_by || $claim->approved_by)
                                            <span
                                                class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                    </path>
                                                </svg>
                                                <span class="truncate">Finance:
                                                    {{ $claim->financeApprover->name ?? ($claim->approvedBy->name ?? '-') }}</span>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @php
                                    $canApprove = in_array($claim->status, ['pending', 'pending_finance'], true)
                                        && Auth::user()->can('approve', $claim);
                                @endphp
                                @if ($canApprove)
                                    <div class="flex items-center justify-end gap-2">
                                        <x-actions.icon-button wire:click="approve('{{ $claim->id }}')"
                                            wire:confirm="{{ __('Approve this claim?') }}" variant="success"
                                            label="{{ __('Approve reimbursement claim from') }} {{ $claim->user->name }}">
                                            <x-heroicon-m-check-circle class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="reject('{{ $claim->id }}')"
                                            wire:confirm="{{ __('Reject this claim?') }}" variant="danger"
                                            label="{{ __('Reject reimbursement claim from') }} {{ $claim->user->name }}">
                                            <x-heroicon-m-x-circle class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">{{ __('Completed') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <x-heroicon-o-currency-dollar
                                        class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                    <p class="font-medium">{{ __('No requests found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reimbursements->hasPages())
            <div
                class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                {{ $reimbursements->links() }}
            </div>
        @endif
    </x-admin.panel>
</x-admin.page-shell>
