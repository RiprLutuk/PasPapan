<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface">
            <x-user.page-header
                :back-href="route('approvals')"
                :title="__('Approval History')"
                title-id="approval-history-title">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Tabs -->
            <div class="flex-1">
                <nav class="user-segmented-tabs" aria-label="Tabs">
                    <button wire:click="switchTab('leaves')"
                        aria-selected="{{ $activeTab === 'leaves' ? 'true' : 'false' }}"
                        class="user-segmented-tab">
                        {{ __('Leave Requests') }}
                    </button>
                    <button wire:click="switchTab('reimbursements')"
                        aria-selected="{{ $activeTab === 'reimbursements' ? 'true' : 'false' }}"
                        class="user-segmented-tab">
                        {{ __('Reimbursements') }}
                    </button>
                    <button wire:click="switchTab('overtimes')"
                        aria-selected="{{ $activeTab === 'overtimes' ? 'true' : 'false' }}"
                        class="user-segmented-tab">
                        {{ __('Overtime Requests') }}
                    </button>
                    <button wire:click="switchTab('kasbons')"
                        aria-selected="{{ $activeTab === 'kasbons' ? 'true' : 'false' }}"
                        class="user-segmented-tab">
                        {{ __('Kasbons') }}
                    </button>
                </nav>
            </div>

            <!-- Search -->
            <div class="w-full sm:w-64">
                <x-forms.input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search employee...') }}" class="w-full" />
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
                                    {{ __('Reason') }}
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
            @elseif ($activeTab === 'reimbursements')
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
                                    {{ __('Reason') }}
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
            @elseif ($activeTab === 'overtimes')
            <!-- Desktop Table -->
            <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Employee') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date & Time') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Duration') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Reason') }}</th>
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
                                    <div class="text-sm text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($overtime->date)->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($overtime->start_time)->diff(\Carbon\Carbon::parse($overtime->end_time))->format('%h hr %i min') }}
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
                                <td class="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                    <span class="truncate max-w-[200px] inline-block" title="{{ $overtime->reason }}">
                                        {{ $overtime->reason ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">{{ __('No overtime records found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Cards -->
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
                        <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 px-2 py-1 text-xs font-semibold rounded-full">
                            {{ \Carbon\Carbon::parse($overtime->start_time)->diff(\Carbon\Carbon::parse($overtime->end_time))->format('%h hr') }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($overtime->date)->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                            <div class="flex flex-col">
                                @if ($overtime->status === 'pending')
                                <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ __('Pending') }}</span>
                                @elseif($overtime->status === 'approved')
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ __('Approved') }}</span>
                                @else
                                <span class="text-red-600 dark:text-red-400 font-medium">{{ __('Rejected') }}</span>
                                @endif
                                @if($overtime->approvedBy)
                                <span class="text-[10px] text-gray-400">{{ __('by') }} {{ $overtime->approvedBy->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 italic">"{{ $overtime->reason }}"</div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">{{ __('No overtime records found') }}</div>
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
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Reason') }}</th>
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
                                <td class="px-6 py-4 whitespace-wrap text-right text-sm font-medium">
                                    <span class="truncate max-w-[200px] inline-block font-normal text-gray-500 dark:text-gray-400" title="{{ $kasbon->purpose }}">
                                        {{ $kasbon->purpose ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">{{ __('No kasbon records found') }}</td>
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
                    <div class="mt-3 flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ ucfirst($kasbon->status) }}
                            @if($kasbon->approvedBy) by {{ $kasbon->approvedBy->name }} @endif
                        </span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 italic">"{{ $kasbon->purpose }}"</div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">{{ __('No kasbon records found') }}</div>
                @endforelse
            </div>
            <div class="px-4 py-3">
                {{ $kasbons->links() }}
            </div>
            @endif
        </div>
    </div>
    </div>
</div>
