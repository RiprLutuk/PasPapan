<div class="space-y-6">
    <div class="mb-6">
        <nav class="user-segmented-tabs" role="tablist" aria-label="{{ __('Kasbon views') }}">
            <button type="button" wire:click="switchTab('requests')"
                id="cash-advance-requests-tab"
                role="tab"
                aria-controls="cash-advance-requests-panel"
                aria-selected="{{ $activeTab === 'requests' ? 'true' : 'false' }}"
                tabindex="{{ $activeTab === 'requests' ? '0' : '-1' }}"
                class="user-segmented-tab">
                {{ __('All Requests') }}
            </button>
            <button type="button" wire:click="switchTab('users')"
                id="cash-advance-users-tab"
                role="tab"
                aria-controls="cash-advance-users-panel"
                aria-selected="{{ $activeTab === 'users' ? 'true' : 'false' }}"
                tabindex="{{ $activeTab === 'users' ? '0' : '-1' }}"
                class="user-segmented-tab">
                {{ __('Group by Employee') }}
            </button>
        </nav>
    </div>

    @if ($activeTab === 'requests')
    <div id="cash-advance-requests-panel" role="tabpanel" aria-labelledby="cash-advance-requests-tab" tabindex="0" class="hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Employee') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Date / Purpose') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Amount') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Deduction Target') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse($advances as $advance)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full">
                                    <img class="h-10 w-10 rounded-full object-cover" src="{{ $advance->user->profile_photo_url }}" alt="{{ $advance->user->name }}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $advance->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $advance->user->jobTitle->name ?? '-' }} ({{ __('Rank') }} {{ $advance->user->jobTitle->jobLevel->rank ?? '-' }})
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <div>{{ $advance->created_at->translatedFormat('d M Y') }}</div>
                            <div class="mt-0.5 max-w-xs truncate text-xs text-gray-400">{{ $advance->purpose }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                            Rp {{ number_format($advance->amount, 0, ',', '.') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::create()->month((int) $advance->payment_month)->translatedFormat('F') }} {{ $advance->payment_year }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                @if($advance->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($advance->status === 'paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($advance->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($advance->status === 'pending_finance') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @endif">
                                {{ __($advance->status === 'pending' ? 'Pending' : ($advance->status === 'pending_finance' ? 'Pending Finance' : ($advance->status === 'approved' ? 'Approved' : ($advance->status === 'paid' ? 'Paid' : 'Rejected')))) }}
                            </span>
                            @if($advance->status !== 'pending')
                            <div class="mt-1 space-y-1">
                                @if($advance->head_approved_by)
                                <div class="text-[10px] text-gray-400">{{ __('Head') }}: {{ $advance->headApprover->name ?? '-' }}</div>
                                @endif
                                @if($advance->finance_approved_by || $advance->approved_by)
                                <div class="text-[10px] text-gray-400">{{ __('Finance') }}: {{ $advance->financeApprover->name ?? $advance->approver->name ?? '-' }}</div>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                @php
                                    $canApprove = in_array($advance->status, ['pending', 'pending_finance'], true)
                                        && Auth::user()->can('approve', $advance);
                                @endphp
                                @if($canApprove)
                                <x-actions.icon-button wire:click="approve('{{ $advance->id }}')" wire:confirm="{{ __('Approve this request?') }}" variant="success" label="{{ __('Approve cash advance request from') }} {{ $advance->user->name }}">
                                    <x-heroicon-m-check-circle class="h-5 w-5" />
                                </x-actions.icon-button>
                                <x-actions.icon-button wire:click="reject('{{ $advance->id }}')" wire:confirm="{{ __('Reject this request?') }}" variant="danger" label="{{ __('Reject cash advance request from') }} {{ $advance->user->name }}">
                                    <x-heroicon-m-x-circle class="h-5 w-5" />
                                </x-actions.icon-button>
                                @else
                                <span class="text-xs italic text-gray-400">{{ $advance->status === 'paid' ? __('Deducted') : __('Processed') }}</span>
                                @endif

                                @if(Auth::user()->can('delete', $advance))
                                <x-actions.icon-button wire:click="delete('{{ $advance->id }}')" wire:confirm="{{ __('Delete permanently?') }}" variant="danger" label="{{ __('Delete cash advance request from') }} {{ $advance->user->name }}">
                                    <x-heroicon-m-trash class="h-5 w-5" />
                                </x-actions.icon-button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No cash advance data found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="cash-advance-requests-panel-mobile" role="tabpanel" aria-labelledby="cash-advance-requests-tab" tabindex="0" class="space-y-4 md:hidden">
        @forelse($advances as $advance)
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-3">
                <div class="flex min-w-0 flex-1 items-center">
                    <img class="h-10 w-10 rounded-full object-cover" src="{{ $advance->user->profile_photo_url }}" alt="{{ $advance->user->name }}">
                    <div class="ml-3 min-w-0">
                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $advance->user->name }}</div>
                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $advance->user->jobTitle->name ?? '-' }}</div>
                    </div>
                </div>
                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                    @if($advance->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($advance->status === 'paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @elseif($advance->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @elseif($advance->status === 'pending_finance') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @endif">
                    {{ __($advance->status === 'pending' ? 'Pending' : ($advance->status === 'pending_finance' ? 'Pending Finance' : ($advance->status === 'approved' ? 'Approved' : ($advance->status === 'paid' ? 'Paid' : 'Rejected')))) }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</div>
                    <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ $advance->created_at->translatedFormat('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Amount') }}</div>
                    <div class="mt-1 font-semibold text-gray-900 dark:text-white">Rp {{ number_format($advance->amount, 0, ',', '.') }}</div>
                </div>
                <div class="col-span-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Purpose') }}</div>
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $advance->purpose }}</div>
                </div>
                <div class="col-span-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Deduction Target') }}</div>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ \Carbon\Carbon::create()->month((int) $advance->payment_month)->translatedFormat('F') }} {{ $advance->payment_year }}</div>
                </div>
            </div>

            @if($advance->status !== 'pending')
            <div class="mt-3 space-y-1">
                @if($advance->head_approved_by)
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Head') }}: {{ $advance->headApprover->name ?? '-' }}</div>
                @endif
                @if($advance->finance_approved_by || $advance->approved_by)
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Finance') }}: {{ $advance->financeApprover->name ?? $advance->approver->name ?? '-' }}</div>
                @endif
            </div>
            @endif

            <div class="mt-4 flex flex-wrap gap-2">
                @php
                    $canApprove = in_array($advance->status, ['pending', 'pending_finance'], true)
                        && Auth::user()->can('approve', $advance);
                @endphp
                @if($canApprove)
                <x-actions.button type="button" wire:click="approve('{{ $advance->id }}')" wire:confirm="{{ __('Approve this request?') }}" variant="soft-success" size="sm" label="{{ __('Approve cash advance request from') }} {{ $advance->user->name }}">
                    <x-heroicon-m-check-circle class="h-5 w-5" />
                    {{ __('Approve') }}
                </x-actions.button>
                <x-actions.button type="button" wire:click="reject('{{ $advance->id }}')" wire:confirm="{{ __('Reject this request?') }}" variant="soft-danger" size="sm" label="{{ __('Reject cash advance request from') }} {{ $advance->user->name }}">
                    <x-heroicon-m-x-circle class="h-5 w-5" />
                    {{ __('Reject') }}
                </x-actions.button>
                @else
                <span class="text-xs italic text-gray-400">{{ $advance->status === 'paid' ? __('Deducted') : __('Processed') }}</span>
                @endif

                @if(Auth::user()->can('delete', $advance))
                <x-actions.button type="button" wire:click="delete('{{ $advance->id }}')" wire:confirm="{{ __('Delete permanently?') }}" variant="soft-danger" size="sm" label="{{ __('Delete cash advance request from') }} {{ $advance->user->name }}">
                    <x-heroicon-m-trash class="h-5 w-5" />
                    {{ __('Delete') }}
                </x-actions.button>
                @endif
            </div>
        </div>
        @empty
        <div class="user-empty-state">
            <div class="user-empty-state__icon">
                <x-heroicon-o-document-text class="h-8 w-8" />
            </div>
            <h3 class="user-empty-state__title">{{ __('No cash advance data found.') }}</h3>
            <p class="user-empty-state__copy">{{ __('No cash advance requests match your current filters.') }}</p>
        </div>
        @endforelse
    </div>

    @if($advances->hasPages())
    <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        {{ $advances->links() }}
    </div>
    @endif
    @else
    <div id="cash-advance-users-panel" role="tabpanel" aria-labelledby="cash-advance-users-tab" tabindex="0" class="hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Employee') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Total Kasbon') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Deduction Breakdown') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Recent History') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse($userGrouped as $user)
                    <tr>
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full">
                                    <img class="h-10 w-10 rounded-full object-cover" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->jobTitle->name ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                            Rp {{ number_format($user->cashAdvances->whereIn('status', ['paid', 'approved', 'pending'])->sum('amount'), 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 align-top">
                            @php
                                $groupedByMonth = $user->cashAdvances
                                    ->whereIn('status', ['paid', 'approved', 'pending'])
                                    ->groupBy(function ($item) {
                                        return $item->payment_year.'-'.str_pad($item->payment_month, 2, '0', STR_PAD_LEFT);
                                    })
                                    ->sortKeysDesc();
                            @endphp
                            <div class="space-y-2">
                                @foreach($groupedByMonth as $key => $items)
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="w-24 text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <div class="space-y-3">
                                @foreach($user->cashAdvances->sortByDesc('created_at')->take(3) as $hist)
                                <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $hist->created_at->translatedFormat('d M') }} ({{ __('Deduction') }} {{ \Carbon\Carbon::create()->month((int) $hist->payment_month)->translatedFormat('F') }})</div>
                                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">Rp {{ number_format($hist->amount, 0, ',', '.') }}</div>
                                    <div class="mt-1">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                            @if($hist->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($hist->status === 'paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @elseif($hist->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @endif">
                                            {{ __($hist->status === 'pending' ? 'Pending' : ($hist->status === 'approved' ? 'Approved' : ($hist->status === 'paid' ? 'Paid' : 'Rejected'))) }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No kasbon data found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="cash-advance-users-panel-mobile" role="tabpanel" aria-labelledby="cash-advance-users-tab" tabindex="0" class="space-y-4 md:hidden">
        @forelse($userGrouped as $user)
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <img class="h-10 w-10 rounded-full object-cover" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                    <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->jobTitle->name ?? '-' }}</div>
                </div>
            </div>

            <div class="mt-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Total Kasbon') }}</div>
                <div class="mt-1 text-base font-semibold text-gray-900 dark:text-white">Rp {{ number_format($user->cashAdvances->whereIn('status', ['paid', 'approved', 'pending'])->sum('amount'), 0, ',', '.') }}</div>
            </div>

            @php
                $groupedByMonth = $user->cashAdvances
                    ->whereIn('status', ['paid', 'approved', 'pending'])
                    ->groupBy(function ($item) {
                        return $item->payment_year.'-'.str_pad($item->payment_month, 2, '0', STR_PAD_LEFT);
                    })
                    ->sortKeysDesc();
            @endphp
            <div class="mt-4">
                <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Deduction Breakdown') }}</div>
                <div class="space-y-2">
                    @foreach($groupedByMonth as $key => $items)
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-900/40">
                        <span class="text-gray-600 dark:text-gray-300">{{ \Carbon\Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Recent History') }}</div>
                <div class="space-y-3">
                    @foreach($user->cashAdvances->sortByDesc('created_at')->take(3) as $hist)
                    <div class="rounded-xl border border-gray-100 p-3 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $hist->created_at->translatedFormat('d M') }} ({{ __('Deduction') }} {{ \Carbon\Carbon::create()->month((int) $hist->payment_month)->translatedFormat('F') }})</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">Rp {{ number_format($hist->amount, 0, ',', '.') }}</div>
                        <div class="mt-2">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                @if($hist->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($hist->status === 'paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($hist->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @endif">
                                {{ __($hist->status === 'pending' ? 'Pending' : ($hist->status === 'approved' ? 'Approved' : ($hist->status === 'paid' ? 'Paid' : 'Rejected'))) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="user-empty-state">
            <div class="user-empty-state__icon">
                <x-heroicon-o-users class="h-8 w-8" />
            </div>
            <h3 class="user-empty-state__title">{{ __('No kasbon data found.') }}</h3>
            <p class="user-empty-state__copy">{{ __('No employee cash advance summaries are available right now.') }}</p>
        </div>
        @endforelse
    </div>

    @if($userGrouped->hasPages())
    <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        {{ $userGrouped->links() }}
    </div>
    @endif
    @endif
</div>
