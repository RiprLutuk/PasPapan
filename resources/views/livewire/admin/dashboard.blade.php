@php
    $date =
        $selectedDate instanceof \Carbon\CarbonInterface
            ? $selectedDate->copy()->startOfDay()
            : \Carbon\Carbon::parse($selectedDate)->startOfDay();
    $isToday = $date->isToday();
    $checkedInCount = $presentCount + $lateCount;
    $leaveCount = $excusedCount + $sickCount;
    $attendanceCoverage = $employeesCount > 0 ? round(($checkedInCount / $employeesCount) * 100) : 0;
    $resolutionCoverage = $employeesCount > 0 ? round((($checkedInCount + $leaveCount) / $employeesCount) * 100) : 0;
    $actionQueueCount =
        $pendingLeavesCount + $pendingReimbursementsCount + ($pendingOvertimesCount ?? 0) + ($pendingKasbonCount ?? 0);
    $chartRangeLabel = match ($chartFilter) {
        'week_2' => __('2 Weeks'),
        'week_3' => __('3 Weeks'),
        'month_1', 'month' => __('1 Month'),
        'month_2' => __('2 Months'),
        'month_3' => __('3 Months'),
        default => __('1 Week'),
    };
    $queueLinks = [
        [
            'label' => __('Leave Requests'),
            'value' => $pendingLeavesCount,
            'route' => route('admin.leaves'),
        ],
        [
            'label' => __('Reimbursements'),
            'value' => $pendingReimbursementsCount,
            'route' => route('admin.reimbursements'),
        ],
        [
            'label' => __('Overtimes'),
            'value' => $pendingOvertimesCount ?? 0,
            'route' => route('admin.overtime'),
        ],
        [
            'label' => __('Cash Advances'),
            'value' => $pendingKasbonCount ?? 0,
            'route' => route('admin.manage-kasbon'),
        ],
    ];
    $reportingLocked = \App\Helpers\Editions::reportingLocked();
    $exportLockTitle = __('Export Locked');
    $exportLockMessage = __('Advanced reporting is an Enterprise feature. Please upgrade.');
@endphp

