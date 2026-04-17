@php
    $selectedPeriod = \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->translatedFormat('F Y');
    $presentTotal = $metrics['present'] ?? 0;
    $lateTotal = $metrics['late'] ?? 0;
    $sickTotal = $metrics['sick'] ?? 0;
    $excusedTotal = $metrics['excused'] ?? 0;
    $alphaTotal = ($metrics['alpha'] ?? 0) + ($metrics['absent'] ?? 0);
    $attendanceMixTotal = max($presentTotal + $lateTotal + $sickTotal + $excusedTotal + $alphaTotal, 1);
    $topRegions = collect($regionDistribution)
        ->countBy(fn ($item) => $item['region'] ?? __('Unknown'))
        ->sortDesc()
        ->take(5);
    $divisionLeaders = collect($divisionStats['labels'] ?? [])
        ->values()
        ->map(fn ($label, $index) => [
            'label' => $label,
            'value' => $divisionStats['data'][$index] ?? 0,
        ])
        ->sortByDesc('value')
        ->take(5)
        ->values();
    $genderBreakdown = collect([
        ['label' => __('Male'), 'value' => $genderDemographics['male'] ?? 0],
        ['label' => __('Female'), 'value' => $genderDemographics['female'] ?? 0],
    ])->filter(fn ($item) => $item['value'] > 0)->values();
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

<x-admin-page-shell
    :title="__('Analytics Dashboard')"
    :description="__('Comprehensive overview of workforce performance.')"
    x-data="analyticsChartsComponent"
    x-init="boot()"
