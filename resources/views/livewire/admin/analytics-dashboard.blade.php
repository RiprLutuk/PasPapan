@php
    $selectedPeriod = \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->translatedFormat('F Y');
    $presentTotal = $metrics['present'] ?? 0;
    $lateTotal = $metrics['late'] ?? 0;
    $sickTotal = $metrics['sick'] ?? 0;
    $excusedTotal = $metrics['excused'] ?? 0;
    $alphaTotal = ($metrics['alpha'] ?? 0) + ($metrics['absent'] ?? 0);
    $attendanceMixTotal = max($presentTotal + $lateTotal + $sickTotal + $excusedTotal + $alphaTotal, 1);
    $topRegions = collect($regionDistribution)
        ->countBy(fn($item) => $item['region'] ?? __('Unknown'))
        ->sortDesc()
        ->take(5);
    $divisionLeaders = collect($divisionStats['labels'] ?? [])
        ->values()
        ->map(
            fn($label, $index) => [
                'label' => $label,
                'value' => $divisionStats['data'][$index] ?? 0,
            ],
        )
        ->sortByDesc('value')
        ->take(5)
        ->values();
    $genderBreakdown = collect([
        ['label' => __('Male'), 'value' => $genderDemographics['male'] ?? 0],
        ['label' => __('Female'), 'value' => $genderDemographics['female'] ?? 0],
    ])
        ->filter(fn($item) => $item['value'] > 0)
        ->values();
    $genderTotal = max($genderBreakdown->sum('value'), 1);
    $summaryCards = [
        [
            'label' => __('Total Workforce'),
            'value' => $summary['total_employees'],
            'hint' => __('Active employees in the organization'),
            'tone' => 'primary',
        ],
        [
            'label' => __('Attendance Rate'),
            'value' => $summary['attendance_rate'] . '%',
            'hint' => __('Presence coverage for the selected period'),
            'tone' => 'emerald',
        ],
        [
            'label' => __('Late Occurrence'),
            'value' => $summary['late_rate'] . '%',
            'hint' => __('Share of late arrivals from recorded presence'),
            'tone' => 'amber',
        ],
        [
            'label' => __('Avg Daily Presence'),
            'value' => $summary['avg_daily_attendance'],
            'hint' => __('Average people present per workday'),
            'tone' => 'teal',
        ],
        [
            'label' => __('Est. Basic Payroll'),
            'value' => 'Rp ' . number_format($estimatedPayroll, 0, ',', '.'),
            'hint' => __('Projected from active employee salary data'),
            'tone' => 'slate',
        ],
    ];
    $analyticsPayload = [
        'trend' => $trend,
        'metrics' => $metrics,
        'division' => $divisionStats,
        'late' => $lateBuckets,
        'absent' => $absentStats,
        'regionDistribution' => $regionDistribution,
        'gender' => $genderDemographics,
        'headcount' => $headcountStats,
    ];
@endphp

