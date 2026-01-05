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
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                Absensi Hari Ini
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $date->format('l, d F Y') }}
            </p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 p-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
            </svg>
            <span class="font-medium text-blue-600 dark:text-blue-400">{{ $employeesCount }} Karyawan</span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-3 gap-2 sm:gap-4 sm:grid-cols-6">
        <!-- Hadir -->
        <div wire:click="showStatDetail('present')"
            class="group cursor-pointer rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 p-3 sm:p-4 border border-green-200 dark:border-green-700 hover:scale-[1.02] transition-transform">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-green-600 dark:text-green-400 mb-1">{{ ucfirst(__('present')) }}</p>
                    <p class="text-2xl sm:text-3xl font-bold text-green-700 dark:text-green-300">{{ $presentCount }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">Tepat Waktu</p>
                </div>
                <div class="rounded-lg bg-green-200 dark:bg-green-700 p-1.5 group-hover:bg-green-300 dark:group-hover:bg-green-600 transition-colors">
                    <svg class="w-3.5 h-3.5 text-green-700 dark:text-green-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Terlambat -->
        <div wire:click="showStatDetail('late')"
            class="group cursor-pointer rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/30 p-3 sm:p-4 border border-amber-200 dark:border-amber-700 hover:scale-[1.02] transition-transform">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-amber-600 dark:text-amber-400 mb-1">{{ ucfirst(__('late')) }}</p>
                    <p class="text-2xl sm:text-3xl font-bold text-amber-700 dark:text-amber-300">{{ $lateCount }}</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Datang Telat</p>
                </div>
                <div class="rounded-lg bg-amber-200 dark:bg-amber-700 p-1.5 group-hover:bg-amber-300 dark:group-hover:bg-amber-600 transition-colors">
                    <svg class="w-3.5 h-3.5 text-amber-700 dark:text-amber-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pulang Cepat -->
        <div wire:click="showStatDetail('early_checkout')"
            class="group cursor-pointer rounded-xl bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 p-3 sm:p-4 border border-orange-200 dark:border-orange-700 hover:scale-[1.02] transition-transform">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-orange-600 dark:text-orange-400 mb-1">Pulang Cepat</p>
                    <p class="text-2xl sm:text-3xl font-bold text-orange-700 dark:text-orange-300">{{ $earlyCheckoutCount }}</p>
                    <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">Sebelum Waktunya</p>
                </div>
                <div class="rounded-lg bg-orange-200 dark:bg-orange-700 p-1.5 group-hover:bg-orange-300 dark:group-hover:bg-orange-600 transition-colors">
                    <svg class="w-3.5 h-3.5 text-orange-700 dark:text-orange-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Izin -->
        <div wire:click="showStatDetail('excused')"
            class="group cursor-pointer rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-3 sm:p-4 border border-blue-200 dark:border-blue-700 hover:scale-[1.02] transition-transform">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-blue-600 dark:text-blue-400 mb-1">{{ ucfirst(__('excused')) }}</p>
                    <p class="text-2xl sm:text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $excusedCount }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Izin/Cuti</p>
                </div>
                <div class="rounded-lg bg-blue-200 dark:bg-blue-700 p-1.5 group-hover:bg-blue-300 dark:group-hover:bg-blue-600 transition-colors">
                    <svg class="w-3.5 h-3.5 text-blue-700 dark:text-blue-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Sakit -->
        <div wire:click="showStatDetail('sick')"
            class="group cursor-pointer rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 p-3 sm:p-4 border border-purple-200 dark:border-purple-700 hover:scale-[1.02] transition-transform">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-purple-600 dark:text-purple-400 mb-1">{{ ucfirst(__('sick')) }}</p>
                    <p class="text-2xl sm:text-3xl font-bold text-purple-700 dark:text-purple-300">{{ $sickCount }}
                    </p>
                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1 opacity-0">-</p>
                </div>
                <div class="rounded-lg bg-purple-200 dark:bg-purple-700 p-1.5 group-hover:bg-purple-300 dark:group-hover:bg-purple-600 transition-colors">
                    <svg class="w-3.5 h-3.5 text-purple-700 dark:text-purple-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Tidak Hadir -->
        <div wire:click="showStatDetail('absent')"
            class="group cursor-pointer rounded-xl bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 p-3 sm:p-4 border border-red-200 dark:border-red-700 hover:scale-[1.02] transition-transform">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-red-600 dark:text-red-400 mb-1">{{ ucfirst(__('absent')) }}</p>
                    <p class="text-2xl sm:text-3xl font-bold text-red-700 dark:text-red-300">{{ $absentCount }}</p>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">Belum Absen</p>
                </div>
                <div class="rounded-lg bg-red-200 dark:bg-red-700 p-1.5 group-hover:bg-red-300 dark:group-hover:bg-red-600 transition-colors">
                    <svg class="w-3.5 h-3.5 text-red-700 dark:text-red-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart, Logs, Map, Calendar Grid --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        {{-- Weekly Chart --}}
        <div class="rounded-lg border border-gray-200 bg-white p-3 shadow dark:border-gray-700 dark:bg-gray-800 flex flex-col"
             wire:ignore
             x-data="weeklyAttendanceChart()"
             x-init="initChart()">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white flex-none">{{ __('Weekly Attendance Trends') }}</h3>
            <div class="relative w-full flex-1 min-h-[300px]">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Recent Activity --}}
        {{-- Recent Activity --}}
        <div class="rounded-lg border border-gray-200 bg-white p-3 shadow dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Recent Activity') }}</h3>
                <a href="{{ route('admin.activity-logs.export') }}" target="_system" 
                   class="rounded bg-blue-700 px-2 py-1 text-[10px] font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    {{ __('Export') }}
                </a>
            </div>
            <div class="flow-root">
                <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentLogs as $log)
                    <li class="py-1.5 sm:py-2">
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-900 truncate dark:text-white">
                                    {{ $log->user->name ?? 'System' }}
                                    @if($log->count > 1)
                                        <span class="ml-1 inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ $log->count }}x
                                        </span>
                                    @endif
                                </p>
                                <p class="text-[10px] sm:text-xs text-gray-500 truncate dark:text-gray-400">
                                    {{ $log->description }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end text-[10px] text-gray-900 dark:text-white">
                                <span>{{ $log->updated_at->diffForHumans() }}</span>
                                @if($log->count > 1)
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ \App\Helpers::format_time($log->created_at) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Overdue Checkout List --}}
        <div class="rounded-lg border border-gray-200 bg-white p-3 shadow dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Belum Checkout (Overdue)') }}</h3>
            
            <div class="space-y-2">
                @forelse($overdueUsers as $overdue)
                    <div class="flex items-center justify-between p-2 rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                        <div>
                            <p class="text-xs font-medium text-gray-900 dark:text-white">{{ $overdue->user->name }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($overdue->date)->format('D, d M') }} • Shift End: {{ $overdue->shift->end_time }}
                            </p>
                        </div>
                        <button wire:click="notifyUser('{{ $overdue->id }}')"
                                wire:loading.attr="disabled"
                                class="rounded bg-red-600 px-2 py-1 text-[10px] font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            {{ __('Remind') }}
                        </button>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Tidak ada karyawan yang terlambat checkout.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Monthly Leave Calendar --}}
        <div class="rounded-lg border border-gray-200 bg-white p-3 shadow dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Leaves this Month') }}</h3>
                <a href="{{ route('admin.reports.export-pdf') }}" target="_system" 
                   class="rounded bg-green-700 px-2 py-1 text-[10px] font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                    {{ __('Export') }}
                </a>
            </div>
            <div class="space-y-2">
                @forelse($calendarLeaves as $leave)
                    <div class="flex items-center justify-between p-2 rounded-lg border {{ $leave['status'] == 'sick' ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' }}">
                        <div class="flex items-center gap-3">
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-gray-900 dark:text-white">{{ $leave['title'] }}</span>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($leave['start'])->format('d M Y') }}</span>
                            </div>
                        </div>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded {{ $leave['status'] == 'sick' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                            {{ ucfirst($leave['status']) }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-4">{{ __('No leaves recorded this month.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Employee Attendance Card -->
    <div class="mt-4 rounded-lg border border-gray-200 bg-white p-3 shadow dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Employee Attendance') }}</h3>
            {{-- Optional: Add filters or search here in future --}}
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
                            $statusColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                            break;
                        case 'late':
                            $statusLabel = ucfirst(__('late'));
                            $statusColor = 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300';
                            break;
                        case 'excused':
                            $statusLabel = ucfirst(__('excused'));
                            $statusColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
                            break;
                        case 'sick':
                            $statusLabel = ucfirst(__('sick'));
                            $statusColor = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                            break;
                        case 'absent':
                            $statusLabel = ucfirst(__('absent'));
                            $statusColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
                            break;
                        default:
                            $statusLabel = '-';
                            $statusColor = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                            break;
                    }
                @endphp
                <div class="rounded-lg border border-gray-100 dark:border-gray-700/50">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                            <p><span class="font-medium">NIP:</span> {{ $employee->nip }}</p>
                            <p><span class="font-medium">Divisi:</span> {{ $employee->division?->name ?? '-' }}</p>
                            <p><span class="font-medium">Jabatan:</span> {{ $employee->jobTitle?->name ?? '-' }}</p>
                            <p><span class="font-medium">Shift:</span> {{ $attendance->shift?->name ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-t border-gray-100 dark:border-gray-700 rounded-b-lg">
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                <div class="flex gap-4">
                                    <span>
                                        <span class="block text-gray-400 dark:text-gray-500">Masuk</span>
                                        <span class="font-medium">{{ $timeIn ?? '-' }}</span>
                                    </span>
                                    <span>
                                        <span class="block text-gray-400 dark:text-gray-500">Keluar</span>
                                        <span class="font-medium">{{ $timeOut ?? '-' }}</span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                 @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                                    <button type="button" 
                                        wire:click="show({{ $attendance->id }})"

                                        class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/50 dark:hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Detail
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Desktop Table View -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                            {{ __('Name') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                            {{ __('NIP') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                            {{ __('Division') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                            {{ __('Job Title') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                            {{ __('Shift') }}
                        </th>
                        <th scope="col"
                            class="text-nowrap border border-gray-300 px-1 py-3 text-center text-xs font-medium text-gray-500 dark:border-gray-600 dark:text-gray-300">
                            Status
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                            {{ __('Time In') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                            {{ __('Time Out') }}
                        </th>
                        <th scope="col" class="relative">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @php
                        $class = 'px-4 py-3 text-sm font-medium text-gray-900 dark:text-white';
                    @endphp
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
                                    $shortStatus = 'H';
                                    $bgColor =
                                        'bg-green-200 dark:bg-green-800 hover:bg-green-300 dark:hover:bg-green-700 border border-green-300 dark:border-green-600';
                                    break;
                                case 'late':
                                    $shortStatus = 'T';
                                    $bgColor =
                                        'bg-amber-200 dark:bg-amber-800 hover:bg-amber-300 dark:hover:bg-amber-700 border border-amber-300 dark:border-amber-600';
                                    break;
                                case 'excused':
                                    $shortStatus = 'I';
                                    $bgColor =
                                        'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700 border border-blue-300 dark:border-blue-600';
                                    break;
                                case 'sick':
                                    $shortStatus = 'S';
                                    $bgColor =
                                        'hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600';
                                    break;
                                case 'absent':
                                    $shortStatus = 'A';
                                    $bgColor =
                                        'bg-red-200 dark:bg-red-800 hover:bg-red-300 dark:hover:bg-red-700 border border-red-300 dark:border-red-600';
                                    break;
                                default:
                                    $shortStatus = '-';
                                    $bgColor =
                                        'hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600';
                                    break;
                            }
                        @endphp
                        <tr wire:key="{{ $employee->id }}" class="group">
                            {{-- Detail karyawan --}}
                            <td
                                class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                                {{ $employee->name }}
                            </td>
                            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                                {{ $employee->nip }}
                            </td>
                            <td
                                class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                                {{ $employee->division?->name ?? '-' }}
                            </td>
                            <td
                                class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                                {{ $employee->jobTitle?->name ?? '-' }}
                            </td>
                            <td
                                class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                                {{ $attendance->shift?->name ?? '-' }}
                            </td>

                            {{-- Absensi --}}
                            <td
                                class="{{ $bgColor }} text-nowrap px-1 py-3 text-center text-sm font-medium text-gray-900 dark:text-white">
                                {{ __($status) }}
                            </td>

                            {{-- Waktu masuk/keluar --}}
                            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                                {{ $timeIn ?? '-' }}
                            </td>
                            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                                {{ $timeOut ?? '-' }}
                            </td>

                            {{-- Action --}}
                            <td
                                class="cursor-pointer text-center text-sm font-medium text-gray-900 group-hover:bg-gray-100 dark:text-white dark:group-hover:bg-gray-700">
                                <div class="flex items-center justify-center gap-3">
                                    @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                                        <button type="button" 
                                            wire:click="show({{ $attendance->id }})"
                                            class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/50 dark:hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            {{ __('Detail') }}
                                        </button>
                                    @else
                                        -
                                    @endif
                                </div>
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
                {{ str_replace('_', ' ', $selectedStatType) == 'absent' ? 'Belum Absen' : ucfirst(str_replace('_', ' ', $selectedStatType)) }}
            </span>
        </x-slot>

        <x-slot name="content">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NIP</th>
                            @if($selectedStatType !== 'absent')
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
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
                                        {{ ucfirst($item->status) }}
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
                                No data found.
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
            return {
                chart: null,
                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 100);
                        return;
                    }
                    const ctx = this.$refs.canvas;
                    if (!ctx) return;
                    
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: window.dashboardChartData.labels,
                            datasets: [
                                {
                                    label: 'Hadir',
                                    data: window.dashboardChartData.present,
                                    backgroundColor: '#22c55e',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Terlambat',
                                    data: window.dashboardChartData.late,
                                    backgroundColor: '#eab308',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Izin/Sakit',
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