>
    <x-slot name="actions">
        <span class="inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-semibold text-primary-700 dark:border-primary-900/40 dark:bg-primary-900/20 dark:text-primary-300">
            <x-heroicon-o-calendar-days class="h-4 w-4" />
            {{ $selectedPeriod }}
        </span>
        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            <x-heroicon-o-banknotes class="h-4 w-4" />
            {{ __('Work Standard') }}: {{ $workHoursPerDay }} {{ __('Hours / Day') }}
        </span>
    </x-slot>

    <x-slot name="toolbar">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Filter') }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Use month and year filters to compare attendance performance, workforce mix, and operational risk over time.') }}
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="w-full sm:w-44">
                    <x-tom-select wire:model.live="month" placeholder="{{ __('Select Month') }}" class="w-full">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </x-tom-select>
                </div>
                <div class="w-full sm:w-32">
                    <x-tom-select wire:model.live="year" placeholder="{{ __('Select Year') }}" class="w-full">
                        @foreach (range(date('Y') - 1, date('Y')) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </x-tom-select>
                </div>
                <div wire:loading class="flex items-center px-1 text-primary-600">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($summaryCards as $card)
                @php
                    $toneClasses = match ($card['tone']) {
                        'primary' => 'border-primary-200/70 bg-primary-50/70 dark:border-primary-900/30 dark:bg-primary-900/10',
                        'emerald' => 'border-emerald-200/70 bg-emerald-50/70 dark:border-emerald-900/30 dark:bg-emerald-900/10',
                        'amber' => 'border-amber-200/70 bg-amber-50/70 dark:border-amber-900/30 dark:bg-amber-900/10',
                        'teal' => 'border-teal-200/70 bg-teal-50/70 dark:border-teal-900/30 dark:bg-teal-900/10',
                        default => 'border-slate-200/70 bg-white dark:border-slate-700 dark:bg-slate-900/80',
                    };
                @endphp
                <div class="rounded-3xl border p-5 shadow-sm {{ $toneClasses }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                    <p class="mt-2 text-sm leading-5 text-slate-600 dark:text-slate-300">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)]">
            <div class="flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Attendance Trend') }}</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Daily movement across the selected period') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Present, late, and absent records are plotted together so trend shifts are easier to compare.') }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $selectedPeriod }}
                    </span>
                </div>
                <div class="mt-6 flex-1 border-t border-slate-200/70 bg-slate-50/60 px-1 pt-5 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="h-full min-h-[420px] w-full">
                        <canvas x-ref="trendChart" class="!h-full !w-full"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid h-full gap-6 xl:grid-rows-2">
                <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Attendance Mix') }}</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Breakdown of recorded statuses') }}</h3>
                        </div>
                        <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">{{ $attendanceMixTotal }}</span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @foreach ([
                            ['label' => __('Present'), 'value' => $presentTotal, 'bar' => 'bg-primary-600'],
                            ['label' => __('Late'), 'value' => $lateTotal, 'bar' => 'bg-amber-500'],
                            ['label' => __('Approved Leave'), 'value' => $sickTotal + $excusedTotal, 'bar' => 'bg-sky-500'],
                            ['label' => __('Alpha / Absent'), 'value' => $alphaTotal, 'bar' => 'bg-rose-500'],
                        ] as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-700 dark:text-slate-200">{{ $row['label'] }}</span>
                                    <span class="text-slate-500 dark:text-slate-400">{{ $row['value'] }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div class="h-full rounded-full {{ $row['bar'] }}" style="width: {{ round(($row['value'] / $attendanceMixTotal) * 100, 1) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Workforce Snapshot') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Current profile highlights') }}</h3>

                    <div class="mt-5 space-y-5">
                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Gender Split') }}</p>
                            <div class="mt-3 space-y-3">
                                @forelse ($genderBreakdown as $row)
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-sm">
                                            <span class="text-slate-600 dark:text-slate-300">{{ $row['label'] }}</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $row['value'] }}</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div class="h-full rounded-full bg-teal-500" style="width: {{ round(($row['value'] / $genderTotal) * 100, 1) }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No demographic data available.') }}</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Top Regions') }}</p>
                            <div class="mt-3 space-y-3">
                                @forelse ($topRegions as $region => $count)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="truncate text-slate-600 dark:text-slate-300">{{ $region }}</span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $count }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No regional distribution available.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Division Performance') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Present volume by division') }}</h3>
                </div>
                <div class="h-80">
                    <canvas x-ref="divisionChart"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Status Distribution') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Overall status composition') }}</h3>
                </div>
                <div class="h-80">
                    <canvas x-ref="statusChart"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Late Analysis') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Severity buckets for tardiness') }}</h3>
                </div>
                <div class="h-80">
                    <canvas x-ref="lateChart"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Gender Demographics') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Workforce composition by gender') }}</h3>
                </div>
                <div class="h-80">
                    <canvas x-ref="genderChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.95fr)]">
            <div class="flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Geographical Distribution') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Employee origins across regions') }}</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Profile address data is plotted on the map to show where the workforce is concentrated.') }}</p>
                </div>
                <div class="mt-6 flex-1 border-t border-slate-200/70 pt-5 dark:border-slate-800">
                    <div id="employeeOriginsMap" class="h-full min-h-[500px] w-full overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700"></div>
                </div>
            </div>

            <div class="grid h-full gap-6 xl:grid-rows-[minmax(0,1fr)_auto]">
                <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Headcount Distribution') }}</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Active employees by division') }}</h3>
                    </div>
                    <div class="h-80">
                        <canvas x-ref="headcountChart"></canvas>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Top Performing Divisions') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Highest present volume this period') }}</h3>

                    <div class="mt-5 space-y-3">
                        @forelse ($divisionLeaders as $index => $division)
                            <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200/70 bg-slate-50/70 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/60">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{{ $division['label'] }}</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $division['value'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No division data available.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-3xl border border-emerald-200/70 bg-gradient-to-br from-white to-emerald-50/70 p-6 shadow-sm dark:border-emerald-900/30 dark:from-slate-900 dark:to-emerald-950/20">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Wall of Fame') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Early Birds') }}</h3>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($topDiligent as $employee)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-emerald-100 bg-white/80 px-4 py-3 dark:border-emerald-900/20 dark:bg-slate-900/60">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $employee->jobTitle?->name ?? __('Employee') }}</p>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                {{ gmdate('H:i', $employee->avg_check_in) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No data available') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-amber-200/70 bg-gradient-to-br from-white to-amber-50/70 p-6 shadow-sm dark:border-amber-900/30 dark:from-slate-900 dark:to-amber-950/20">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-amber-100 p-3 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        <x-heroicon-o-exclamation-circle class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Attention') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Frequent Late') }}</h3>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($topLate as $employee)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-amber-100 bg-white/80 px-4 py-3 dark:border-amber-900/20 dark:bg-slate-900/60">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</p>
                            </div>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                {{ $employee->late_count }}x
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Everyone is on time!') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-rose-200/70 bg-gradient-to-br from-white to-rose-50/70 p-6 shadow-sm dark:border-rose-900/30 dark:from-slate-900 dark:to-rose-950/20">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-rose-100 p-3 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                        <x-heroicon-o-arrow-right-end-on-rectangle class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Attention') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Early Runners') }}</h3>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($topEarlyLeavers as $employee)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-rose-100 bg-white/80 px-4 py-3 dark:border-rose-900/20 dark:bg-slate-900/60">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</p>
                            </div>
                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                                {{ $employee->early_leave_count }}x
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Full attendance!') }}</p>
                    @endforelse
                </div>
            </div>
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
                        'present': '{{ __("Present") }}',
                        'late': '{{ __("Late") }}',
                        'sick': '{{ __("Sick") }}',
                        'excused': '{{ __("Excused") }}',
                        'absent': '{{ __("Absent") }}',
                        'alpha': '{{ __("Alpha") }}',
                        'male': '{{ __("Male") }}',
                        'female': '{{ __("Female") }}'
                    };
                    return dict[key.toLowerCase()] || (key.charAt(0).toUpperCase() + key.slice(1));
                },

                init() {
                    this.$nextTick(() => {
                        this.renderCharts();
                    });

                    Livewire.on('chart-update', (newData) => {
                        this.data.trend = newData.trend;
                        this.data.metrics = newData.metrics;
                        this.data.division = newData.divisionStats;
                        this.data.late = newData.lateBuckets;
                        this.data.absent = newData.absentStats;
                        this.data.regionDistribution = newData.regionDistribution;

                        this.renderCharts();
                    });

                    Livewire.on('hris-update', (newData) => {
                        this.data.gender = newData.genderDemographics;
                        this.data.headcount = newData.headcountStats;
                        this.renderCharts();
                    });
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
                            datasets: [
                                {
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
                                    labels: { usePointStyle: true, boxWidth: 8 }
                                },
                                tooltip: { mode: 'index', intersect: false }
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, grid: { color: '#e2e8f0' } }
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
                                label: '{{ __("Present") }}',
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
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, grid: { color: '#e2e8f0' } }
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
                                backgroundColor: ['#16a34a', '#f59e0b', '#0ea5e9', '#8b5cf6', '#ef4444', '#64748b'],
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
                                    labels: { usePointStyle: true, boxWidth: 8, padding: 14 }
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
                                    labels: { usePointStyle: true, boxWidth: 8, padding: 14 }
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
                                    labels: { usePointStyle: true, boxWidth: 8, padding: 14 }
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
                                label: '{{ __("Headcount") }}',
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
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, grid: { color: '#e2e8f0' } }
                            }
                        }
                    });
                },

                renderEmployeeOriginsMap() {
                    if (typeof L === 'undefined' || typeof L.markerClusterGroup === 'undefined') {
                        setTimeout(() => this.renderEmployeeOriginsMap(), 100);
                        return;
                    }

                    const mapElement = document.getElementById('employeeOriginsMap');
                    if (!mapElement) return;

                    if (!this.mapInstance) {
                        this.mapInstance = L.map(mapElement).setView([-2.548926, 118.0148634], 5);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                            subdomains: 'abcd',
                            maxZoom: 20
                        }).addTo(this.mapInstance);

                        this.markersLayer = L.markerClusterGroup({
                            showCoverageOnHover: false,
                            spiderfyOnMaxZoom: true,
                            maxClusterRadius: 50,
                            iconCreateFunction: function (cluster) {
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
                        }).addTo(this.mapInstance);

                        if (typeof ResizeObserver !== 'undefined') {
                            this.mapResizeObserver = new ResizeObserver(() => {
                                if (this.mapInstance) {
                                    this.mapInstance.invalidateSize();
                                }
                            });
                            this.mapResizeObserver.observe(mapElement);
                        }
                    }

                    this.markersLayer.clearLayers();

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

                                const marker = L.marker(latLng, { icon: customIcon });
                                const popupContent = `
                                    <div class="p-2 min-w-[120px] text-center">
                                        <div class="font-bold text-sm text-gray-800 mb-1">${item.name}</div>
                                        <div class="text-xs text-gray-500">${item.region}</div>
                                    </div>
                                `;
                                marker.bindPopup(popupContent);
                                this.markersLayer.addLayer(marker);
                            }
                        });

                        if (bounds.isValid()) {
                            this.mapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 8 });
                        }
                    } else {
                        this.mapInstance.setView([-2.548926, 118.0148634], 5);
                    }

                    setTimeout(() => {
                        this.mapInstance.invalidateSize();
                    }, 200);
                }
            });

            document.addEventListener('alpine:init', () => {
                Alpine.data('analyticsChartsComponent', () => {
                    const base = window.initAnalyticsCharts(window.analyticsChartsPayload || {});

                    return {
                        ...base,
                        boot() {
                            this.init();
                        },
                    };
                });
            });
        </script>
    @endpush
</x-admin-page-shell>
