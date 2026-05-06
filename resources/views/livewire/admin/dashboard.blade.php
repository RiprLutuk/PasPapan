@php
    $user = auth()->user();
    $can = fn (string $ability, mixed $arguments = []) => $user?->can($ability, $arguments) ?? false;
    $notificationsHref = $can('manageAdminNotifications') ? route('admin.notifications') : null;
    $activityLogsHref = $can('viewActivityLogs') ? route('admin.activity-logs') : null;
    $employeesHref = $can('viewEmployees') ? route('admin.employees') : null;
    $reportExportHref = $can('exportAdminReports') ? route('admin.reports.export-pdf') : null;
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
        $pendingLeavesCount + ($pendingAttendanceCorrectionsCount ?? 0) + $pendingReimbursementsCount + ($pendingOvertimesCount ?? 0) + ($pendingKasbonCount ?? 0);
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
            'visible' => $can('manageLeaveApprovals'),
        ],
        [
            'label' => __('Attendance Corrections'),
            'value' => $pendingAttendanceCorrectionsCount ?? 0,
            'route' => route('admin.attendance-corrections'),
            'visible' => $can('manageAttendanceCorrections'),
        ],
        [
            'label' => __('Reimbursements'),
            'value' => $pendingReimbursementsCount,
            'route' => route('admin.reimbursements'),
            'visible' => $user?->allowsAdminPermission('admin.reimbursements.approve') ?? false,
        ],
        [
            'label' => __('Overtimes'),
            'value' => $pendingOvertimesCount ?? 0,
            'route' => route('admin.overtime'),
            'visible' => $can('manageOvertime'),
        ],
        [
            'label' => __('Cash Advances'),
            'value' => $pendingKasbonCount ?? 0,
            'route' => route('admin.manage-kasbon'),
            'visible' => $can('manageCashAdvances'),
        ],
    ];
    $queueLinks = array_values(array_filter($queueLinks, fn (array $item): bool => $item['visible']));
    $actionQueueCount = collect($queueLinks)->sum('value');
    $reportingLocked = \App\Helpers\Editions::reportingLocked();
    $exportLockTitle = __('Export Locked');
    $exportLockMessage = __('Advanced reporting is an Enterprise feature. Please upgrade.');
@endphp

