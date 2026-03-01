@php
$date = Carbon\Carbon::now();
@endphp
<div class="mx-auto max-w-7xl px-2 sm:px-2 lg:px-2 py-2">
    @pushOnce('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpushOnce
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                    {{ __("Today's Attendance") }}
                </h3>
                @if(isset($activeHolidaysCount) && $activeHolidaysCount > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-900/30 dark:text-red-400">
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.08 7.373cV7h1.84v.373c.062.666.24 1.83.676 2.723A8.528 8.528 0 0013 11.5v1H7v-1a8.528 8.528 0 001.404-1.404c.436-.893.614-2.057.676-2.723z" clip-rule="evenodd" />
                    </svg>
                    {{ __('Holiday Today') }}
                </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $date->translatedFormat('l, d F Y') }}
            </p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 p-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
            </svg>
            <span class="font-medium text-blue-600 dark:text-blue-400">{{ $employeesCount }} {{ __('Employees') }}</span>
        </div>
    </div>

    <!-- Talenta-Style Summary Cards -->
    <div wire:poll.15s class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <!-- 1. Staff Overview -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Employees') }}</dt>
            <dd class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $employeesCount }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Active') }}</span>
            </dd>
            <div class="mt-4 flex flex-col gap-2">
                <div class="flex items-center gap-x-2 text-sm text-green-600 dark:text-green-400">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" />
                    </svg>
                    <div class="cursor-pointer hover:underline" wire:click="showStatDetail('present')">
                        <span class="font-medium">{{ $presentCount }} {{ __('Present Today') }}</span>
                    </div>
                </div>

                @if(isset($missingFaceDataCount) && $missingFaceDataCount > 0)
                <div class="flex items-center gap-x-2 text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-2 py-1.5 rounded-md mt-1 border border-amber-200/50 dark:border-amber-800/50">
                    <svg class="h-4 w-4 flex-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><strong>{{ $missingFaceDataCount }}</strong> {{ __('Users missing Face ID') }}</span>
                </div>
                @endif
            </div>

            <!-- Quick Link to Employees -->
            <!-- Removed absolute link effectively to allow clicking on details -->
        </div>

        <!-- 2. Action Center (Pending Tasks) -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 shadow-sm ring-1 ring-blue-900/5 transition-all hover:shadow-md dark:from-blue-900/20 dark:to-indigo-900/20 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <dt class="truncate text-sm font-medium text-blue-600 dark:text-blue-300">
                    {{ (auth()->user()->is_admin || auth()->user()->is_superadmin) ? __('Action Needed') : __('My Team Requests') }}
                </dt>
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                    {{ $pendingLeavesCount + $pendingReimbursementsCount + ($pendingOvertimesCount ?? 0) + ($pendingKasbonCount ?? 0) }} {{ __('Pending') }}
                </span>
            </div>
            <div class="mt-4 space-y-2">
                <!-- Pending Leaves -->
                <a href="{{ route('admin.leaves') }}" class="flex items-center justify-between rounded-lg bg-white/60 px-3 py-2 text-sm transition-colors hover:bg-white dark:bg-gray-800/40 dark:hover:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full {{ $pendingLeavesCount > 0 ? 'bg-amber-500 animate-pulse' : 'bg-gray-300' }}"></div>
                        <span class="text-gray-700 dark:text-gray-200">{{ __('Leave Requests') }}</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $pendingLeavesCount }}</span>
                </a>
                <!-- Pending Reimbursements -->
                <a href="{{ route('admin.reimbursements') }}" class="flex items-center justify-between rounded-lg bg-white/60 px-3 py-2 text-sm transition-colors hover:bg-white dark:bg-gray-800/40 dark:hover:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full {{ $pendingReimbursementsCount > 0 ? 'bg-amber-500 animate-pulse' : 'bg-gray-300' }}"></div>
                        <span class="text-gray-700 dark:text-gray-200">{{ __('Reimbursements') }}</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $pendingReimbursementsCount }}</span>
                </a>
                <!-- Pending Overtimes -->
                <a href="{{ route('admin.overtime') }}" class="flex items-center justify-between rounded-lg bg-white/60 px-3 py-2 text-sm transition-colors hover:bg-white dark:bg-gray-800/40 dark:hover:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full {{ ($pendingOvertimesCount ?? 0) > 0 ? 'bg-amber-500 animate-pulse' : 'bg-gray-300' }}"></div>
                        <span class="text-gray-700 dark:text-gray-200">{{ __('Overtimes') }}</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $pendingOvertimesCount ?? 0 }}</span>
                </a>
                <!-- Pending Kasbon -->
                <a href="{{ route('admin.manage-kasbon') }}" class="flex items-center justify-between rounded-lg bg-white/60 px-3 py-2 text-sm transition-colors hover:bg-white dark:bg-gray-800/40 dark:hover:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full {{ ($pendingKasbonCount ?? 0) > 0 ? 'bg-amber-500 animate-pulse' : 'bg-gray-300' }}"></div>
                        <span class="text-gray-700 dark:text-gray-200">{{ __('Cash Advances') }}</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $pendingKasbonCount ?? 0 }}</span>
                </a>
            </div>
        </div>

        <!-- 3. Attendance Health (Chart) -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Attendance Health') }}</dt>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <!-- Present -->
                <button wire:click="showStatDetail('present')" class="flex flex-col text-left rounded-xl bg-green-50 p-3 hover:bg-green-100 transition-colors dark:bg-green-900/20 dark:hover:bg-green-900/40">
                    <span class="text-xs font-medium text-green-600 dark:text-green-400">{{ __('Present') }}</span>
                    <span class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $presentCount }}</span>
                </button>
                <!-- Late -->
                <button wire:click="showStatDetail('late')" class="flex flex-col text-left rounded-xl bg-amber-50 p-3 hover:bg-amber-100 transition-colors dark:bg-amber-900/20 dark:hover:bg-amber-900/40">
                    <span class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ __('Late') }}</span>
                    <span class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $lateCount }}</span>
                </button>
                <!-- Absent/Sick -->
                <button wire:click="showStatDetail('absent')" class="col-span-2 flex items-center justify-between text-left rounded-xl bg-gray-50 px-3 py-2 hover:bg-gray-100 transition-colors dark:bg-gray-700/30 dark:hover:bg-gray-700/50">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Absent / Sick / Leave') }}</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $absentCount + $sickCount + $excusedCount }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Chart, Logs, Map, Calendar Grid --}}
    <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Weekly Chart (Spans 2 columns) --}}
        <div class="col-span-1 lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 flex flex-col"
            wire:ignore
            x-data="weeklyAttendanceChart()"
            x-init="initChart()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Attendance Trends') }}</h3>
                <select wire:model.live="chartFilter" class="text-xs border-0 bg-gray-50 rounded-lg text-gray-500 focus:ring-0 dark:bg-gray-700 dark:text-gray-400">
                    <option value="week">{{ __('Last 7 Days') }}</option>
                    <option value="month">{{ __('Last 30 Days') }}</option>
                </select>
            </div>
            <div class="relative w-full flex-1 min-h-[300px]">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Live Feed / Recent Activity (1 column) --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10" wire:poll.10s>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Live Feed') }}</h3>
                <a href="{{ route('admin.activity-logs') }}"
                    class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                    {{ __('View All') }}
                </a>
            </div>

            <div class="relative pl-4 border-l border-gray-200 dark:border-gray-700 space-y-6">
                @foreach($recentLogs->take(5) as $log)
                <div class="relative group">
                    <div class="absolute -left-[21px] mt-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-gray-300 dark:border-gray-800 dark:bg-gray-600 group-hover:bg-blue-500 transition-colors"></div>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $log->user->name ?? __('System') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __($log->description) }}
                        </span>
                        <span class="mt-1 text-[10px] text-gray-400">
                            {{ $log->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Bottom Section: Overdue & Leaves --}}
    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Overdue Checkout --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-red-500"></div>
                {{ __('Overdue Checkout') }}
            </h3>

            <div class="space-y-3">
                @forelse($overdueUsers as $overdue)
                <div class="flex items-center justify-between p-3 rounded-xl bg-red-50/50 border border-red-100 dark:bg-red-900/10 dark:border-red-900/20">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center text-red-600 text-xs font-bold dark:bg-red-900/30 dark:text-red-400">
                            {{ substr($overdue->user->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $overdue->user->name }}</p>
                            <p class="text-xs text-red-600 dark:text-red-400">
                                {{ __('Shift End') }}: {{ $overdue->shift->end_time }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="notifyUser('{{ $overdue->id }}')"
                        wire:loading.attr="disabled"
                        class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                        {{ __('Remind') }}
                    </button>
                </div>
                @empty
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('All clear! No overdue checkouts.') }}</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Monthly Leave Calendar --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Upcoming Leaves') }}</h3>
                <a href="{{ route('admin.reports.export-pdf') }}" target="_system"
                    @if(\App\Helpers\Editions::reportingLocked())
                    @click.prevent="$dispatch('feature-lock', { title: 'Export Locked', message: 'Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.' })"
                    @endif
                    class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                    {{ __('Export') }}
                    @if(\App\Helpers\Editions::reportingLocked()) 🔒 @endif
                </a>
            </div>
            <div class="space-y-3">
                @forelse($calendarLeaves->take(5) as $leave)
                <div class="flex items-center gap-4 p-3 rounded-xl border border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <div class="flex-none flex flex-col items-center justify-center h-12 w-12 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        <span class="text-xs font-bold">{{ \Carbon\Carbon::parse($leave['start_date'])->format('d') }}</span>
                        <span class="text-[10px] uppercase">{{ \Carbon\Carbon::parse($leave['start_date'])->translatedFormat('M') }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate dark:text-white">
                            {{ $leave['title'] }}
                        </p>
                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $leave['status'] == 'sick' ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                            {{ __(ucfirst($leave['status'])) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No leaves schedule for this month.') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Employee Attendance Card -->
    <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Employee Attendance') }}</h3>
            <div class="flex gap-2">
                {{-- Search Input (Visual Only for now, or wire:model if we add a search property) --}}
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search...') }}"
                        class="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="space-y-4 sm:hidden">
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
            $statusLabel = ucfirst(__('present'));
            $statusColor = 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400';
            break;
            case 'late':
            $statusLabel = ucfirst(__('late'));
            $statusColor = 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-400';
            break;
            case 'excused':
            $statusLabel = ucfirst(__('excused'));
            $statusColor = 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400';
            break;
            case 'sick':
            $statusLabel = ucfirst(__('sick'));
            $statusColor = 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-400';
            break;
            case 'absent':
            $statusLabel = ucfirst(__('absent'));
            $statusColor = 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400';
            break;
            default:
            $statusLabel = '-';
            $statusColor = 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400';
            break;
            }
            @endphp
            <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-sm dark:bg-gray-700 dark:text-gray-300">
                            {{ substr($employee->name, 0, 1) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $employee->jobTitle?->name ?? __('Staff') }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                        {{ $statusLabel }}
                        @if($attendance && $attendance->is_suspicious)
                        <span title="{{ $attendance->suspicious_reason }}" class="cursor-help text-red-500">⚠️</span>
                        @endif
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Time In') }}</span>
                        <span class="font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $timeIn ?? '--:--' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Time Out') }}</span>
                        <span class="font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $timeOut ?? '--:--' }}</span>
                    </div>
                </div>

                @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button type="button"
                        wire:click="show({{ $attendance->id }})"
                        class="w-full inline-flex justify-center items-center px-2 py-1.5 text-xs font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 transition-colors">
                        {{ __('View Details') }}
                    </button>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Desktop Table View -->
        <div class="hidden sm:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Employee') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Shift') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Time In') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Time Out') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
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
                    $statusLabel = 'Present';
                    $statusDot = 'bg-green-500';
                    $statusBg = 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400';
                    break;
                    case 'late':
                    $statusLabel = 'Late';
                    $statusDot = 'bg-amber-500';
                    $statusBg = 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-400';
                    break;
                    case 'excused':
                    $statusLabel = 'Excused';
                    $statusDot = 'bg-blue-500';
                    $statusBg = 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400';
                    break;
                    case 'sick':
                    $statusLabel = 'Sick';
                    $statusDot = 'bg-purple-500';
                    $statusBg = 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-400';
                    break;
                    case 'absent':
                    $statusLabel = 'Absent';
                    $statusDot = 'bg-red-500';
                    $statusBg = 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400';
                    break;
                    default:
                    $statusLabel = '-';
                    $statusDot = 'bg-gray-400';
                    $statusBg = 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400';
                    break;
                    }
                    @endphp
                    <tr wire:key="{{ $employee->id }}" class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ substr($employee->name, 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $employee->jobTitle?->name ?? __('Staff') }} • {{ $employee->division?->name ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $attendance->shift?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusBg }}">
                                <span class="mr-1.5 h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                {{ __($statusLabel) }}
                                @if($attendance && $attendance->is_suspicious)
                                <span title="{{ $attendance->suspicious_reason }}" class="cursor-help text-red-500 ml-1">⚠️</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600 dark:text-gray-300">
                            {{ $timeIn ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600 dark:text-gray-300">
                            {{ $timeOut ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                            <button wire:click="show({{ $attendance->id }})" class="text-gray-400 hover:text-primary-600 transition-colors" title="{{ __('Detail') }}">
                                <x-heroicon-m-eye class="h-5 w-5" />
                            </button>
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
    </div>

    <x-attendance-detail-modal :current-attendance="$currentAttendance" />

    <!-- Stat Detail Modal -->
    <x-dialog-modal wire:model="showStatModal" maxWidth="2xl">
        <x-slot name="title">
            {{ __('Detail List') }}:
            <span class="capitalize">
                {{ str_replace('_', ' ', $selectedStatType) == 'absent' ? __('Not Present') : __(ucfirst(str_replace('_', ' ', $selectedStatType))) }}
            </span>
        </x-slot>

        <x-slot name="content">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('NIP') }}</th>
                            @if($selectedStatType !== 'absent')
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Time') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($detailList as $item)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ isset($item->user) ? $item->user->name : $item->name }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ isset($item->user) ? $item->user->nip : $item->nip }}
                            </td>
                            @if($selectedStatType !== 'absent')
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $item->status === 'present' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                           ($item->status === 'late' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 
                                           ($item->status === 'sick' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 
                                           'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300')) }}">
                                    {{ __(ucfirst($item->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $item->time_in ? \App\Helpers::format_time($item->time_in) : '-' }}
                                @if($item->time_out)
                                - {{ \App\Helpers::format_time($item->time_out) }}
                                @endif
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $selectedStatType !== 'absent' ? 4 : 2 }}" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No data found.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeStatModal" wire:loading.attr="disabled" class="!px-2 !py-1">
                {{ __('Close') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
    @stack('attendance-detail-scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Define data globally
        window.dashboardChartData = @json($chartData);

        function weeklyAttendanceChart() {
            let chart = null;

            return {
                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 100);
                        return;
                    }
                    const ctx = this.$refs.canvas;
                    if (!ctx) return;

                    if (chart) {
                        chart.destroy();
                    }

                    // Watch for Livewire Event
                    Livewire.on('chart-updated', (data) => {
                        // Parse the data correctly since it comes as array
                        const chartData = data[0];

                        if (chart) {
                            chart.data.labels = chartData.labels;
                            chart.data.datasets[0].data = chartData.present;
                            chart.data.datasets[1].data = chartData.late;
                            chart.data.datasets[2].data = chartData.other;
                            chart.update();
                        }
                    });

                    chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: window.dashboardChartData.labels,
                            datasets: [{
                                    label: '{{ __("Present") }}',
                                    data: window.dashboardChartData.present,
                                    backgroundColor: '#22c55e',
                                    borderRadius: 4
                                },
                                {
                                    label: '{{ __("Late") }}',
                                    data: window.dashboardChartData.late,
                                    backgroundColor: '#eab308',
                                    borderRadius: 4
                                },
                                {
                                    label: '{{ __("Excused") }}/{{ __("Sick") }}',
                                    data: window.dashboardChartData.other,
                                    backgroundColor: '#3b82f6',
                                    borderRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        display: false
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            };
        }
    </script>
</div>