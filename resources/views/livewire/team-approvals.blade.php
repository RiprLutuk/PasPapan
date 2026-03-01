<div class="py-6 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('Team Approvals') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Manage leave and reimbursement requests from your team.') }}
                </p>
            </div>
            <a href="{{ route('approvals.history') }}"
                class="group inline-flex items-center gap-2 p-2 sm:px-4 sm:py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg font-medium text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary-600 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 ease-in-out"
                title="{{ __('History') }}">
                <svg class="w-5 h-5 text-gray-500 group-hover:text-primary-600 dark:text-gray-400 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="hidden sm:inline">{{ __('History') }}</span>
            </a>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button wire:click="switchTab('leaves')"
                    class="{{ $activeTab === 'leaves' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    {{ __('Leave Requests') }}
                </button>
                <button wire:click="switchTab('reimbursements')"
                    class="{{ $activeTab === 'reimbursements' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    {{ __('Reimbursements') }}
                </button>
                <button wire:click="switchTab('overtimes')"
                    class="{{ $activeTab === 'overtimes' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    {{ __('Overtime Requests') }}
                </button>
                <button wire:click="switchTab('kasbons')"
                    class="{{ $activeTab === 'kasbons' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    {{ __('Kasbons') }}
                </button>
            </nav>
        </div>

        @if (session()->has('success'))
        <div class="mb-4 rounded-xl bg-green-50 p-4 border border-green-100 dark:bg-green-900/20 dark:border-green-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
        @endif

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
                                    {{ __('Employee') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Type') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Date') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
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
                                                {{ $leave->user->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $leave->user->jobTitle->name ?? __('N/A') }}
                                            </div>
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
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($leave->approval_status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="approveLeave('{{ $leave->id }}')"
                                            class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors"
                                            title="{{ __('Approve') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="rejectLeave('{{ $leave->id }}')"
                                            class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors"
                                            title="{{ __('Reject') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
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
                        {{ $leave->note }}
                    </div>
                    @endif

                    @if ($leave->approval_status === 'pending')
                    <div class="mt-4 flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="rejectLeave('{{ $leave->id }}')" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{ __('Reject') }}
                        </button>
                        <button wire:click="approveLeave('{{ $leave->id }}')" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('Approve') }}
                        </button>
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
            @elseif ($activeTab === 'reimbursements')
            <!-- Reimbursement Desktop/Mobile Table (Existing Code) -->
            <!-- Desktop Table -->
            <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Employee') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Type') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Amount') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
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
                                                {{ $reimbursement->user->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $reimbursement->user->jobTitle->name ?? __('N/A') }}
                                            </div>
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
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($reimbursement->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="approveReimbursement('{{ $reimbursement->id }}')"
                                            class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors"
                                            title="{{ __('Approve') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="rejectReimbursement('{{ $reimbursement->id }}')"
                                            class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors"
                                            title="{{ __('Reject') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
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
                <!-- Mobile Card Content (Shortened for brevity as it was already present) -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                    <!-- ... content ... -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <img class="h-10 w-10 rounded-full object-cover"
                                src="{{ $reimbursement->user->profile_photo_url }}"
                                alt="{{ $reimbursement->user->name }}">
                            <!-- ... -->
                        </div>
                        <!-- ... -->
                    </div>
                    <!-- Actions -->
                    @if ($reimbursement->status === 'pending')
                    <div class="flex gap-2">
                        <button wire:click="rejectReimbursement('{{ $reimbursement->id }}')" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <button wire:click="approveReimbursement('{{ $reimbursement->id }}')" class="p-2 text-green-600 bg-green-50 rounded-lg hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                    </div>
                    @endif
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
            @elseif ($activeTab === 'overtimes')
            <!-- Overtime Table -->
            <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Employee') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date & Time') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Reason') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($overtimes as $overtime)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" src="{{ $overtime->user->profile_photo_url }}" alt="{{ $overtime->user->name }}">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $overtime->user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $overtime->user->jobTitle->name ?? __('N/A') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white font-medium">{{ \Carbon\Carbon::parse($overtime->date)->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                                        ({{ $overtime->duration_text }})
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white truncate max-w-xs">{{ $overtime->reason }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($overtime->status === 'pending')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ __('Pending') }}</span>
                                    @elseif($overtime->status === 'approved')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Approved') }}</span>
                                    @if($overtime->approvedBy)
                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $overtime->approvedBy->name }}</div>
                                    @endif
                                    @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Rejected') }}</span>
                                    @if($overtime->approvedBy)
                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $overtime->approvedBy->name }}</div>
                                    @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($overtime->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="approveOvertime('{{ $overtime->id }}')" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors" title="{{ __('Approve') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="rejectOvertime('{{ $overtime->id }}')" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors" title="{{ __('Reject') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">{{ __('No overtime requests found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Mobile List -->
            <div class="md:hidden space-y-4">
                @forelse ($overtimes as $overtime)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <img class="h-10 w-10 rounded-full object-cover" src="{{ $overtime->user->profile_photo_url }}" alt="{{ $overtime->user->name }}">
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $overtime->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $overtime->user->jobTitle->name ?? __('N/A') }}</div>
                            </div>
                        </div>
                        <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 px-2 py-1 text-xs font-semibold rounded-full">SPL</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($overtime->date)->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Time') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 italic">"{{ $overtime->reason }}"</div>

                    <div class="mt-3 flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ ucfirst($overtime->status) }}
                            @if($overtime->approvedBy) by {{ $overtime->approvedBy->name }} @endif
                        </span>
                        @if ($overtime->status === 'pending')
                        <div class="flex gap-2">
                            <button wire:click="rejectOvertime('{{ $overtime->id }}')" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            <button wire:click="approveOvertime('{{ $overtime->id }}')" class="p-2 text-green-600 bg-green-50 rounded-lg hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">{{ __('No overtime requests found') }}</div>
                @endforelse
            </div>
            <div class="px-4 py-3">
                {{ $overtimes->links() }}
            </div>
            @else
            <!-- Kasbons Table -->
            <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Employee') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Payment') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Amount') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($kasbons as $kasbon)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" src="{{ $kasbon->user->profile_photo_url }}" alt="{{ $kasbon->user->name }}">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $kasbon->user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $kasbon->user->jobTitle->name ?? __('N/A') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white font-medium">{{ \Carbon\Carbon::create()->month($kasbon->payment_month)->englishMonth }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $kasbon->payment_year }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                                    Rp {{ number_format($kasbon->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($kasbon->status === 'pending')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ __('Pending') }}</span>
                                    @elseif($kasbon->status === 'approved')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Approved') }}</span>
                                    @if($kasbon->approvedBy)
                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $kasbon->approvedBy->name }}</div>
                                    @endif
                                    @elseif($kasbon->status === 'paid')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ __('Paid') }}</span>
                                    @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Rejected') }}</span>
                                    @if($kasbon->approvedBy)
                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $kasbon->approvedBy->name }}</div>
                                    @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($kasbon->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="approveKasbon('{{ $kasbon->id }}')" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors" title="{{ __('Approve') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="rejectKasbon('{{ $kasbon->id }}')" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors" title="{{ __('Reject') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">{{ __('No kasbon requests found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Mobile List -->
            <div class="md:hidden space-y-4">
                @forelse ($kasbons as $kasbon)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <img class="h-10 w-10 rounded-full object-cover" src="{{ $kasbon->user->profile_photo_url }}" alt="{{ $kasbon->user->name }}">
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $kasbon->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $kasbon->user->jobTitle->name ?? __('N/A') }}</div>
                            </div>
                        </div>
                        <span class="bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 px-2 py-1 text-xs font-semibold rounded-full">Rp</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Payment Month') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::create()->month($kasbon->payment_month)->englishMonth }} {{ $kasbon->payment_year }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Amount') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white font-mono">
                                Rp {{ number_format($kasbon->amount, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 italic">"{{ $kasbon->purpose }}"</div>

                    <div class="mt-3 flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ ucfirst($kasbon->status) }}
                            @if($kasbon->approvedBy) by {{ $kasbon->approvedBy->name }} @endif
                        </span>
                        @if ($kasbon->status === 'pending')
                        <div class="flex gap-2">
                            <button wire:click="rejectKasbon('{{ $kasbon->id }}')" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            <button wire:click="approveKasbon('{{ $kasbon->id }}')" class="p-2 text-green-600 bg-green-50 rounded-lg hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">{{ __('No kasbon requests found') }}</div>
                @endforelse
            </div>
            <div class="px-4 py-3">
                {{ $kasbons->links() }}
            </div>
            @endif
        </div>
    </div>
</div>