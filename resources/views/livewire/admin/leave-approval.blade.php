<x-admin.page-shell :title="__('Leave Approvals')" :description="__('Review and manage your team\'s leave requests.')">
    <x-slot name="toolbar">
        <x-admin.page-tools>

            <div class="md:col-span-2 xl:col-span-6">
                <x-forms.label for="leave-search" value="{{ __('Search leave requests') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    <x-forms.input id="leave-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search employee, NIP, or note...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="leave-status-filter" value="{{ __('Approval Status') }}" class="mb-1.5 block" />
                <x-forms.select id="leave-status-filter" wire:model.live="statusFilter" class="w-full">
                    <option value="all">{{ __('All statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                </x-forms.select>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="leave-type-filter" value="{{ __('Request Type') }}" class="mb-1.5 block" />
                <x-forms.select id="leave-type-filter" wire:model.live="requestTypeFilter" class="w-full">
                    <option value="all">{{ __('All request types') }}</option>
                    @foreach ($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                    @endforeach
                    <option value="leave">{{ __('Legacy Leave') }}</option>
                    <option value="permission">{{ __('Legacy Permission') }}</option>
                    <option value="sick">{{ __('Legacy Sick') }}</option>
                    <option value="excused">{{ __('Legacy Excused') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    @php
        $allLeaves = $groupedLeaves->getCollection();
        $pendingLeaves = $allLeaves->filter(fn($group) => $group->first()?->approval_status === 'pending')->count();
        $approvedLeaves = $allLeaves->filter(fn($group) => $group->first()?->approval_status === 'approved')->count();
        $rejectedLeaves = $allLeaves->filter(fn($group) => $group->first()?->approval_status === 'rejected')->count();
        $totalDays = $allLeaves->sum(fn($group) => $group->count());
    @endphp

    <dl class="flex flex-wrap gap-2 mb-4" role="region" aria-label="{{ __('Leave Summary') }}">
        <div class="rounded-xl border border-amber-300/70 bg-amber-50/60 px-3 py-1.5 dark:border-amber-800 dark:bg-amber-900/15 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300">{{ __('Pending') }}</dt>
            <dd class="text-sm font-bold text-amber-800 dark:text-amber-200">{{ $pendingLeaves }}</dd>
        </div>
        <div class="rounded-xl border border-emerald-300/70 bg-emerald-50/60 px-3 py-1.5 dark:border-emerald-800 dark:bg-emerald-900/15 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ __('Approved') }}</dt>
            <dd class="text-sm font-bold text-emerald-800 dark:text-emerald-200">{{ $approvedLeaves }}</dd>
        </div>
        <div class="rounded-xl border border-rose-300/70 bg-rose-50/60 px-3 py-1.5 dark:border-rose-800 dark:bg-rose-900/15 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-rose-700 dark:text-rose-300">{{ __('Rejected') }}</dt>
            <dd class="text-sm font-bold text-rose-800 dark:text-rose-200">{{ $rejectedLeaves }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 bg-white px-3 py-1.5 dark:border-slate-700 dark:bg-slate-900/80 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Days') }}</dt>
            <dd class="text-sm font-bold text-slate-900 dark:text-white">{{ $totalDays }}</dd>
        </div>
    </dl>

    <x-admin.panel>
        <div class="space-y-3 p-4 lg:hidden">
            @forelse ($groupedLeaves as $groupKey => $group)
                @php
                    $orderedGroup = $group->sortBy('date')->values();
                    $firstLeave = $orderedGroup->first();
                    $lastLeave = $orderedGroup->last();
                    $leaveIds = $orderedGroup->pluck('id')->toArray();
                    if ($group->count() > 1) {
                        $dateDisplay = $firstLeave->date->format('M Y') == $lastLeave->date->format('M Y')
                            ? $firstLeave->date->format('d').' - '.$lastLeave->date->format('d M Y').' ('.$orderedGroup->count().' days)'
                            : $firstLeave->date->format('d M').' - '.$lastLeave->date->format('d M Y').' ('.$orderedGroup->count().' days)';
                    } else {
                        $dateDisplay = $firstLeave->date->format('d M Y');
                    }
                @endphp
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start gap-3">
                        <img src="{{ $firstLeave->user->profile_photo_url }}" alt="{{ $firstLeave->user->name }}" class="h-10 w-10 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $firstLeave->user->name }}</h3>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $firstLeave->user->jobTitle->name ?? '-' }}</p>
                        </div>
                        <x-admin.status-badge :tone="$firstLeave->status === 'sick' ? 'warning' : 'info'">
                            {{ $firstLeave->leaveType?->name ?? __(ucfirst($firstLeave->status)) }}
                        </x-admin.status-badge>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Date') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $dateDisplay }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Attachment') }}</dt>
                            <dd class="mt-1">
                                @if ($firstLeave->attachment)
                                    <a href="{{ $firstLeave->attachment_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-primary-600">
                                        <x-heroicon-m-paper-clip class="h-4 w-4" /> {{ __('View') }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($firstLeave->note || ($firstLeave->approval_status === 'rejected' && $firstLeave->rejection_note))
                        <div class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                            {{ $firstLeave->note }}
                            @if ($firstLeave->approval_status === 'rejected' && $firstLeave->rejection_note)
                                <div class="mt-1 text-xs text-red-500">{{ __('Reason') }}: {{ $firstLeave->rejection_note }}</div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 flex justify-end gap-2">
                        @if ($firstLeave->approval_status === 'pending')
                            <x-actions.icon-button wire:click="approve({{ json_encode($leaveIds) }})" variant="success" label="{{ __('Approve leave request') }}">
                                <x-heroicon-m-check-circle class="h-6 w-6" />
                            </x-actions.icon-button>
                            <x-actions.icon-button wire:click="confirmReject({{ json_encode($leaveIds) }})" variant="danger" label="{{ __('Reject leave request') }}">
                                <x-heroicon-m-x-circle class="h-6 w-6" />
                            </x-actions.icon-button>
                        @else
                            <x-admin.status-badge :tone="$firstLeave->approval_status === 'approved' ? 'success' : 'danger'" pill="true" class="capitalize">
                                {{ __($firstLeave->approval_status) }}
                            </x-admin.status-badge>
                        @endif
                    </div>
                </article>
            @empty
                <x-admin.empty-state :title="__('No leave requests found')" :description="__('Try changing the status, request type, or search filter.')" class="border border-dashed border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <x-slot name="icon">
                        <x-heroicon-o-inbox class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                    </x-slot>
                </x-admin.empty-state>
            @endforelse
        </div>

        <div class="hidden lg:block">
            <table class="w-full whitespace-nowrap text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Note') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Attachment') }}</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($groupedLeaves as $groupKey => $group)
                        @php
                            $orderedGroup = $group->sortBy('date')->values();
                            $firstLeave = $orderedGroup->first();
                            $lastLeave = $orderedGroup->last();
                            $leaveIds = $orderedGroup->pluck('id')->toArray();
                            // Format Date Range
                            if ($group->count() > 1) {
                                if ($firstLeave->date->format('M Y') == $lastLeave->date->format('M Y')) {
                                    $dateDisplay =
                                        $firstLeave->date->format('d') .
                                        ' - ' .
                                        $lastLeave->date->format('d M Y') .
                                        ' (' .
                                        $orderedGroup->count() .
                                        ' days)';
                                } else {
                                    $dateDisplay =
                                        $firstLeave->date->format('d M') .
                                        ' - ' .
                                        $lastLeave->date->format('d M Y') .
                                        ' (' .
                                        $orderedGroup->count() .
                                        ' days)';
                                }
                            } else {
                                $dateDisplay = $firstLeave->date->format('d M Y');
                            }
                        @endphp
                        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <img src="{{ $firstLeave->user->profile_photo_url }}"
                                            alt="{{ $firstLeave->user->name }}" class="h-full w-full object-cover">
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $firstLeave->user->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $firstLeave->user->jobTitle->name ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $dateDisplay }}
                            </td>
                            <td class="px-4 py-3">
                                <x-admin.status-badge :tone="$firstLeave->status === 'sick' ? 'warning' : 'info'">
                                    {{ $firstLeave->leaveType?->name ?? __(ucfirst($firstLeave->status)) }}
                                </x-admin.status-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-xs truncate">
                                {{ $firstLeave->note }}
                                @if ($firstLeave->approval_status === 'rejected' && $firstLeave->rejection_note)
                                    <div class="text-xs text-red-500 mt-1">{{ __('Reason') }}:
                                        {{ $firstLeave->rejection_note }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                @if ($firstLeave->attachment)
                                    <a href="{{ $firstLeave->attachment_url }}" target="_blank"
                                        rel="noopener noreferrer"
                                        class="wcag-touch-target flex items-center gap-1 rounded text-primary-600 transition-colors hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                        <x-heroicon-m-paper-clip class="h-4 w-4" />
                                        <span>{{ __('View') }}</span>
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($firstLeave->approval_status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="approve({{ json_encode($leaveIds) }})"
                                            variant="success" label="{{ __('Approve leave request') }}">
                                            <x-heroicon-m-check-circle class="h-6 w-6" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="confirmReject({{ json_encode($leaveIds) }})"
                                            variant="danger" label="{{ __('Reject leave request') }}">
                                            <x-heroicon-m-x-circle class="h-6 w-6" />
                                        </x-actions.icon-button>
                                    </div>
                                @else
                                    <x-admin.status-badge :tone="$firstLeave->approval_status === 'approved' ? 'success' : 'danger'" pill="true" class="capitalize">
                                        {{ __($firstLeave->approval_status) }}
                                    </x-admin.status-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                <x-admin.empty-state :title="__('No leave requests found')" :description="__('Try changing the status, request type, or search filter.')"
                                    class="border-0 bg-transparent p-0 shadow-none dark:bg-transparent">
                                    <x-slot name="icon">
                                        <x-heroicon-o-inbox class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                                    </x-slot>
                                </x-admin.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($groupedLeaves->hasPages())
            <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">
                {{ $groupedLeaves->links() }}
            </div>
        @endif
    </x-admin.panel>

    <!-- Rejection Modal -->
    <x-overlays.dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">
            {{ __('Reject Leave Request') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Please provide a reason for rejecting this leave request.') }}

            <div class="mt-4">
                <x-forms.textarea wire:model="rejectionNote" placeholder="{{ __('Rejection Reason') }}"
                    class="block w-full" />
                <x-forms.input-error for="rejectionNote" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingRejection')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.danger-button class="ms-3" wire:click="reject" wire:loading.attr="disabled">
                {{ __('Reject Request') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
