<div class="py-6 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('Approval History') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('View past leave and reimbursement requests.') }}
                </p>
            </div>
            <a href="{{ route('approvals') }}" 
               class="group inline-flex items-center gap-2 p-2 sm:px-4 sm:py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg font-medium text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary-600 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 ease-in-out"
               title="{{ __('Back to Approvals') }}">
                <svg class="w-5 h-5 text-gray-500 group-hover:text-primary-600 dark:text-gray-400 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="hidden sm:inline">{{ __('Back to Approvals') }}</span>
            </a>
        </div>

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700 flex-1">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button wire:click="switchTab('leaves')"
                        class="{{ $activeTab === 'leaves' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        {{ __('Leave Requests') }}
                    </button>
                    <button wire:click="switchTab('reimbursements')"
                        class="{{ $activeTab === 'reimbursements' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        {{ __('Reimbursements') }}
                    </button>
                </nav>
            </div>

            <!-- Search -->
            <div class="w-full sm:w-64">
                <x-input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search employee...') }}" class="w-full" />
            </div>
        </div>

        <div class="space-y-6">
            @if ($activeTab === 'leaves')
                <!-- Desktop Table -->
                <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Employee') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Type') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Date') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Reason') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($leaves as $leave)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover"
                                                    src="{{ $leave->user->profile_photo_url }}"
                                                    alt="{{ $leave->user->name }}">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $leave->user->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $leave->user->jobTitle->name ?? __('N/A') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $leave->status === 'sick' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                            {{ ucfirst($leave->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($leave->date)->format('d M Y') }}
                                        @if ($leave->note)
                                            <div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $leave->note }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($leave->approval_status === 'pending')
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                {{ __('Pending') }}
                                            </span>
                                        @elseif($leave->approval_status === 'approved')
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ __('Approved') }}
                                            </span>
                                            @if($leave->approvedBy)
                                                <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $leave->approvedBy->name }}</div>
                                            @endif
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                {{ __('Rejected') }}
                                            </span>
                                            @if($leave->approvedBy)
                                                <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $leave->approvedBy->name }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">
                                        @if ($leave->approval_note)
                                            <span class="italic">{{ $leave->approval_note }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('No leave requests found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-4">
                    @forelse ($leaves as $leave)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="{{ $leave->user->profile_photo_url }}"
                                        alt="{{ $leave->user->name }}">
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $leave->user->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $leave->user->jobTitle->name ?? __('N/A') }}
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $leave->status === 'sick' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($leave->date)->format('d M Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                    <div class="flex flex-col">
                                        @if ($leave->approval_status === 'pending')
                                            <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ __('Pending') }}</span>
                                        @elseif($leave->approval_status === 'approved')
                                            <span class="text-green-600 dark:text-green-400 font-medium">{{ __('Approved') }}</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400 font-medium">{{ __('Rejected') }}</span>
                                        @endif
                                        @if($leave->approvedBy)
                                            <span class="text-[10px] text-gray-400">{{ __('by') }} {{ $leave->approvedBy->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if ($leave->note)
                                <div class="mt-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded text-xs text-gray-600 dark:text-gray-300">
                                    <span class="font-semibold">{{ __('User Note') }}:</span> {{ $leave->note }}
                                </div>
                            @endif

                            @if ($leave->approval_note)
                                <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded text-xs text-gray-600 dark:text-gray-300">
                                    <span class="font-semibold">{{ __('Admin Note') }}:</span> {{ $leave->approval_note }}
                                </div>
                            @endif
                        </div>
                    @empty
                         <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No leave requests found') }}
                        </div>
                    @endforelse
                </div>
                
                <div class="px-4 py-3">
                    {{ $leaves->links() }}
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Employee') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Type') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Amount') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Reason') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($reimbursements as $reimbursement)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover"
                                                    src="{{ $reimbursement->user->profile_photo_url }}"
                                                    alt="{{ $reimbursement->user->name }}">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $reimbursement->user->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $reimbursement->user->jobTitle->name ?? __('N/A') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ ucfirst($reimbursement->type) }}</span>
                                        <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($reimbursement->date)->format('d M Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                                        Rp {{ number_format($reimbursement->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($reimbursement->status === 'pending')
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                {{ __('Pending') }}
                                            </span>
                                        @elseif($reimbursement->status === 'approved')
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ __('Approved') }}
                                            </span>
                                            @if($reimbursement->approvedBy)
                                                <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $reimbursement->approvedBy->name }}</div>
                                            @endif
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                {{ __('Rejected') }}
                                            </span>
                                            @if($reimbursement->approvedBy)
                                                <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $reimbursement->approvedBy->name }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">
                                        @if ($reimbursement->admin_note)
                                            <span class="italic">{{ $reimbursement->admin_note }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('No reimbursement requests found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-4">
                    @forelse ($reimbursements as $reimbursement)
                         <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="{{ $reimbursement->user->profile_photo_url }}"
                                        alt="{{ $reimbursement->user->name }}">
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $reimbursement->user->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $reimbursement->user->jobTitle->name ?? __('N/A') }}
                                        </div>
                                    </div>
                                </div>
                                <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ ucfirst($reimbursement->type) }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Amount') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white font-mono">Rp {{ number_format($reimbursement->amount, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($reimbursement->date)->format('d M Y') }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-3 flex justify-between items-center">
                                <div class="flex flex-col">
                                     <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</span>
                                    @if ($reimbursement->status === 'pending')
                                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ __('Pending') }}</span>
                                    @elseif($reimbursement->status === 'approved')
                                        <span class="text-green-600 dark:text-green-400 font-medium">{{ __('Approved') }}</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400 font-medium">{{ __('Rejected') }}</span>
                                    @endif
                                     @if($reimbursement->approvedBy)
                                        <span class="text-[10px] text-gray-400">{{ __('by') }} {{ $reimbursement->approvedBy->name }}</span>
                                    @endif
                                </div>
                                
                                @if ($reimbursement->admin_note)
                                    <div class="flex-1 ml-4 text-right">
                                        <p class="text-[10px] text-gray-400">{{ __('Reason') }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 italic">{{ $reimbursement->admin_note }}</p>
                                    </div>
                                @endif
                            </div>
                         </div>
                    @empty
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No reimbursement requests found') }}
                         </div>
                    @endforelse
                </div>
                <div class="px-4 py-3">
                    {{ $reimbursements->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
