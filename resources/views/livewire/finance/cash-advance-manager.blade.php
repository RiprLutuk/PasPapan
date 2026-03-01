<div class="py-12">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    {{ __('Manage Kasbon') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Manage and approve employee cash advance requests.') }}
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="statusFilter" class="block w-full rounded-lg border-0 py-2 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                    <option value="all">{{ __('All Status') }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
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
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $advance->user->jobTitle->name ?? '-' }} (Rank {{ $advance->user->jobTitle->jobLevel->rank ?? '-' }})</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                <div>{{ $advance->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $advance->purpose }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($advance->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::create()->month($advance->payment_month)->translatedFormat('F') }} {{ $advance->payment_year }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-start">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                            @if($advance->status === 'approved') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/50
                                            @elseif($advance->status === 'paid') bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-500/50
                                            @elseif($advance->status === 'rejected') bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/50
                                            @else bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/50 @endif">
                                        {{ __($advance->status === 'pending' ? 'Pending' : ($advance->status === 'approved' ? 'Approved' : ($advance->status === 'paid' ? 'Paid' : 'Rejected'))) }}
                                    </span>
                                    @if($advance->status !== 'pending')
                                    <span class="text-[10px] mt-1 text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        {{ $advance->approver->name ?? '-' }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($advance->status === 'pending')
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
        </div>
    </div>
</div>