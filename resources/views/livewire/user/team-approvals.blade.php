<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Team Approvals')"
                title-id="team-approvals-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-users class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <a href="{{ route('approvals.history') }}"
                        class="wcag-touch-target inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white sm:w-auto"
                        title="{{ __('History') }}">
                        <x-heroicon-o-clock class="h-5 w-5" />
                        <span>{{ __('History') }}</span>
                    </a>
                </x-slot>
            </x-user.page-header>

        <div class="user-page-body pt-0">
        <div class="mb-6">
            <nav class="user-segmented-tabs" aria-label="{{ __('Tabs') }}">
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
                <button wire:click="switchTab('attendance-corrections')"
                    aria-selected="{{ $activeTab === 'attendance-corrections' ? 'true' : 'false' }}"
                    class="user-segmented-tab">
                    {{ __('Attendance Corrections') }}
                </button>
                <button wire:click="switchTab('shift-swaps')"
                    aria-selected="{{ $activeTab === 'shift-swaps' ? 'true' : 'false' }}"
                    class="user-segmented-tab">
                    {{ __('Shift Swaps') }}
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

        @if (session()->has('success'))
            <div
                class="mb-4 rounded-xl bg-green-50 p-4 border border-green-100 dark:bg-green-900/20 dark:border-green-800">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
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
                <div
                    class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
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
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($leave->date)->format('d M Y') }}
                                            @if ($leave->note)
                                                <div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">
                                                    {{ $leave->note }}
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
                                                @if ($leave->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $leave->approvedBy->name }}</div>
                                                @endif
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ __('Rejected') }}
                                                </span>
                                                @if ($leave->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $leave->approvedBy->name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if ($leave->approval_status === 'pending')
                                                <div class="flex justify-end gap-2">
                                                    <button wire:click="approveLeave('{{ $leave->id }}')"
                                                        class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Approve') }}">
                                                        <x-heroicon-o-check class="h-5 w-5" />
                                                    </button>
                                                    <button wire:click="rejectLeave('{{ $leave->id }}')"
                                                        class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Reject') }}">
                                                        <x-heroicon-o-x-mark class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('No leave requests found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="space-y-4 md:hidden">
                    @forelse ($leaves as $leave)
                        <div
                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3">
                                <div class="flex min-w-0 flex-1 items-center">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="{{ $leave->user->profile_photo_url }}" alt="{{ $leave->user->name }}">
                                    <div class="ml-3 min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $leave->user->name }}
                                        </div>
                                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $leave->user->jobTitle->name ?? __('N/A') }}
                                        </div>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $leave->status === 'sick' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($leave->date)->format('d M Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                    <div class="flex flex-col">
                                        @if ($leave->approval_status === 'pending')
                                            <span
                                                class="text-yellow-600 dark:text-yellow-400 font-medium">{{ __('Pending') }}</span>
                                        @elseif($leave->approval_status === 'approved')
                                            <span
                                                class="text-green-600 dark:text-green-400 font-medium">{{ __('Approved') }}</span>
                                        @else
                                            <span
                                                class="text-red-600 dark:text-red-400 font-medium">{{ __('Rejected') }}</span>
                                        @endif
                                        @if ($leave->approvedBy)
                                            <span class="text-[10px] text-gray-400">{{ __('by') }}
                                                {{ $leave->approvedBy->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if ($leave->note)
                                <div
                                    class="mt-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded text-xs text-gray-600 dark:text-gray-300">
                                    {{ $leave->note }}
                                </div>
                            @endif

                            @if ($leave->approval_status === 'pending')
                                <div
                                    class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                    <button wire:click="rejectLeave('{{ $leave->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        {{ __('Reject') }}
                                    </button>
                                    <button wire:click="approveLeave('{{ $leave->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-50 px-3 py-2.5 text-sm font-medium text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        {{ __('Approve') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No leave requests found') }}
                        </div>
                    @endforelse
                </div>

                <div class="px-2 py-3 sm:px-4">
                    {{ $leaves->links() }}
                </div>
            @elseif ($activeTab === 'attendance-corrections')
                @include('livewire.user.partials.team-attendance-corrections-pending')
            @elseif ($activeTab === 'shift-swaps')
                @include('livewire.user.partials.team-shift-swaps-pending')
            @elseif ($activeTab === 'reimbursements')
                <!-- Reimbursement Desktop/Mobile Table (Existing Code) -->
                <!-- Desktop Table -->
                <div
                    class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
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
                                            <span
                                                class="text-sm text-gray-900 dark:text-white">{{ ucfirst($reimbursement->type) }}</span>
                                            <div class="text-xs text-gray-400">
                                                {{ \Carbon\Carbon::parse($reimbursement->date)->format('d M Y') }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
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
                                                @if ($reimbursement->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $reimbursement->approvedBy->name }}</div>
                                                @endif
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ __('Rejected') }}
                                                </span>
                                                @if ($reimbursement->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $reimbursement->approvedBy->name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if ($reimbursement->status === 'pending')
                                                <div class="flex justify-end gap-2">
                                                    <button
                                                        wire:click="approveReimbursement('{{ $reimbursement->id }}')"
                                                        class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Approve') }}">
                                                        <x-heroicon-o-check class="h-5 w-5" />
                                                    </button>
                                                    <button
                                                        wire:click="rejectReimbursement('{{ $reimbursement->id }}')"
                                                        class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Reject') }}">
                                                        <x-heroicon-o-x-mark class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            @else
                                                <span
                                                    class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('No reimbursement requests found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="space-y-4 md:hidden">
                    @forelse ($reimbursements as $reimbursement)
                        <div
                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3">
                                <div class="flex min-w-0 flex-1 items-center">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="{{ $reimbursement->user->profile_photo_url }}"
                                        alt="{{ $reimbursement->user->name }}">
                                    <div class="ml-3 min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $reimbursement->user->name }}
                                        </div>
                                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $reimbursement->user->jobTitle->name ?? __('N/A') }}
                                        </div>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 dark:bg-sky-900 dark:text-sky-200">
                                    {{ ucfirst($reimbursement->type) }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Amount') }}</p>
                                    <p class="font-mono font-medium text-gray-900 dark:text-white">
                                        Rp {{ number_format($reimbursement->amount, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($reimbursement->date)->format('d M Y') }}
                                    </p>
                                </div>
                            </div>

                            @if ($reimbursement->description)
                                <div
                                    class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-700/50 dark:text-gray-300">
                                    {{ $reimbursement->description }}
                                </div>
                            @endif

                            <div class="mt-3 flex flex-col gap-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                @if ($reimbursement->status === 'pending')
                                    <span
                                        class="font-medium text-yellow-600 dark:text-yellow-400">{{ __('Pending') }}</span>
                                @elseif($reimbursement->status === 'approved')
                                    <span
                                        class="font-medium text-green-600 dark:text-green-400">{{ __('Approved') }}</span>
                                @else
                                    <span
                                        class="font-medium text-red-600 dark:text-red-400">{{ __('Rejected') }}</span>
                                @endif
                                @if ($reimbursement->approvedBy)
                                    <span class="text-[11px] text-gray-400">{{ __('by') }}
                                        {{ $reimbursement->approvedBy->name }}</span>
                                @endif
                            </div>

                            @if ($reimbursement->status === 'pending')
                                <div
                                    class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                    <button wire:click="rejectReimbursement('{{ $reimbursement->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        {{ __('Reject') }}
                                    </button>
                                    <button wire:click="approveReimbursement('{{ $reimbursement->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-50 px-3 py-2.5 text-sm font-medium text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        {{ __('Approve') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No reimbursement requests found') }}
                        </div>
                    @endforelse
                </div>
                <div class="px-2 py-3 sm:px-4">
                    {{ $reimbursements->links() }}
                </div>
            @elseif ($activeTab === 'overtimes')
                <!-- Overtime Table -->
                <div
                    class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Employee') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Date & Time') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Reason') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Status') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($overtimes as $overtime)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover"
                                                        src="{{ $overtime->user->profile_photo_url }}"
                                                        alt="{{ $overtime->user->name }}">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $overtime->user->name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $overtime->user->jobTitle->name ?? __('N/A') }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white font-medium">
                                                {{ \Carbon\Carbon::parse($overtime->date)->format('d M Y') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                                                ({{ $overtime->duration_text }})
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white truncate max-w-xs">
                                                {{ $overtime->reason }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($overtime->status === 'pending')
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ __('Pending') }}</span>
                                            @elseif($overtime->status === 'approved')
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Approved') }}</span>
                                                @if ($overtime->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $overtime->approvedBy->name }}</div>
                                                @endif
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Rejected') }}</span>
                                                @if ($overtime->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $overtime->approvedBy->name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if ($overtime->status === 'pending')
                                                <div class="flex justify-end gap-2">
                                                    <button wire:click="approveOvertime('{{ $overtime->id }}')"
                                                        class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Approve') }}">
                                                        <x-heroicon-o-check class="h-5 w-5" />
                                                    </button>
                                                    <button wire:click="rejectOvertime('{{ $overtime->id }}')"
                                                        class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Reject') }}">
                                                        <x-heroicon-o-x-mark class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            @else
                                                <span
                                                    class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('No overtime requests found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Mobile List -->
                <div class="space-y-4 md:hidden">
                    @forelse ($overtimes as $overtime)
                        <div
                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3">
                                <div class="flex min-w-0 flex-1 items-center">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="{{ $overtime->user->profile_photo_url }}"
                                        alt="{{ $overtime->user->name }}">
                                    <div class="ml-3 min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $overtime->user->name }}</div>
                                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $overtime->user->jobTitle->name ?? __('N/A') }}</div>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">SPL</span>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($overtime->date)->format('d M Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Time') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-3 rounded-xl bg-gray-50 p-3 text-sm italic text-gray-600 dark:bg-gray-700/50 dark:text-gray-300">
                                "{{ $overtime->reason }}"</div>

                            <div class="mt-3 flex flex-col gap-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                <span
                                    class="text-sm font-medium {{ $overtime->status === 'pending' ? 'text-yellow-600 dark:text-yellow-400' : ($overtime->status === 'approved' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ ucfirst($overtime->status) }}
                                </span>
                                @if ($overtime->approvedBy)
                                    <span class="text-[11px] text-gray-400">{{ __('by') }}
                                        {{ $overtime->approvedBy->name }}</span>
                                @endif
                            </div>

                            @if ($overtime->status === 'pending')
                                <div
                                    class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                    <button wire:click="rejectOvertime('{{ $overtime->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        {{ __('Reject') }}
                                    </button>
                                    <button wire:click="approveOvertime('{{ $overtime->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-50 px-3 py-2.5 text-sm font-medium text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        {{ __('Approve') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No overtime requests found') }}</div>
                    @endforelse
                </div>
                <div class="px-2 py-3 sm:px-4">
                    {{ $overtimes->links() }}
                </div>
            @else
                <!-- Kasbons Table -->
                <div
                    class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Employee') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Payment') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Amount') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Status') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($kasbons as $kasbon)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover"
                                                        src="{{ $kasbon->user->profile_photo_url }}"
                                                        alt="{{ $kasbon->user->name }}">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $kasbon->user->name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $kasbon->user->jobTitle->name ?? __('N/A') }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white font-medium">
                                                {{ \Carbon\Carbon::create()->month($kasbon->payment_month)->englishMonth }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $kasbon->payment_year }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                                            Rp {{ number_format($kasbon->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($kasbon->status === 'pending')
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ __('Pending') }}</span>
                                            @elseif($kasbon->status === 'approved')
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Approved') }}</span>
                                                @if ($kasbon->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $kasbon->approvedBy->name }}</div>
                                                @endif
                                            @elseif($kasbon->status === 'paid')
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ __('Paid') }}</span>
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Rejected') }}</span>
                                                @if ($kasbon->approvedBy)
                                                    <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }}
                                                        {{ $kasbon->approvedBy->name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if ($kasbon->status === 'pending')
                                                <div class="flex justify-end gap-2">
                                                    <button wire:click="approveKasbon('{{ $kasbon->id }}')"
                                                        class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Approve') }}">
                                                        <x-heroicon-o-check class="h-5 w-5" />
                                                    </button>
                                                    <button wire:click="rejectKasbon('{{ $kasbon->id }}')"
                                                        class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors"
                                                        title="{{ __('Reject') }}">
                                                        <x-heroicon-o-x-mark class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            @else
                                                <span
                                                    class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('No kasbon requests found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Mobile List -->
                <div class="space-y-4 md:hidden">
                    @forelse ($kasbons as $kasbon)
                        <div
                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3">
                                <div class="flex min-w-0 flex-1 items-center">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="{{ $kasbon->user->profile_photo_url }}"
                                        alt="{{ $kasbon->user->name }}">
                                    <div class="ml-3 min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $kasbon->user->name }}</div>
                                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $kasbon->user->jobTitle->name ?? __('N/A') }}</div>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">Rp</span>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Payment Month') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::create()->month($kasbon->payment_month)->englishMonth }}
                                        {{ $kasbon->payment_year }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Amount') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white font-mono">
                                        Rp {{ number_format($kasbon->amount, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-3 rounded-xl bg-gray-50 p-3 text-sm italic text-gray-600 dark:bg-gray-700/50 dark:text-gray-300">
                                "{{ $kasbon->purpose }}"</div>

                            <div class="mt-3 flex flex-col gap-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                <span
                                    class="text-sm font-medium {{ $kasbon->status === 'pending' ? 'text-yellow-600 dark:text-yellow-400' : ($kasbon->status === 'approved' ? 'text-green-600 dark:text-green-400' : ($kasbon->status === 'paid' ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400')) }}">
                                    {{ ucfirst($kasbon->status) }}
                                </span>
                                @if ($kasbon->approvedBy)
                                    <span class="text-[11px] text-gray-400">{{ __('by') }}
                                        {{ $kasbon->approvedBy->name }}</span>
                                @endif
                            </div>

                            @if ($kasbon->status === 'pending')
                                <div
                                    class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                    <button wire:click="rejectKasbon('{{ $kasbon->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        {{ __('Reject') }}
                                    </button>
                                    <button wire:click="approveKasbon('{{ $kasbon->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-50 px-3 py-2.5 text-sm font-medium text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        {{ __('Approve') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No kasbon requests found') }}</div>
                    @endforelse
                </div>
                <div class="px-2 py-3 sm:px-4">
                    {{ $kasbons->links() }}
                </div>
            @endif
        </div>
    </div>
    </div>
</div>