<x-admin.page-shell :title="__('Analytics Dashboard')" :description="__('Comprehensive overview of workforce performance.')" data-analytics-charts-root x-data="analyticsChartsComponent"
    x-init="boot()" x-on:chart-update.window="updateCharts($event.detail)"
    x-on:hris-update.window="updateHrisCharts($event.detail)">
    <x-slot name="actions">
        <span
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            <x-heroicon-o-banknotes class="h-4 w-4" />
            {{ __('Work Standard') }}: {{ $workHoursPerDay }} {{ __('Hours / Day') }}
        </span>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools :title="__('Filter Analytics Period')" :description="__(
            'Use month and year filters to compare attendance performance, workforce mix, and operational risk over time.',
        )"
            grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-slot name="actions">
                <div wire:loading role="status" aria-live="polite" class="flex items-center px-1 text-primary-600">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="sr-only">{{ __('Loading analytics') }}</span>
                </div>

                <form method="GET" action="{{ route('admin.analytics') }}" class="w-full sm:w-[22rem]">
                    <div class="grid grid-cols-[minmax(10rem,1fr)_7rem] gap-2">
                        <x-forms.tom-select
                            id="analytics-month"
                            name="month"
                            :selected="$month"
                            :submit-on-change="true"
                            placeholder="{{ __('Month') }}"
                            :options="collect(range(1, 12))->map(
                                fn($m) => [
                                    'id' => $m,
                                    'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
                                ],
                            )"
                        />

                        <x-forms.tom-select
                            id="analytics-year"
                            name="year"
                            :selected="$year"
                            :submit-on-change="true"
                            placeholder="{{ __('Year') }}"
                            :options="collect(range(date('Y') - 1, date('Y')))->map(fn($y) => ['id' => $y, 'name' => $y])"
                        />
                    </div>
                </form>
            </x-slot>
        </x-admin.page-tools>
    </x-slot>

    <div class="space-y-4">
        <!-- Finance & HR Banner -->
        <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-slate-200 dark:divide-slate-800 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            @foreach ($summaryCards as $card)
                <div class="p-5 flex flex-col justify-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300">
                        {{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <!-- Attendance Trend & Mix -->
        <div class="grid gap-4 xl:grid-cols-[minmax(0,2.2fr)_minmax(260px,1fr)] items-start">
            <x-admin.insight-panel class="flex flex-col overflow-hidden p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Attendance Trend') }}</h3>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $selectedPeriod }}</span>
                </div>
                <div class="h-[220px] w-full">
                    <canvas x-ref="trendChart" class="!h-full !w-full" role="img" aria-label="{{ __('Attendance trend line chart') }}"></canvas>
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Attendance Mix') }}</h3>
                    <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $attendanceMixTotal }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ([['label' => __('Present'), 'value' => $presentTotal, 'bar' => 'bg-primary-500'], ['label' => __('Late'), 'value' => $lateTotal, 'bar' => 'bg-amber-500'], ['label' => __('Leave'), 'value' => $sickTotal + $excusedTotal, 'bar' => 'bg-sky-500'], ['label' => __('Alpha'), 'value' => $alphaTotal, 'bar' => 'bg-rose-500']] as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $row['label'] }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $row['value'] }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="progressbar" aria-valuenow="{{ $row['value'] }}" aria-valuemax="{{ $attendanceMixTotal }}" aria-label="{{ $row['label'] }}">
                                <div class="h-full rounded-full {{ $row['bar'] }}" style="width: {{ round(($row['value'] / $attendanceMixTotal) * 100, 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.insight-panel>
        </div>

        <!-- Map & Headcount -->
        <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <x-admin.insight-panel class="flex flex-col overflow-hidden p-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Geographical Distribution') }}</h3>
                <div class="flex-1 min-h-[320px] w-full">
                    <div id="employeeOriginsMap" x-ref="employeeOriginsMap" wire:ignore class="h-full w-full rounded-xl border border-slate-200 dark:border-slate-800 z-0"></div>
                </div>
            </x-admin.insight-panel>

            <div class="grid gap-4">
                <x-admin.insight-panel class="p-5">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Headcount Distribution') }}</h3>
                    <div class="h-[200px]"><canvas x-ref="headcountChart" role="img" aria-label="{{ __('Headcount distribution chart') }}"></canvas></div>
                </x-admin.insight-panel>
                <x-admin.insight-panel class="p-5">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Top Performing Divisions') }}</h3>
                    <div class="space-y-2">
                        @forelse ($divisionLeaders as $index => $division)
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">{{ $index + 1 }}</span>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $division['label'] }}</span>
                                </div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $division['value'] }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">{{ __('No data') }}</p>
                        @endforelse
                    </div>
                </x-admin.insight-panel>
            </div>
        </div>

        <!-- Micro Charts -->
        <div class="grid gap-4 grid-cols-2 xl:grid-cols-5">
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Division Performance') }}</h3>
                <div class="h-48"><canvas x-ref="divisionChart" role="img" aria-label="{{ __('Division performance chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Status Distribution') }}</h3>
                <div class="h-48"><canvas x-ref="statusChart" role="img" aria-label="{{ __('Status distribution chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Late Analysis') }}</h3>
                <div class="h-48"><canvas x-ref="lateChart" role="img" aria-label="{{ __('Late analysis chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Gender Split') }}</h3>
                <div class="h-48"><canvas x-ref="genderChart" role="img" aria-label="{{ __('Gender split chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Absence Reasons') }}</h3>
                <div class="h-48"><canvas x-ref="absentChart" role="img" aria-label="{{ __('Absence reasons chart') }}"></canvas></div>
            </x-admin.insight-panel>
        </div>

        <!-- Wall of Fame -->
        <div class="grid gap-4 md:grid-cols-3">
            <x-admin.insight-panel class="p-5 border-emerald-200/50 bg-gradient-to-b from-white to-emerald-50/30 dark:border-emerald-900/30 dark:from-slate-900 dark:to-emerald-900/10">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-s-star class="h-5 w-5 text-emerald-500" />
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Early Birds') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($topDiligent as $employee)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700/50">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $employee->name }}</span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ gmdate('H:i', $employee->avg_check_in) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('No data') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="p-5 border-amber-200/50 bg-gradient-to-b from-white to-amber-50/30 dark:border-amber-900/30 dark:from-slate-900 dark:to-amber-900/10">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-amber-500" />
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Frequent Late') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($topLate as $employee)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700/50">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $employee->name }}</span>
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400">{{ $employee->late_count }}x</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('Everyone on time') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="p-5 border-rose-200/50 bg-gradient-to-b from-white to-rose-50/30 dark:border-rose-900/30 dark:from-slate-900 dark:to-rose-900/10">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-s-arrow-right-end-on-rectangle class="h-5 w-5 text-rose-500" />
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Early Runners') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($topEarlyLeavers as $employee)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700/50">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $employee->name }}</span>
                            <span class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ $employee->early_leave_count }}x</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('Full attendance') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>
        </div>

        <!-- Extended Analytics Row -->
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Top Regions -->
            <x-admin.insight-panel class="p-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Top Employee Regions') }}</h3>
                <div class="space-y-2.5">
                    @forelse ($topRegions as $region => $count)
                        @php $regionPct = round(($count / max($topRegions->sum(), 1)) * 100, 1); @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $region }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $count }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-teal-500" style="width: {{ $regionPct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('No region data') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <!-- Attendance Rate Gauge -->
            <x-admin.insight-panel class="p-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Attendance Rate') }}</h3>
                <div class="flex items-center gap-4">
                    <div class="relative flex-shrink-0">
                        <svg viewBox="0 0 120 120" class="w-24 h-24">
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-slate-100 dark:stroke-slate-800" />
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" stroke-linecap="round"
                                class="stroke-emerald-500"
                                stroke-dasharray="{{ 2 * 3.14159 * 52 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 52 * (1 - ($summary['attendance_rate'] ?? 0) / 100) }}"
                                transform="rotate(-90 60 60)" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-slate-900 dark:text-white">{{ $summary['attendance_rate'] ?? 0 }}%</span>
                        </div>
                    </div>
                    <div class="flex-1 space-y-2 text-xs">
                        <div class="flex justify-between rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400">
                            <span class="font-medium">{{ __('Avg Daily') }}</span>
                            <span class="font-bold">{{ $summary['avg_daily_attendance'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-amber-50 p-2 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400">
                            <span class="font-medium">{{ __('Late Rate') }}</span>
                            <span class="font-bold">{{ $summary['late_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-slate-50 p-2 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300">
                            <span class="font-medium">{{ __('Workforce') }}</span>
                            <span class="font-bold">{{ $summary['total_employees'] }}</span>
                        </div>
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>
    </div>

    @push('scripts')
        <script>
            window.analyticsChartsPayload = @js($analyticsPayload);

            window.initAnalyticsCharts = (initialData) => ({
                data: initialData,
                charts: {},

                translate(key) {
                    const dict = {
                        'present': '{{ __('Present') }}',
                        'late': '{{ __('Late') }}',
                        'sick': '{{ __('Sick') }}',
                        'excused': '{{ __('Excused') }}',
                        'absent': '{{ __('Absent') }}',
                        'alpha': '{{ __('Alpha') }}',
                        'male': '{{ __('Male') }}',
                        'female': '{{ __('Female') }}'
                    };
                    return dict[key.toLowerCase()] || (key.charAt(0).toUpperCase() + key.slice(1));
                },

                init() {
                    this.$nextTick(() => {
                        this.renderCharts();
                    });
                    this.registerMapCleanup();
                },

                normalizePayload(payload) {
                    return Array.isArray(payload) ? (payload[0] || {}) : (payload || {});
                },

                updateCharts(newData) {
                    const payload = this.normalizePayload(newData);

                    this.data.trend = payload.trend;
                    this.data.metrics = payload.metrics;
                    this.data.division = payload.divisionStats;
                    this.data.late = payload.lateBuckets;
                    this.data.absent = payload.absentStats;
                    this.data.regionDistribution = payload.regionDistribution;

                    this.$nextTick(() => this.renderCharts());
                },

                updateHrisCharts(newData) {
                    const payload = this.normalizePayload(newData);

                    this.data.gender = payload.genderDemographics;
                    this.data.headcount = payload.headcountStats;
                    this.$nextTick(() => this.renderCharts());
                },

                registerMapCleanup() {
                    if (this.mapCleanupRegistered) return;

                    this.mapCleanupHandler = () => this.destroyEmployeeOriginsMap();
                    document.addEventListener('livewire:navigating', this.mapCleanupHandler);
                    window.addEventListener('pagehide', this.mapCleanupHandler);
                    this.mapCleanupRegistered = true;
                },

                destroyEmployeeOriginsMap() {
                    const mapElement = this.$refs.employeeOriginsMap || document.getElementById('employeeOriginsMap');
                    const state = mapElement?._analyticsLeaflet;

                    if (!state) return;

                    state.resizeObserver?.disconnect();
                    state.markersLayer?.clearLayers();
                    state.map?.remove();
                    delete mapElement._analyticsLeaflet;
                },

                renderCharts() {
                    if (typeof Chart === 'undefined') {
                        if (this.retryCount === undefined) this.retryCount = 0;
                        if (this.retryCount < 20) {
                            this.retryCount++;
                            setTimeout(() => this.renderCharts(), 100);
                        } else {
                            console.error('Chart.js is not available. Analytics charts cannot be rendered.');
                        }
                        return;
                    }

                    this.renderTrendChart();
                    this.renderDivisionChart();
                    this.renderStatusChart();
                    this.renderLateChart();
                    this.renderGenderChart();
                    this.renderHeadcountChart();
                    this.renderEmployeeOriginsMap();
                    this.renderAbsentChart();
                },

                renderTrendChart() {
                    const ctx = this.$refs.trendChart;
                    if (!ctx) return;

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const presentGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 320);
                    presentGradient.addColorStop(0, 'rgba(22, 163, 74, 0.2)');
                    presentGradient.addColorStop(1, 'rgba(22, 163, 74, 0)');

                    this.charts.trend = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.data.trend.labels || [],
                            datasets: [{
                                    label: this.translate('present'),
                                    data: this.data.trend.present || [],
                                    borderColor: '#16a34a',
                                    backgroundColor: presentGradient,
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2
                                },
                                {
                                    label: this.translate('late'),
                                    data: this.data.trend.late || [],
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'transparent',
                                    tension: 0.35,
                                    pointRadius: 2
                                },
                                {
                                    label: this.translate('absent'),
                                    data: this.data.trend.absent || [],
                                    borderColor: '#ef4444',
                                    backgroundColor: 'transparent',
                                    borderDash: [6, 6],
                                    tension: 0.35,
                                    pointRadius: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 6,
                                    left: 0,
                                    right: 0,
                                    bottom: 0
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#e2e8f0'
                                    }
                                }
                            }
                        }
                    });
                },

                renderDivisionChart() {
                    const ctx = this.$refs.divisionChart;
                    if (!ctx) return;

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    this.charts.division = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.data.division.labels || [],
                            datasets: [{
                                label: '{{ __('Present') }}',
                                data: this.data.division.data || [],
                                backgroundColor: '#16a34a',
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#e2e8f0'
                                    }
                                }
                            }
                        }
                    });
                },

                renderStatusChart() {
                    const ctx = this.$refs.statusChart;
                    if (!ctx) return;

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.metrics || {});
                    const data = Object.values(this.data.metrics || {});

                    this.charts.status = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => this.translate(l)),
                            datasets: [{
                                data: data,
                                backgroundColor: ['#16a34a', '#f59e0b', '#0ea5e9', '#8b5cf6', '#ef4444',
                                    '#64748b'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14
                                    }
                                }
                            }
                        }
                    });
                },

                renderLateChart() {
                    const ctx = this.$refs.lateChart;
                    if (!ctx) return;

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.late || {});
                    const data = Object.values(this.data.late || {});

                    this.charts.late = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: ['#fde68a', '#fbbf24', '#f59e0b', '#d97706'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14
                                    }
                                }
                            }
                        }
                    });
                },

                renderGenderChart() {
                    const ctx = this.$refs.genderChart;
                    if (!ctx) return;

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.gender || {});
                    const data = Object.values(this.data.gender || {});

                    this.charts.gender = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => this.translate(l)),
                            datasets: [{
                                data: data,
                                backgroundColor: ['#0f766e', '#16a34a', '#94a3b8'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14
                                    }
                                }
                            }
                        }
                    });
                },

                renderAbsentChart() {
                    const ctx = this.$refs.absentChart;
                    if (!ctx) return;

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.absent || {});
                    const data = Object.values(this.data.absent || {});

                    if (!labels.length) {
                        return;
                    }

                    this.charts.absent = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => this.translate(l)),
                            datasets: [{
                                data: data,
                                backgroundColor: ['#0ea5e9', '#8b5cf6', '#e11d48', '#f59e0b'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14
                                    }
                                }
                            }
                        }
                    });
                },

                renderHeadcountChart() {
                    const ctx = this.$refs.headcountChart;
                    if (!ctx) return;

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    this.charts.headcount = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.data.headcount?.labels || [],
                            datasets: [{
                                label: '{{ __('Headcount') }}',
                                data: this.data.headcount?.data || [],
                                backgroundColor: '#0f766e',
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#e2e8f0'
                                    }
                                }
                            }
                        }
                    });
                },

                renderEmployeeOriginsMap() {
                    if (typeof L === 'undefined' || typeof L.markerClusterGroup === 'undefined') {
                        setTimeout(() => this.renderEmployeeOriginsMap(), 100);
                        return;
                    }

                    const mapElement = this.$refs.employeeOriginsMap || document.getElementById('employeeOriginsMap');
                    if (!mapElement) return;

                    if (!mapElement._analyticsLeaflet) {
                        const map = L.map(mapElement, {
                            fadeAnimation: false,
                            markerZoomAnimation: false,
                            wheelDebounceTime: 80,
                            zoomAnimation: false,
                        }).setView([-2.548926, 118.0148634], 5);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                            subdomains: 'abcd',
                            maxZoom: 20
                        }).addTo(map);

                        const markersLayer = L.markerClusterGroup({
                            animate: false,
                            animateAddingMarkers: false,
                            showCoverageOnHover: false,
                            spiderfyOnMaxZoom: true,
                            maxClusterRadius: 50,
                            iconCreateFunction: function(cluster) {
                                const markers = cluster.getAllChildMarkers();
                                let c = ' marker-cluster-';
                                if (markers.length < 10) {
                                    c += 'small';
                                } else if (markers.length < 100) {
                                    c += 'medium';
                                } else {
                                    c += 'large';
                                }

                                return new L.DivIcon({
                                    html: `<div class="bg-emerald-600/90 text-white font-bold rounded-full w-full h-full flex items-center justify-center border-2 border-white shadow-lg"><span>${markers.length}</span></div>`,
                                    className: 'marker-cluster' + c,
                                    iconSize: new L.Point(40, 40)
                                });
                            }
                        }).addTo(map);

                        mapElement._analyticsLeaflet = {
                            map,
                            markersLayer,
                            resizeObserver: null,
                        };

                        if (typeof ResizeObserver !== 'undefined') {
                            mapElement._analyticsLeaflet.resizeObserver = new ResizeObserver(() => {
                                if (mapElement._analyticsLeaflet?.map) {
                                    mapElement._analyticsLeaflet.map.invalidateSize();
                                }
                            });
                            mapElement._analyticsLeaflet.resizeObserver.observe(mapElement);
                        }
                    }

                    const {
                        map,
                        markersLayer
                    } = mapElement._analyticsLeaflet;
                    markersLayer.clearLayers();

                    const mapData = this.data.regionDistribution || [];
                    if (mapData.length > 0) {
                        const bounds = L.latLngBounds();

                        mapData.forEach(item => {
                            if (item.lat && item.lng) {
                                const latLng = [item.lat, item.lng];
                                bounds.extend(latLng);

                                const customIcon = L.divIcon({
                                    className: 'custom-div-icon',
                                    html: `
                                        <div class="relative flex items-center justify-center rounded-full border-2 border-white shadow-md text-white bg-emerald-500 w-8 h-8">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                    `,
                                    iconSize: [32, 32],
                                    iconAnchor: [16, 16]
                                });

                                const marker = L.marker(latLng, {
                                    icon: customIcon
                                });
                                const popupContent = `
                                    <div class="p-2 min-w-[120px] text-center">
                                        <div class="font-bold text-sm text-gray-800 mb-1">${item.name}</div>
                                        <div class="text-xs text-gray-500">${item.region}</div>
                                    </div>
                                `;
                                marker.bindPopup(popupContent);
                                markersLayer.addLayer(marker);
                            }
                        });

                        if (bounds.isValid()) {
                            map.fitBounds(bounds, {
                                padding: [40, 40],
                                maxZoom: 8
                            });
                        }
                    } else {
                        map.setView([-2.548926, 118.0148634], 5);
                    }

                    setTimeout(() => {
                        map.invalidateSize();
                    }, 200);
                }
            });

            window.registerAnalyticsChartsComponent = window.registerAnalyticsChartsComponent || function() {
                if (!window.Alpine) {
                    return false;
                }

                if (!window.__analyticsChartsComponentRegistered) {
                    Alpine.data('analyticsChartsComponent', () => {
                        const base = window.initAnalyticsCharts(window.analyticsChartsPayload || {});

                        return {
                            ...base,
                            boot() {
                                this.init();
                            },
                        };
                    });

                    window.__analyticsChartsComponentRegistered = true;
                }

                return true;
            };

            window.initAnalyticsChartsPage = function() {
                if (!window.registerAnalyticsChartsComponent || !window.registerAnalyticsChartsComponent()) {
                    return;
                }

                document.querySelectorAll('[data-analytics-charts-root]').forEach((root) => {
                    if (root._x_dataStack || root.__x) {
                        const component = window.Alpine?.$data?.(root);

                        if (component?.data) {
                            component.data = window.analyticsChartsPayload || {};
                            component.renderCharts?.();
                        }

                        return;
                    }

                    window.Alpine.initTree(root);
                });
            };

            if (!window.registerAnalyticsChartsComponent()) {
                document.addEventListener('alpine:init', () => {
                    window.initAnalyticsChartsPage();
                }, {
                    once: true
                });
            } else {
                queueMicrotask(() => {
                    window.initAnalyticsChartsPage();
                });
            }

            if (!window.__analyticsChartsNavigatedListenerRegistered) {
                document.addEventListener('livewire:navigated', () => {
                    if (document.querySelector('[data-analytics-charts-root]')) {
                        queueMicrotask(() => {
                            window.initAnalyticsChartsPage();
                        });
                    }
                });

                window.__analyticsChartsNavigatedListenerRegistered = true;
            }
        </script>
    @endpush
</x-admin.page-shell>
