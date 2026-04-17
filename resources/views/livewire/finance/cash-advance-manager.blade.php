<x-admin-page-shell
    :title="__('Manage Kasbon')"
    :description="__('Manage and approve employee cash advance requests.')"
>
    <x-slot name="toolbar">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-3xl">
            <div class="relative w-full">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                </div>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search Employee...') }}" class="block w-full rounded-lg border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
            </div>
            @if($activeTab === 'requests')
            <select wire:model.live="statusFilter" class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="all">{{ __('All Status') }}</option>
            </select>
            @endif
        </div>
    </x-slot>

        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button wire:click="switchTab('requests')" class="{{ $activeTab === 'requests' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    {{ __('All Requests') }}
                </button>
                <button wire:click="switchTab('users')" class="{{ $activeTab === 'users' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    {{ __('Group by Employee') }}
                </button>
            </nav>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @if ($activeTab === 'requests')
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Date / Purpose') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Amount') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Deduction Target') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($advances as $advance)
                        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <img src="{{ $advance->user->profile_photo_url }}" alt="{{ $advance->user->name }}" class="h-full w-full object-cover">
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $advance->user->name }}</div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5 flex-wrap">
                                            <span>{{ $advance->user->jobTitle->name ?? '-' }} (Rank {{ $advance->user->jobTitle->jobLevel->rank ?? '-' }})</span>
                                            @if($advance->user->kabupaten)
                                            <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                            <span class="flex items-center gap-0.5"><x-heroicon-m-map-pin class="h-3 w-3" /> {{ $advance->user->kabupaten->nama }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                <div>{{ $advance->created_at->translatedFormat('d M Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $advance->purpose }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($advance->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::create()->month((int) $advance->payment_month)->translatedFormat('F') }} {{ $advance->payment_year }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-start">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                                @if($advance->status === 'approved') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/50
                                                @elseif($advance->status === 'paid') bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-500/50
                                                @elseif($advance->status === 'rejected') bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/50
                                                @elseif($advance->status === 'pending_finance') bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-500/50
                                                @else bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/50 @endif">
                                        {{ __($advance->status === 'pending' ? 'Pending' : ($advance->status === 'pending_finance' ? 'Menunggu Finance' : ($advance->status === 'approved' ? 'Approved' : ($advance->status === 'paid' ? 'Paid' : 'Rejected')))) }}
                                    </span>
                                    @if($advance->status !== 'pending')
                                    <div class="mt-1 flex flex-col gap-0.5">
                                        @if($advance->head_approved_by)
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            Head: {{ $advance->headApprover->name ?? '-' }}
                                        </span>
                                        @endif
                                        @if($advance->finance_approved_by || $advance->approved_by)
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            Finance: {{ $advance->financeApprover->name ?? $advance->approver->name ?? '-' }}
                                        </span>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @php
                                        $user = Auth::user();
                                        $isFinanceHead = ($user->isAdmin || $user->isSuperadmin || ($user->jobTitle?->jobLevel?->rank <= 2 && $user->division && strtolower($user->division->name) === 'finance'));
                                        $canApprove = false;
                                        if ($advance->status === 'pending') $canApprove = true;
                                        if ($advance->status === 'pending_finance' && $isFinanceHead) $canApprove = true;
                                    @endphp
                                    @if($canApprove)
                                    <button wire:click="approve('{{ $advance->id }}')" wire:confirm="{{ __('Approve this request?') }}" class="text-gray-400 hover:text-green-600 transition-colors" title="{{ __('Approve') }}">
                                        <x-heroicon-m-check-circle class="h-6 w-6" />
                                    </button>
                                    <button wire:click="reject('{{ $advance->id }}')" wire:confirm="{{ __('Reject this request?') }}" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Reject') }}">
                                        <x-heroicon-m-x-circle class="h-6 w-6" />
                                    </button>
                                    @else
                                    <span class="text-xs text-gray-400">
                                        @if($advance->status === 'paid')
                                        {{ __('Deducted') }}
                                        @else
                                        {{ __('Completed') }}
                                        @endif
                                    </span>
                                    @endif

                                    @if(auth()->user()->isAdmin || auth()->user()->isSuperadmin)
                                    <button wire:click="delete('{{ $advance->id }}')" wire:confirm="{{ __('Delete permanently?') }}" class="text-gray-400 hover:text-red-500 transition-colors ml-2" title="{{ __('Delete') }}">
                                        <x-heroicon-m-trash class="h-5 w-5" />
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <x-heroicon-o-document-text class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                    <p class="font-medium">{{ __('No cash advance data found.') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($advances->hasPages())
            <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                {{ $advances->links() }}
            </div>
            @endif
            @else
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Total Kasbon') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Deduction Breakdown') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Recent History') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($userGrouped as $user)
                        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 flex flex-col sm:flex-row sm:items-center sm:gap-1.5">
                                            <span>{{ $user->jobTitle->name ?? '-' }}</span>
                                            @if($user->kabupaten)
                                            <span class="hidden sm:inline text-gray-300 dark:text-gray-600">&bull;</span>
                                            <span class="flex items-center gap-0.5"><x-heroicon-m-map-pin class="h-3 w-3" /> {{ $user->kabupaten->nama }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white align-top">
                                Rp {{ number_format($user->cashAdvances->whereIn('status', ['paid', 'approved', 'pending'])->sum('amount'), 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 align-top">
                                @php
                                // Group by month and year
                                $groupedByMonth = $user->cashAdvances->whereIn('status', ['paid', 'approved', 'pending'])->groupBy(function($item) {
                                return $item->payment_year . '-' . str_pad($item->payment_month, 2, '0', STR_PAD_LEFT);
                                })->sortKeysDesc();
                                @endphp
                                <ul class="space-y-2">
                                    @foreach($groupedByMonth as $key => $items)
                                    <li class="flex items-center gap-3 text-sm">
                                        <span class="w-24 text-gray-600 dark:text-gray-400">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y') }}
                                        </span>
                                        <span class="font-mono text-gray-900 dark:text-white">
                                            Rp {{ number_format($items->sum('amount'), 0, ',', '.') }}
                                        </span>
                                    </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <ul class="space-y-4">
                                    @foreach($user->cashAdvances->sortByDesc('created_at')->take(3) as $hist)
                                    <li class="relative pl-4 border-l-2 @if($hist->status === 'paid') border-blue-400 @elseif($hist->status === 'approved') border-green-400 @elseif($hist->status === 'rejected') border-red-400 @else border-yellow-400 @endif pb-2 last:pb-0">
                                        <div class="absolute -left-[5px] top-1.5 h-2 w-2 rounded-full @if($hist->status === 'paid') bg-blue-400 @elseif($hist->status === 'approved') bg-green-400 @elseif($hist->status === 'rejected') bg-red-400 @else bg-yellow-400 @endif ring-4 ring-white dark:ring-gray-800"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $hist->created_at->translatedFormat('d M') }} ({{ __('Deduction') }} {{ \Carbon\Carbon::create()->month((int) $hist->payment_month)->translatedFormat('F') }})</div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Rp {{ number_format($hist->amount, 0, ',', '.') }} <span class="text-xs font-normal text-gray-500">· {{ __($hist->status === 'pending' ? 'Pending' : ($hist->status === 'approved' ? 'Approved' : ($hist->status === 'paid' ? 'Paid' : 'Rejected'))) }}</span></div>
                                    </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <x-heroicon-o-users class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                    <p class="font-medium">{{ __('No kasbon data found.') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($userGrouped->hasPages())
            <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                {{ $userGrouped->links() }}
            </div>
            @endif
            @endif
        </div>
</x-admin-page-shell>
