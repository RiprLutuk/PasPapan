<x-admin.page-shell
    :title="__('Reimbursement Requests')"
    :description="__('Manage and approve employee expense claims.')"
>
    <x-slot name="toolbar">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-2xl">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search...') }}" class="block w-full rounded-lg border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
            </div>
            <select wire:model.live="statusFilter" class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="">{{ __('All Status') }}</option>
            </select>
        </div>
    </x-slot>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
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
                                            <img src="{{ $claim->user->profile_photo_url }}" alt="{{ $claim->user->name }}" class="h-full w-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $claim->user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $claim->user->email }}</div>
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
                                        <a href="{{ Storage::url($claim->attachment) }}" target="_blank" class="flex items-center gap-1 text-primary-600 hover:text-primary-700 transition-colors">
                                            <x-heroicon-m-paper-clip class="h-4 w-4" />
                                            <span>{{ __('View') }}</span>
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">{{ __('No File') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                        @if($claim->status === 'approved') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/50
                                        @elseif($claim->status === 'rejected') bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/50
                                        @elseif($claim->status === 'pending_finance') bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-500/50
                                        @else bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/50 @endif">
                                        {{ __($claim->status === 'pending_finance' ? 'Menunggu Finance' : ucfirst($claim->status)) }}
                                    </span>
                                    @if($claim->status !== 'pending')
                                    <div class="mt-1 flex flex-col gap-0.5 w-[140px]">
                                        @if($claim->head_approved_by)
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span class="truncate">Head: {{ $claim->headApprover->name ?? '-' }}</span>
                                        </span>
                                        @endif
                                        @if($claim->finance_approved_by || $claim->approved_by)
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span class="truncate">Finance: {{ $claim->financeApprover->name ?? $claim->approvedBy->name ?? '-' }}</span>
                                        </span>
                                        @endif
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @php
                                        $user = Auth::user();
                                        $isFinanceHead = ($user->is_admin || $user->is_superadmin || ($user->jobTitle?->jobLevel?->rank <= 2 && $user->division && strtolower($user->division->name) === 'finance'));
                                        $canApprove = false;
                                        if ($claim->status === 'pending') $canApprove = true;
                                        if ($claim->status === 'pending_finance' && $isFinanceHead) $canApprove = true;
                                    @endphp
                                    @if($canApprove)
                                        <div class="flex justify-end gap-2">
                                            <button wire:click="approve('{{ $claim->id }}')" wire:confirm="{{ __('Approve this claim?') }}" class="text-gray-400 hover:text-green-600 transition-colors" title="{{ __('Approve') }}">
                                                <x-heroicon-m-check-circle class="h-6 w-6" />
                                            </button>
                                            <button wire:click="reject('{{ $claim->id }}')" wire:confirm="{{ __('Reject this claim?') }}" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Reject') }}">
                                                <x-heroicon-m-x-circle class="h-6 w-6" />
                                            </button>
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
                                        <x-heroicon-o-currency-dollar class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                        <p class="font-medium">{{ __('No requests found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reimbursements->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                    {{ $reimbursements->links() }}
                </div>
            @endif
        </div>
</x-admin.page-shell>