<x-admin.page-shell :title="__('Attendance Overview')" :description="$date->translatedFormat('l, d F Y')">
    <x-slot name="actions">
        <div class="flex flex-wrap items-center justify-end gap-2">
            <label for="selectedDate" class="sr-only">{{ __('Date') }}</label>
            <div wire:ignore wire:key="dashboard-selected-date-{{ $selectedDate }}">
                <x-forms.input
                    id="selectedDate"
                    type="date"
                    wire:model.live="selectedDate"
                    value="{{ $selectedDate }}"
                    max="{{ now()->toDateString() }}"
                    class="border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
            </div>

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

    <div wire:poll.15s class="space-y-3 sm:space-y-4">
        <!-- Prominent KPI Action Cards (Pending Approvals) -->
        @if(count($queueLinks) > 0)
        <x-admin.insight-panel class="p-3 sm:hidden">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Pending Queue') }}</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-slate-900 dark:text-white">{{ __('What still needs attention') }}</p>
                </div>
                <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-bold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                    {{ $actionQueueCount }}
                </span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($queueLinks as $item)
                    <a href="{{ $item['route'] }}" class="flex min-h-12 items-center justify-between gap-2 rounded-xl border border-slate-200/70 bg-slate-50 px-3 py-2 transition hover:border-primary-300 hover:bg-primary-50 dark:border-slate-800 dark:bg-slate-900/60 dark:hover:bg-primary-900/20">
                        <span class="min-w-0 truncate text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $item['label'] }}</span>
                        <span class="shrink-0 text-sm font-bold text-slate-950 dark:text-white">{{ $item['value'] }}</span>
                    </a>
                @endforeach
            </div>
        </x-admin.insight-panel>

        <div class="hidden gap-4 sm:grid sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($queueLinks as $item)
                <a href="{{ $item['route'] }}" class="relative overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm transition hover:border-primary-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ $item['label'] }}</p>
                        <x-heroicon-o-arrow-right class="h-4 w-4 text-slate-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $item['value'] }}</p>
                    <div class="absolute bottom-0 left-0 h-1 bg-primary-500" style="width: 100%"></div>
                </a>
            @endforeach
        </div>
        @endif

        <!-- Restructured Snapshot & Signals -->
        <div class="grid gap-4 md:grid-cols-2">
            <x-admin.insight-panel class="p-3 sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-3 sm:mb-4">
                    <h3 class="min-w-0 text-sm font-bold text-slate-900 dark:text-white sm:text-base">{{ $isToday ? __('Team readiness for today') : __('Team readiness on :date', ['date' => $date->translatedFormat('d M Y')]) }}</h3>
                    <span class="shrink-0 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">{{ $attendanceCoverage }}%</span>
                </div>
                <div class="grid grid-cols-[84px_1fr] items-center gap-3 sm:grid-cols-[120px_1fr] sm:gap-4">
                    <div class="h-[76px] w-[76px] sm:h-[100px] sm:w-[100px]" x-data="snapshotDonutChart()" x-init="initChart()" wire:ignore>
                        <canvas x-ref="canvas"></canvas>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="flex justify-between rounded-lg bg-emerald-50 px-2 py-1.5 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400 sm:p-2"><span class="truncate font-medium">{{ __('Present') }}</span><span class="font-bold">{{ $presentCount }}</span></div>
                        <div class="flex justify-between rounded-lg bg-amber-50 px-2 py-1.5 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400 sm:p-2"><span class="truncate font-medium">{{ __('Late') }}</span><span class="font-bold">{{ $lateCount }}</span></div>
                        <div class="flex justify-between rounded-lg bg-sky-50 px-2 py-1.5 dark:bg-sky-900/10 text-sky-700 dark:text-sky-400 sm:p-2"><span class="truncate font-medium">{{ __('Excused') }}</span><span class="font-bold">{{ $excusedCount }}</span></div>
                        <div class="flex justify-between rounded-lg bg-violet-50 px-2 py-1.5 dark:bg-violet-900/10 text-violet-700 dark:text-violet-400 sm:p-2"><span class="truncate font-medium">{{ __('Sick') }}</span><span class="font-bold">{{ $sickCount }}</span></div>
                        <div class="flex justify-between rounded-lg bg-rose-50 px-2 py-1.5 dark:bg-rose-900/10 text-rose-700 dark:text-rose-400 col-span-2 sm:p-2"><span class="truncate font-medium">{{ __('No Record') }}</span><span class="font-bold">{{ $absentCount }}</span></div>
                    </div>
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="p-3 sm:p-5">
                <div class="mb-3 flex items-center justify-between sm:mb-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">{{ __('Attention Signals') }}</h3>
                    <x-heroicon-o-bell-alert class="h-5 w-5 text-amber-500" />
                </div>
                <div class="grid grid-cols-2 gap-2 sm:block sm:space-y-3">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-amber-100 bg-amber-50/70 px-3 py-2 dark:bg-amber-900/20 dark:border-amber-900/40 sm:p-3">
                        <span class="text-xs font-semibold text-amber-900 dark:text-amber-200 sm:text-sm">{{ __('Face Enrollment Gap') }}</span>
                        <span class="text-base font-bold text-amber-700 dark:text-amber-300 sm:text-lg">{{ $missingFaceDataCount }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-rose-100 bg-rose-50/70 px-3 py-2 dark:bg-rose-900/20 dark:border-rose-900/40 sm:p-3">
                        <span class="text-xs font-semibold text-rose-900 dark:text-rose-200 sm:text-sm">{{ __('Open Overdue Checkout') }}</span>
                        <span class="text-base font-bold text-rose-700 dark:text-rose-300 sm:text-lg">{{ $overdueUsers->count() }}</span>
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>

        <x-admin.insight-panel class="p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Attendance Charts') }}</h3>
                <div class="w-full sm:w-48">
                    <label for="chartFilter" class="sr-only">{{ __('Chart Range') }}</label>
                    <x-forms.select id="chartFilter" wire:model.live="chartFilter"
                        class="block w-full border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white py-1 text-sm">
                        <option value="week_1">{{ __('1 Week') }}</option>
                        <option value="week_2">{{ __('2 Weeks') }}</option>
                        <option value="week_3">{{ __('3 Weeks') }}</option>
                        <option value="month_1">{{ __('1 Month') }}</option>
                        <option value="month_2">{{ __('2 Months') }}</option>
                        <option value="month_3">{{ __('3 Months') }}</option>
                    </x-forms.select>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/60" x-data="attendanceMovementChart()" x-init="initChart()">
                    <div class="h-[320px]" wire:ignore><canvas x-ref="canvas"></canvas></div>
                </div>
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/60" x-data="attendanceMixChart()" x-init="initChart()">
                    <div class="h-[260px]" wire:ignore><canvas x-ref="canvas"></canvas></div>
                </div>
            </div>
        </x-admin.insight-panel>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <!-- User Access Donut -->
            <x-admin.insight-panel class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('User Access Status') }}</h3>
                    <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $employeesCount }}</span>
                </div>
                <div class="grid grid-cols-[100px_1fr] items-center gap-4">
                    <div class="h-[90px] w-[90px]" x-data="userAccessDonutChart()" x-init="initChart()" wire:ignore>
                        <canvas x-ref="canvas"></canvas>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400">
                            <span class="font-medium">{{ __('Logged In') }}</span>
                            <span class="font-bold">{{ $loggedInUsersCount }}</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-amber-50 p-2 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400">
                            <span class="font-medium">{{ __('Not Logged In') }}</span>
                            <span class="font-bold">{{ $notLoggedInUsersCount }}</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-rose-50 p-2 dark:bg-rose-900/10 text-rose-700 dark:text-rose-400">
                            <span class="font-medium">{{ __('Never Logged In') }}</span>
                            <span class="font-bold">{{ $neverLoggedInCount }}</span>
                        </div>
                    </div>
                </div>
            </x-admin.insight-panel>

            <!-- Pending Approvals Chart -->
            <x-admin.insight-panel class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Pending Queue') }}</h3>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">{{ $pendingLeavesCount + $pendingAttendanceCorrectionsCount + $pendingReimbursementsCount + $pendingOvertimesCount + $pendingKasbonCount }} {{ __('total') }}</span>
                </div>
                <div class="h-[120px]" x-data="pendingQueueChart()" x-init="initChart()" wire:ignore>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </x-admin.insight-panel>

            <!-- Workforce Summary -->
            <x-admin.insight-panel class="p-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">{{ __('Workforce Snapshot') }}</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-users class="h-4 w-4 text-primary-500" />
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Total Employees') }}</span>
                        </div>
                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $employeesCount }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/30">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-check-badge class="h-4 w-4 text-emerald-500" />
                            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('Coverage Rate') }}</span>
                        </div>
                        <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ $attendanceCoverage }}%</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-sky-50/60 dark:bg-sky-900/10 border border-sky-100 dark:border-sky-900/30">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-arrow-left-end-on-rectangle class="h-4 w-4 text-sky-500" />
                            <span class="text-sm font-medium text-sky-700 dark:text-sky-300">{{ __('Early Checkout') }}</span>
                        </div>
                        <span class="text-sm font-bold text-sky-700 dark:text-sky-300">{{ $earlyCheckoutCount }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-violet-50/60 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-900/30">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calendar-days class="h-4 w-4 text-violet-500" />
                            <span class="text-sm font-medium text-violet-700 dark:text-violet-300">{{ __('Active Holidays') }}</span>
                        </div>
                        <span class="text-sm font-bold text-violet-700 dark:text-violet-300">{{ $activeHolidaysCount }}</span>
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>

        <x-admin.insight-panel class="p-5" wire:poll.10s>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('User Access & Activity') }}</h3>
                <div class="flex gap-2">
                    @if ($notificationsHref)
                        <a href="{{ $notificationsHref }}" class="rounded-full bg-primary-100 px-3 py-1 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                            {{ $unreadNotificationsCount }} {{ __('Notifs') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                <!-- Notifications -->
                <x-admin.insight-panel class="p-4">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Recent Notifications') }}</h4>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @forelse ($unreadNotificationsPreview as $notification)
                            <a href="{{ \App\Support\Helpers::normalizeInternalUrl($notification->data['url'] ?? ($notification->data['action_url'] ?? ($notificationsHref ?? $user?->preferredHomeUrl() ?? route('home')))) }}"
                                class="block rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 transition hover:bg-primary-50 dark:border-slate-700/50 dark:bg-slate-800/50 dark:hover:bg-primary-900/20">
                                <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $notification->data['title'] ?? __('Notification') }}</p>
                                <p class="mt-1 text-[10px] text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                            </a>
                        @empty
                            <p class="text-xs text-slate-500">{{ __('No unread notifications.') }}</p>
                        @endforelse
                    </div>
                </x-admin.insight-panel>

                <!-- Live Activity Feed -->
                <x-admin.insight-panel class="p-4">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Live Activity') }}</h4>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @forelse ($recentUserActivities as $activity)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 dark:border-slate-700/50 dark:bg-slate-800/50">
                                <div class="flex items-center justify-between">
                                    <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $activity['user_name'] }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $activity['badge_class'] }}">{{ $activity['badge'] }}</span>
                                </div>
                                <p class="mt-0.5 text-[10px] text-slate-500">{{ $activity['summary'] }} • {{ $activity['created_at']->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">{{ __('No recent activity.') }}</p>
                        @endforelse
                    </div>
                </x-admin.insight-panel>

                <!-- Overdue & Not Logged In -->
                <div class="space-y-4">
                    <x-admin.insight-panel class="p-4">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Overdue Checkout') }}</h4>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            @forelse ($overdueUsers as $overdue)
                                <div class="flex items-center justify-between gap-2 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 dark:border-rose-900/20 dark:bg-rose-900/10">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $overdue->user->name }}</p>
                                        <p class="text-[10px] text-slate-500">{{ __('Shift End') }}: {{ $overdue->shift->end_time }}</p>
                                    </div>
                                    <x-actions.button type="button" wire:click="notifyUser('{{ $overdue->id }}')"
                                        wire:loading.attr="disabled" variant="soft-danger" size="sm"
                                        label="{{ __('Send checkout reminder to') }} {{ $overdue->user->name }}">
                                        {{ __('Remind') }}
                                    </x-actions.button>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">{{ __('All clear!') }}</p>
                            @endforelse
                        </div>
                    </x-admin.insight-panel>

                    <x-admin.insight-panel class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Not Logged In') }}</h4>
                            <span class="text-xs font-bold text-slate-500">{{ $notLoggedInUsersCount }}</span>
                        </div>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            @forelse ($notLoggedInUsers->take(4) as $user)
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 dark:border-slate-700/50 dark:bg-slate-800/50">
                                    <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $user->name }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">{{ __('Everyone logged in.') }}</p>
                            @endforelse
                        </div>
                    </x-admin.insight-panel>
                </div>

                <!-- Schedule Watch & Exports -->
                <div class="space-y-4">
                    <x-admin.insight-panel class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Upcoming Leaves') }}</h4>
                            @if ($reportExportHref)
                                <x-actions.button href="{{ $reportExportHref }}" target="_system" variant="ghost" size="sm">
                                    {{ __('Export') }}
                                </x-actions.button>
                            @endif
                        </div>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @forelse ($calendarLeaves->take(4) as $leave)
                                <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 dark:border-slate-700/50 dark:bg-slate-800/50">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                        {{ \Carbon\Carbon::parse($leave['start_date'])->format('d') }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $leave['title'] }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $leave['date_display'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">{{ __('No upcoming leaves.') }}</p>
                            @endforelse
                        </div>
                    </x-admin.insight-panel>

                    <div wire:poll.5s class="pt-2">
                        <x-admin.import-export-run-list
                            :runs="$recentReportRuns"
                            :title="__('Monthly report jobs')"
                            :empty="__('No monthly report jobs.')"
                        />
                    </div>
                </div>
            </div>
        </x-admin.insight-panel>

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

                    @if ($employeesHref)
                        <x-actions.button href="{{ $employeesHref }}" variant="soft-primary" size="sm">
                            <x-heroicon-o-users class="h-4 w-4" />
                            {{ __('Open Employees') }}
                        </x-actions.button>
                    @endif
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
                class="mt-4 hidden rounded-2xl border border-slate-200/70 sm:block dark:border-slate-800">
                <table class="w-full divide-y divide-slate-200 dark:divide-slate-800">
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
            <div class="space-y-3 sm:hidden">
                @forelse ($detailList as $item)
                    <article class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ isset($item->user) ? $item->user->name : $item->name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('NIP') }}: {{ isset($item->user) ? $item->user->nip : $item->nip }}
                                </p>
                            </div>
                            @if ($selectedStatType !== 'absent')
                                <span
                                    class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->status === 'present'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                                        : ($item->status === 'late'
                                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300'
                                            : ($item->status === 'sick'
                                                ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300'
                                                : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300')) }}">
                                    {{ __(ucfirst($item->status)) }}
                                </span>
                            @endif
                        </div>

                        @if ($selectedStatType !== 'absent')
                            <p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                {{ __('Time') }}:
                                <span class="font-mono">
                                    {{ $item->time_in ? \App\Helpers::format_time($item->time_in) : '-' }}
                                    @if ($item->time_out)
                                        - {{ \App\Helpers::format_time($item->time_out) }}
                                    @endif
                                </span>
                            </p>
                        @endif
                    </article>
                @empty
                    <x-admin.empty-state :title="__('No data found.')" class="border border-dashed border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/50" />
                @endforelse
            </div>

            <div class="hidden sm:block">
                <table class="w-full divide-y divide-slate-200 dark:divide-slate-800">
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

        function attendanceMovementChart() {
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
                            chart.data.datasets[2].data = chartData.excused;
                            chart.data.datasets[3].data = chartData.sick;
                            chart.data.datasets[4].data = chartData.absent;
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
                                    label: '{{ __('Excused') }}',
                                    data: window.dashboardChartData.excused,
                                    borderColor: '#0ea5e9',
                                    backgroundColor: 'transparent',
                                    borderDash: [6, 6],
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 4,
                                },
                                {
                                    label: '{{ __('Sick') }}',
                                    data: window.dashboardChartData.sick,
                                    borderColor: '#8b5cf6',
                                    backgroundColor: 'transparent',
                                    borderDash: [3, 5],
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 4,
                                },
                                {
                                    label: '{{ __('No Record') }}',
                                    data: window.dashboardChartData.absent,
                                    borderColor: '#e11d48',
                                    backgroundColor: 'transparent',
                                    tension: 0.35,
                                    pointRadius: 1,
                                    pointHoverRadius: 3,
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

        function userAccessDonutChart() {
            let chart = null;

            return {
                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 100);
                        return;
                    }

                    const ctx = this.$refs.canvas;
                    if (!ctx) return;

                    chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['{{ __("Logged In") }}', '{{ __("Not Logged In") }}', '{{ __("Never Logged In") }}'],
                            datasets: [{
                                data: [{{ $loggedInUsersCount }}, {{ $notLoggedInUsersCount }}, {{ $neverLoggedInCount }}],
                                backgroundColor: ['#10b981', '#f59e0b', '#e11d48'],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: { display: false },
                            },
                        }
                    });
                }
            };
        }

        function pendingQueueChart() {
            let chart = null;

            return {
                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 100);
                        return;
                    }

                    const ctx = this.$refs.canvas;
                    if (!ctx) return;

                    chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['{{ __("Leave") }}', '{{ __("Correction") }}', '{{ __("Reimburse") }}', '{{ __("Overtime") }}', '{{ __("Kasbon") }}'],
                            datasets: [{
                                data: [{{ $pendingLeavesCount }}, {{ $pendingAttendanceCorrectionsCount }}, {{ $pendingReimbursementsCount }}, {{ $pendingOvertimesCount }}, {{ $pendingKasbonCount }}],
                                backgroundColor: ['#8b5cf6', '#0ea5e9', '#f59e0b', '#10b981', '#e11d48'],
                                borderRadius: 6,
                                barThickness: 18
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: {
                                legend: { display: false },
                            },
                            scales: {
                                x: { display: false },
                                y: {
                                    grid: { display: false },
                                    border: { display: false },
                                    ticks: { font: { size: 11, weight: 600 } }
                                }
                            }
                        }
                    });
                }
            };
        }

        function snapshotDonutChart() {
            let chart = null;

            return {
                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 100);
                        return;
                    }

                    const ctx = this.$refs.canvas;
                    if (!ctx) return;

                    chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: [
                                '{{ __('Present') }}',
                                '{{ __('Late') }}',
                                '{{ __('Excused') }}',
                                '{{ __('Sick') }}',
                                '{{ __('No Record') }}',
                            ],
                            datasets: [{
                                data: [
                                    {{ $presentCount }},
                                    {{ $lateCount }},
                                    {{ $excusedCount }},
                                    {{ $sickCount }},
                                    {{ $absentCount }},
                                ],
                                backgroundColor: ['#10b981', '#f59e0b', '#0ea5e9', '#8b5cf6', '#e11d48'],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    display: false
                                },
                            },
                        }
                    });
                }
            };
        }

        function attendanceMixChart() {
            let chart = null;

            const latest = (values) => Array.isArray(values) && values.length ? values[values.length - 1] : 0;
            const valuesFrom = (chartData) => [
                latest(chartData.present),
                latest(chartData.late),
                latest(chartData.excused),
                latest(chartData.sick),
                latest(chartData.absent),
            ];

            return {
                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 100);
                        return;
                    }

                    const ctx = this.$refs.canvas;
                    if (!ctx) return;

                    Livewire.on('chart-updated', (data) => {
                        const chartData = data[0] || data;

                        if (chart) {
                            chart.data.datasets[0].data = valuesFrom(chartData);
                            chart.update();
                        }
                    });

                    chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: [
                                '{{ __('Present') }}',
                                '{{ __('Late') }}',
                                '{{ __('Excused') }}',
                                '{{ __('Sick') }}',
                                '{{ __('No Record') }}',
                            ],
                            datasets: [{
                                data: valuesFrom(window.dashboardChartData),
                                backgroundColor: ['#16a34a', '#f59e0b', '#0ea5e9', '#8b5cf6', '#e11d48'],
                                borderWidth: 0,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                    }
                                },
                            },
                        }
                    });
                }
            };
        }
    </script>
</x-admin.page-shell>