<x-admin.page-shell :title="__('Attendance Overview')" :description="$date->translatedFormat('l, d F Y')">
    <x-slot name="actions">
        <div class="flex flex-wrap items-center justify-end gap-2">
            <label for="selectedDate" class="sr-only">{{ __('Date') }}</label>
            <x-forms.input
                id="selectedDate"
                type="date"
                wire:model.live="selectedDate"
                max="{{ now()->toDateString() }}"
                class="border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />

            @unless ($isToday)
                <x-actions.button type="button" wire:click="resetSelectedDate" variant="secondary" size="sm">
                    {{ __('Today') }}
                </x-actions.button>
            @endunless

            @if ($activeHolidaysCount > 0)
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                    <x-heroicon-o-sparkles class="h-4 w-4" />
                    {{ $isToday ? __('Holiday Today') : __('Holiday') }}
                </span>
            @endif
        </div>
    </x-slot>

    <div wire:poll.15s class="space-y-4">
        <div class="grid gap-3 md:grid-cols-2">
            <div
                class="rounded-3xl border border-slate-200/70 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            {{ __('Operational Snapshot') }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">
                            {{ $isToday ? __('Team readiness for today') : __('Team readiness on :date', ['date' => $date->translatedFormat('d M Y')]) }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                            {{ __('Employees') }}: {{ $employeesCount }} • {{ __('Pending') }}: {{ $actionQueueCount }}
                            • {{ __('Coverage :value%', ['value' => $resolutionCoverage]) }}
                        </p>
                    </div>
                    <x-admin.tone-panel tone="primary" class="px-2.5 py-1.5 text-right">
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary-700 dark:text-primary-300">
                            {{ __('Attendance Coverage') }}</p>
                        <p class="mt-0.5 text-lg font-semibold text-slate-900 dark:text-white">
                            {{ $attendanceCoverage }}%</p>
                    </x-admin.tone-panel>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <x-admin.tone-panel class="px-3 py-2.5">
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Present') }}</p>
                        <p class="mt-0.5 text-lg font-semibold text-slate-900 dark:text-white">{{ $presentCount }}</p>
                    </x-admin.tone-panel>
                    <x-admin.tone-panel tone="amber" class="px-3 py-2.5">
                        <p class="text-xs text-amber-700 dark:text-amber-300">{{ __('Late') }}</p>
                        <p class="mt-0.5 text-lg font-semibold text-slate-900 dark:text-white">{{ $lateCount }}</p>
                    </x-admin.tone-panel>
                    <x-admin.tone-panel tone="sky" class="px-3 py-2.5">
                        <p class="text-xs text-sky-700 dark:text-sky-300">{{ __('Approved Leave') }}</p>
                        <p class="mt-0.5 text-lg font-semibold text-slate-900 dark:text-white">{{ $leaveCount }}</p>
                    </x-admin.tone-panel>
                    <x-admin.tone-panel tone="rose" class="px-3 py-2.5">
                        <p class="text-xs text-rose-700 dark:text-rose-300">{{ __('No Record') }}</p>
                        <p class="mt-0.5 text-lg font-semibold text-slate-900 dark:text-white">{{ $absentCount }}</p>
                    </x-admin.tone-panel>
                </div>
            </div>

            <x-admin.insight-panel class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            {{ __('Signals') }}</p>
                        <h3 class="mt-1 text-base font-semibold text-slate-950 dark:text-white">
                            {{ __('What still needs attention') }}</h3>
                    </div>
                    <x-heroicon-o-bell-alert class="h-5 w-5 text-amber-500" />
                </div>

                <div class="mt-3 space-y-2">
                    <x-admin.tone-panel tone="amber" class="flex items-center justify-between gap-3 px-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold leading-5 text-slate-900 dark:text-white">
                                {{ __('Face Enrollment Gap') }}</p>
                            <p class="mt-0.5 text-[11px] leading-4 text-slate-600 dark:text-slate-300">
                                {{ __('Employees still missing biometric enrollment for attendance verification.') }}
                            </p>
                        </div>
                        <span
                            class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ $missingFaceDataCount }}</span>
                    </x-admin.tone-panel>

                    <x-admin.tone-panel tone="rose" class="flex items-center justify-between gap-3 px-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold leading-5 text-slate-900 dark:text-white">
                                {{ __('Open Overdue Checkout') }}</p>
                            <p class="mt-0.5 text-[11px] leading-4 text-slate-600 dark:text-slate-300">
                                {{ __('People who checked in but still have no checkout after shift end.') }}</p>
                        </div>
                        <span
                            class="text-lg font-semibold text-rose-700 dark:text-rose-300">{{ $overdueUsers->count() }}</span>
                    </x-admin.tone-panel>

                    @if ($missingFaceDataCount === 0 && $overdueUsers->isEmpty() && $actionQueueCount === 0)
                        <x-admin.tone-panel tone="primary" class="p-3 text-sm text-primary-700 dark:text-primary-300">
                            {{ __('No critical issues detected right now.') }}
                        </x-admin.tone-panel>
                    @endif
                </div>
            </x-admin.insight-panel>
        </div>

        <x-admin.insight-panel class="p-4"
            x-data="weeklyAttendanceChart()" x-init="initChart()">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-3xl">
                    <h3 class="text-lg font-semibold text-slate-950 dark:text-white">
                        {{ __('Daily attendance movement') }}</h3>
                    <p class="mt-0.5 text-xs leading-5 text-slate-600 dark:text-slate-300">
                        {{ __('Based on :range ending on the selected date.', ['range' => $chartRangeLabel]) }}</p>
                </div>

                <div class="w-full sm:w-48">
                    <label for="chartFilter" class="sr-only">{{ __('Chart Range') }}</label>
                    <x-forms.select id="chartFilter" wire:model.live="chartFilter"
                        class="block w-full border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="week_1">{{ __('1 Week') }}</option>
                        <option value="week_2">{{ __('2 Weeks') }}</option>
                        <option value="week_3">{{ __('3 Weeks') }}</option>
                        <option value="month_1">{{ __('1 Month') }}</option>
                        <option value="month_2">{{ __('2 Months') }}</option>
                        <option value="month_3">{{ __('3 Months') }}</option>
                    </x-forms.select>
                </div>
            </div>

            <div class="mt-4 h-[320px]" wire:ignore>
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-admin.insight-panel>

        <x-admin.insight-panel class="p-4"
            wire:poll.10s>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                        {{ __('User Access') }}</p>
                    <h3 class="mt-1.5 text-base font-semibold text-slate-950 dark:text-white">
                        {{ __('Live notifications and login activity') }}</h3>
                    <p class="mt-2.5 max-w-xl text-xs leading-5 text-slate-600 dark:text-slate-300">
                        {{ __('Notifications, approvals, and login status') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 lg:pt-1">
                    <a href="{{ route('admin.notifications') }}"
                        class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                        {{ __('Notifications') }}: {{ $unreadNotificationsCount }}
                    </a>
                    <a href="{{ route('admin.activity-logs') }}"
                        class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-primary-200 hover:text-primary-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-primary-900/40 dark:hover:text-primary-300">
                        {{ __('Activity Logs') }}
                    </a>
                </div>
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <x-admin.tone-panel tone="primary" class="px-3 py-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-700 dark:text-primary-300">
                        {{ __('Notifications') }}</p>
                    <p class="mt-0.5 text-base font-semibold text-slate-950 dark:text-white">
                        {{ $unreadNotificationsCount }}</p>
                </x-admin.tone-panel>
                <x-admin.tone-panel tone="amber" class="px-3 py-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-300">
                        {{ __('Pending Approvals') }}</p>
                    <p class="mt-0.5 text-base font-semibold text-slate-950 dark:text-white">{{ $actionQueueCount }}
                    </p>
                </x-admin.tone-panel>
                <x-admin.tone-panel tone="emerald" class="px-3 py-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">
                        {{ __('Logged In') }}</p>
                    <p class="mt-0.5 text-base font-semibold text-slate-950 dark:text-white">{{ $loggedInUsersCount }}
                    </p>
                </x-admin.tone-panel>
                <x-admin.tone-panel tone="rose" class="px-3 py-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-700 dark:text-rose-300">
                        {{ __('Never Logged In') }}</p>
                    <p class="mt-0.5 text-base font-semibold text-slate-950 dark:text-white">{{ $neverLoggedInCount }}
                    </p>
                </x-admin.tone-panel>
            </div>

            <div class="mt-3 grid gap-3 xl:grid-cols-2">
                <x-admin.tone-panel class="p-3">
                    <div
                        class="flex items-center justify-between gap-3  border-slate-200/70 pb-2.5 dark:border-slate-700/80">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ __('Unread Notifications') }}</h4>
                        <span class="text-xs text-slate-400">{{ $unreadNotificationsCount }}
                            {{ __('Items') }}</span>
                    </div>

                    <div class="mt-3 space-y-2">
                        @forelse ($unreadNotificationsPreview as $notification)
                            <a href="{{ \App\Support\Helpers::normalizeInternalUrl($notification->data['url'] ?? ($notification->data['action_url'] ?? route('admin.notifications'))) }}"
                                class="block rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 transition hover:border-primary-200 hover:bg-primary-50/40 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-primary-900/40 dark:hover:bg-primary-900/10">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $notification->data['title'] ?? __('Notification') }}</p>
                                <p class="mt-0.5 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $notification->data['message'] ?? '' }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ $notification->created_at->diffForHumans() }}</p>
                            </a>
                        @empty
                            <div
                                class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                                {{ __('No unread notifications.') }}
                            </div>
                        @endforelse
                    </div>
                </x-admin.tone-panel>

                <x-admin.tone-panel class="p-3">
                    <div
                        class="flex items-center justify-between gap-3 border-slate-200/70 pb-2.5 dark:border-slate-700/80">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Pending Approvals') }}
                        </h4>
                        <span class="text-xs text-slate-400">{{ $actionQueueCount }} {{ __('Items') }}</span>
                    </div>

                    <div class="mt-3 space-y-2">
                        @forelse ($queueLinks as $item)
                            <a href="{{ $item['route'] }}"
                                class="flex items-center justify-between rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm transition hover:border-primary-200 hover:bg-primary-50/40 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-primary-900/40 dark:hover:bg-primary-900/10">
                                <span
                                    class="font-medium text-slate-700 dark:text-slate-200">{{ $item['label'] }}</span>
                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm dark:bg-slate-950 dark:text-slate-200">{{ $item['value'] }}</span>
                            </a>
                        @empty
                            <div
                                class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                                {{ __('No pending approvals.') }}
                            </div>
                        @endforelse
                    </div>
                </x-admin.tone-panel>

                <x-admin.tone-panel class="p-3">
                    <div
                        class="flex items-start justify-between gap-3 border-slate-200/70 pb-2.5 dark:border-slate-700/80">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ __('Live Activity Feed') }}</h4>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                {{ __('Latest user actions and logins') }}</p>
                        </div>
                        <span class="text-xs text-slate-400">{{ __('Updates every 10s') }}</span>
                    </div>

                    <div class="mt-3 space-y-2">
                        @forelse ($recentUserActivities as $activity)
                            <div
                                class="rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                                            {{ $activity['user_name'] }}</p>
                                        <p class="mt-1 text-xs font-medium text-slate-600 dark:text-slate-300">
                                            {{ $activity['summary'] }}</p>
                                        @if ($activity['detail'])
                                            <p class="mt-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">
                                                {{ $activity['detail'] }}</p>
                                        @endif
                                    </div>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $activity['badge_class'] }}">
                                        {{ $activity['badge'] }}
                                    </span>
                                </div>
                                <div
                                    class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400">
                                    <span>{{ $activity['created_at']->diffForHumans() }}</span>
                                    @if ($activity['ip_address'])
                                        <span>{{ __('IP') }}: {{ $activity['ip_address'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                                {{ __('No recent user activity found.') }}
                            </div>
                        @endforelse
                    </div>
                </x-admin.tone-panel>

                <x-admin.tone-panel class="p-3">
                    <div
                        class="flex items-center justify-between gap-3 border-slate-200/70 pb-2.5 dark:border-slate-700/80">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ __('Not logged in on selected date') }}</h4>
                        <span class="text-xs text-slate-400">{{ $notLoggedInUsersCount }} {{ __('Users') }}</span>
                    </div>

                    <div class="mt-3 space-y-2">
                        @forelse ($notLoggedInUsers as $user)
                            <div
                                class="rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                                            {{ $user->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $user->nip ?: '-' }}</p>
                                    </div>
                                    <span
                                        class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                                        {{ __('No Login') }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                                {{ __('All managed users already logged in on the selected date.') }}
                            </div>
                        @endforelse
                    </div>

                    @if ($notLoggedInUsers->hasPages())
                        <div class="mt-3 border-t border-slate-200/70 pt-3 dark:border-slate-700/80">
                            {{ $notLoggedInUsers->onEachSide(1)->links() }}
                        </div>
                    @endif
                </x-admin.tone-panel>
            </div>
        </x-admin.insight-panel>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-admin.insight-panel class="p-3">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            {{ __('Overdue Checkout') }}</p>
                        <h3 class="mt-0.5 text-sm font-semibold text-slate-950 dark:text-white">
                            {{ __('People who still need a reminder') }}</h3>
                    </div>
                    <span
                        class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ $overdueUsers->count() }}</span>
                </div>

                <div class="mt-3 space-y-2">
                    @forelse ($overdueUsers as $overdue)
                        <div
                            class="flex items-center justify-between gap-3 rounded-2xl border border-rose-100 bg-rose-50/70 px-3 py-2 dark:border-rose-900/30 dark:bg-rose-900/10">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $overdue->user->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Shift End') }}:
                                    {{ $overdue->shift->end_time }}</p>
                            </div>
                            <x-actions.button type="button" wire:click="notifyUser('{{ $overdue->id }}')"
                                wire:loading.attr="disabled" variant="soft-danger" size="sm"
                                label="{{ __('Send checkout reminder to') }} {{ $overdue->user->name }}">
                                {{ __('Remind') }}
                            </x-actions.button>
                        </div>
                    @empty
                        <x-admin.tone-panel tone="primary" class="px-3 py-3 text-sm text-primary-700 dark:text-primary-300">
                            {{ __('All clear! No overdue checkouts.') }}
                        </x-admin.tone-panel>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="p-3">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            {{ __('Schedule Watch') }}</p>
                        <h3 class="mt-0.5 text-sm font-semibold text-slate-950 dark:text-white">
                            {{ __('Upcoming Leaves') }}</h3>
                    </div>
                    @if ($reportingLocked)
                        <x-actions.button href="#" variant="ghost" size="sm"
                            @click.prevent="$dispatch('feature-lock', { title: @js($exportLockTitle), message: @js($exportLockMessage) })">
                            {{ __('Export') }} {{ __('Locked') }}
                        </x-actions.button>
                    @else
                        <x-actions.button href="{{ route('admin.reports.export-pdf') }}" target="_system"
                            variant="ghost" size="sm">
                            {{ __('Export') }}
                        </x-actions.button>
                    @endif
                </div>

                <div class="mt-3 space-y-2">
                    @forelse ($calendarLeaves->take(6) as $leave)
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-200/70 bg-slate-50/70 px-3 py-2 dark:border-slate-700 dark:bg-slate-800/70">
                            <div
                                class="flex h-10 w-10 flex-none flex-col items-center justify-center rounded-2xl bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                                <span
                                    class="text-xs font-semibold">{{ \Carbon\Carbon::parse($leave['start_date'])->format('d') }}</span>
                                <span
                                    class="text-[10px] uppercase">{{ \Carbon\Carbon::parse($leave['start_date'])->translatedFormat('M') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $leave['title'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $leave['date_display'] }}</p>
                            </div>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $leave['status'] === 'sick' ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300' : 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300' }}">
                                {{ __(ucfirst($leave['status'])) }}
                            </span>
                        </div>
                    @empty
                        <x-admin.tone-panel class="px-3 py-3 text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No leaves schedule for this month.') }}
                        </x-admin.tone-panel>
                    @endforelse
                </div>
            </x-admin.insight-panel>
        </div>

        <x-admin.insight-panel class="p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                        {{ __('Team Attendance') }}</p>
                    <h3 class="mt-1 text-base font-semibold text-slate-950 dark:text-white">
                        {{ __('View attendance by employee') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-300">
                        {{ __('Search the team list to review shift, attendance status, and supporting details for the selected date.') }}
                    </p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <div class="relative w-full sm:w-64">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                        </div>
                        <x-forms.input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search employee or NIP') }}"
                            class="block w-full border-slate-200 bg-white pl-10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500" />
                    </div>

                    <x-actions.button href="{{ route('admin.employees') }}" variant="soft-primary" size="sm">
                        <x-heroicon-o-users class="h-4 w-4" />
                        {{ __('Open Employees') }}
                    </x-actions.button>
                </div>
            </div>

            <div class="mt-4 space-y-3 sm:hidden">
                @foreach ($employees as $employee)
                    @php
                        $attendance = $employee->attendance;
                        $timeIn = $attendance ? \App\Helpers::format_time($attendance->time_in) : null;
                        $timeOut = $attendance ? \App\Helpers::format_time($attendance->time_out) : null;
                        $isWeekend = $date->isWeekend();
                        $status = ($attendance ?? ['status' => $isWeekend || !$date->isPast() ? '-' : 'absent'])[
                            'status'
                        ];
                        switch ($status) {
                            case 'present':
                                $statusLabel = __('Present');
                                $statusColor =
                                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/30 dark:text-emerald-300';
                                break;
                            case 'late':
                                $statusLabel = __('Late');
                                $statusColor =
                                    'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300';
                                break;
                            case 'excused':
                                $statusLabel = __('Excused');
                                $statusColor =
                                    'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/30 dark:text-sky-300';
                                break;
                            case 'sick':
                                $statusLabel = __('Sick');
                                $statusColor =
                                    'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-300';
                                break;
                            case 'absent':
                                $statusLabel = __('Absent');
                                $statusColor =
                                    'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-900/30 dark:text-rose-300';
                                break;
                            default:
                                $statusLabel = '-';
                                $statusColor =
                                    'bg-slate-50 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-400';
                                break;
                        }
                    @endphp

                    <x-admin.tone-panel class="p-3 bg-slate-50/60 dark:bg-slate-800/60">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                                    {{ substr($employee->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $employee->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $employee->jobTitle?->name ?? __('Staff') }}</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                                {{ $statusLabel }}
                                @if ($attendance && $attendance->is_suspicious)
                                    <span title="{{ $attendance->suspicious_reason }}"
                                        class="ml-1 cursor-help text-rose-500">!</span>
                                @endif
                            </span>
                        </div>

                        <div
                            class="mt-3 grid grid-cols-2 gap-3 border-t border-slate-200 pt-2.5 dark:border-slate-700">
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Time In') }}</p>
                                <p class="mt-0.5 font-mono text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $timeIn ?? '--:--' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Time Out') }}</p>
                                <p class="mt-0.5 font-mono text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $timeOut ?? '--:--' }}</p>
                            </div>
                        </div>

                        @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                            <div class="mt-3 border-t border-slate-200 pt-2.5 dark:border-slate-700">
                                <x-actions.button type="button" wire:click="show({{ $attendance->id }})"
                                    variant="soft-primary" size="sm" class="w-full justify-center">
                                    {{ __('View Details') }}
                                </x-actions.button>
                            </div>
                        @endif
                    </x-admin.tone-panel>
                @endforeach
            </div>

            <div
                class="mt-4 hidden overflow-x-auto rounded-2xl border border-slate-200/70 sm:block dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50/90 dark:bg-slate-900/70">
                        <tr>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('Employee') }}</th>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('Shift') }}</th>
                            <th
                                class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('Status') }}</th>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('Time In') }}</th>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('Time Out') }}</th>
                            <th
                                class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('Detail') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900/40">
                        @foreach ($employees as $employee)
                            @php
                                $attendance = $employee->attendance;
                                $timeIn = $attendance ? \App\Helpers::format_time($attendance->time_in) : null;
                                $timeOut = $attendance ? \App\Helpers::format_time($attendance->time_out) : null;
                                $isWeekend = $date->isWeekend();
                                $status = ($attendance ?? [
                                    'status' => $isWeekend || !$date->isPast() ? '-' : 'absent',
                                ])['status'];
                                switch ($status) {
                                    case 'present':
                                        $statusLabel = __('Present');
                                        $statusDot = 'bg-emerald-500';
                                        $statusColor =
                                            'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300';
                                        break;
                                    case 'late':
                                        $statusLabel = __('Late');
                                        $statusDot = 'bg-amber-500';
                                        $statusColor =
                                            'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300';
                                        break;
                                    case 'excused':
                                        $statusLabel = __('Excused');
                                        $statusDot = 'bg-sky-500';
                                        $statusColor =
                                            'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-300';
                                        break;
                                    case 'sick':
                                        $statusLabel = __('Sick');
                                        $statusDot = 'bg-purple-500';
                                        $statusColor =
                                            'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/20 dark:text-purple-300';
                                        break;
                                    case 'absent':
                                        $statusLabel = __('Absent');
                                        $statusDot = 'bg-rose-500';
                                        $statusColor =
                                            'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-300';
                                        break;
                                    default:
                                        $statusLabel = '-';
                                        $statusDot = 'bg-slate-400';
                                        $statusColor =
                                            'bg-slate-50 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-400';
                                        break;
                                }
                            @endphp

                            <tr wire:key="{{ $employee->id }}"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                            {{ substr($employee->name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                                {{ $employee->name }}</p>
                                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                                {{ $employee->jobTitle?->name ?? __('Staff') }} •
                                                {{ $employee->division?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $attendance->shift?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                        {{ $statusLabel }}
                                        @if ($attendance && $attendance->is_suspicious)
                                            <span title="{{ $attendance->suspicious_reason }}"
                                                class="ml-1 cursor-help text-rose-500">!</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">
                                    {{ $timeIn ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">
                                    {{ $timeOut ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                                        <x-actions.icon-button type="button" wire:click="show({{ $attendance->id }})"
                                            variant="primary"
                                            label="{{ __('View attendance detail for') }} {{ $employee->name }}">
                                            <x-heroicon-m-eye class="h-4 w-4" />
                                        </x-actions.icon-button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        </x-admin.insight-panel>
    </div>

    <x-shared.attendance-detail-modal :current-attendance="$currentAttendance" />

    <x-overlays.dialog-modal wire:model="showStatModal" maxWidth="2xl">
        <x-slot name="title">
            @php
                $statTitle = match ($selectedStatType) {
                    'absent' => __('Not Present'),
                    'checked_in' => __('Checked In'),
                    default => __(ucfirst(str_replace('_', ' ', $selectedStatType))),
                };
            @endphp
            {{ __('Detail List') }}:
            <span class="capitalize">
                {{ $statTitle }}
            </span>
        </x-slot>

        <x-slot name="content">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('Name') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ __('NIP') }}</th>
                            @if ($selectedStatType !== 'absent')
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                    {{ __('Status') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                    {{ __('Time') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900/50">
                        @forelse ($detailList as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ isset($item->user) ? $item->user->name : $item->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                    {{ isset($item->user) ? $item->user->nip : $item->nip }}</td>
                                @if ($selectedStatType !== 'absent')
                                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->status === 'present'
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                                                : ($item->status === 'late'
                                                    ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300'
                                                    : ($item->status === 'sick'
                                                        ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300'
                                                        : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300')) }}">
                                            {{ __(ucfirst($item->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                        {{ $item->time_in ? \App\Helpers::format_time($item->time_in) : '-' }}
                                        @if ($item->time_out)
                                            - {{ \App\Helpers::format_time($item->time_out) }}
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $selectedStatType !== 'absent' ? 4 : 2 }}"
                                    class="px-4 py-5 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No data found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="closeStatModal" wire:loading.attr="disabled" class="!px-3 !py-2">
                {{ __('Close') }}
            </x-actions.secondary-button>
        </x-slot>
    </x-overlays.dialog-modal>

    @stack('attendance-detail-scripts')

    <script>
        window.dashboardChartData = @json($chartData);

        function weeklyAttendanceChart() {
            let chart = null;

            return {
                initChart() {
                    if (typeof Chart === 'undefined') {
                        if (this.retryCount === undefined) this.retryCount = 0;
                        if (this.retryCount < 20) {
                            this.retryCount++;
                            setTimeout(() => this.initChart(), 100);
                        } else {
                            console.error('Chart.js is not available. The attendance chart cannot be rendered.');
                        }
                        return;
                    }

                    const ctx = this.$refs.canvas;
                    if (!ctx) return;

                    if (chart) {
                        chart.destroy();
                    }

                    Livewire.on('chart-updated', (data) => {
                        const chartData = data[0] || data;

                        if (chart) {
                            chart.data.labels = chartData.labels;
                            chart.data.datasets[0].data = chartData.present;
                            chart.data.datasets[1].data = chartData.late;
                            chart.data.datasets[2].data = chartData.other;
                            chart.update();
                        }
                    });

                    const presentGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 360);
                    presentGradient.addColorStop(0, 'rgba(22, 163, 74, 0.22)');
                    presentGradient.addColorStop(1, 'rgba(22, 163, 74, 0)');

                    chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: window.dashboardChartData.labels,
                            datasets: [{
                                    label: '{{ __('Present') }}',
                                    data: window.dashboardChartData.present,
                                    borderColor: '#16a34a',
                                    backgroundColor: presentGradient,
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 4,
                                },
                                {
                                    label: '{{ __('Late') }}',
                                    data: window.dashboardChartData.late,
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'transparent',
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 4,
                                },
                                {
                                    label: '{{ __('Excused') }}/{{ __('Sick') }}',
                                    data: window.dashboardChartData.other,
                                    borderColor: '#0ea5e9',
                                    backgroundColor: 'transparent',
                                    borderDash: [6, 6],
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 4,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        display: false,
                                    },
                                    border: {
                                        display: false,
                                    },
                                },
                                x: {
                                    grid: {
                                        display: false,
                                    },
                                    border: {
                                        display: false,
                                    },
                                }
                            },
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                        }
                    });
                }
            };
        }
    </script>
</x-admin.page-shell>
