@php
    $alertCodes = collect($health['alerts'])->pluck('code');
    $hasAlert = fn (string $code) => $alertCodes->contains($code);
    $formatTime = function ($value): string {
        if (! $value) {
            return __('Not seen');
        }

        try {
            $date = \Illuminate\Support\Carbon::parse($value);

            return $date->translatedFormat('d M Y H:i').' · '.$date->diffForHumans();
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $shortChecksum = fn (?string $value): string => $value ? substr($value, 0, 12).'...' : __('Not recorded');
    $statusLabel = $health['status'] === 'ok' ? __('Operational') : __('Needs Attention');
    $statusTone = $health['status'] === 'ok'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-300'
        : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300';
    $backupLabel = $health['backup']['checksum_matches_meta'] === false
        ? __('Checksum Mismatch')
        : ($health['backup']['file_present'] ? __('File Present') : __('No File'));
    $backupTone = $health['backup']['checksum_matches_meta'] === false || ! $health['backup']['file_present']
        ? 'warning'
        : 'ok';
    $checkToneClasses = [
        'ok' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'critical' => 'bg-rose-500',
        'neutral' => 'bg-slate-400',
    ];
    $subsystemChecks = [
        [
            'label' => __('Database'),
            'tone' => $health['database']['ok'] ? (($health['database']['latency_ms'] ?? 0) > 500 ? 'warning' : 'ok') : 'critical',
            'value' => $health['database']['ok'] ? __('Connected') : __('Disconnected'),
            'detail' => $health['database']['latency_ms'] !== null ? __('Latency: :value ms', ['value' => $health['database']['latency_ms']]) : __($health['database']['error'] ?? 'Database connectivity check failed.'),
        ],
        [
            'label' => __('Queue Worker'),
            'tone' => $hasAlert('queue_stale') ? 'critical' : 'ok',
            'value' => $hasAlert('queue_stale') ? __('Stale') : __('Heartbeat OK'),
            'detail' => __('Connection: :connection · Backlog: :backlog · Failed jobs: :count', ['connection' => $health['queue_connection'], 'backlog' => $health['queue_backlog_count'], 'count' => $health['failed_jobs_count']]).' · '.$formatTime($health['queue_heartbeat_at']),
        ],
        [
            'label' => __('Email Delivery'),
            'tone' => ($health['email_delivery']['failed'] ?? 0) > 0 ? 'warning' : ((($health['email_delivery']['queued'] ?? 0) + ($health['email_delivery']['retrying'] ?? 0)) > 0 ? 'neutral' : 'ok'),
            'value' => __(':count sent (24h)', ['count' => $health['email_delivery']['sent_last_24h'] ?? 0]),
            'detail' => __('Queued: :queued · Retrying: :retrying · Failed: :failed', ['queued' => $health['email_delivery']['queued'] ?? 0, 'retrying' => $health['email_delivery']['retrying'] ?? 0, 'failed' => $health['email_delivery']['failed'] ?? 0]),
        ],
        [
            'label' => __('Scheduler'),
            'tone' => $hasAlert('scheduler_stale') ? 'critical' : 'ok',
            'value' => $hasAlert('scheduler_stale') ? __('Stale') : __('Heartbeat OK'),
            'detail' => $formatTime($health['scheduler_heartbeat_at']),
        ],
        [
            'label' => __('Storage'),
            'tone' => ! $health['storage_writable'] ? 'critical' : ($hasAlert('disk_low') ? 'warning' : 'ok'),
            'value' => $health['storage_writable'] ? __('Writable') : __('Not writable'),
            'detail' => __('Free disk: :value', ['value' => $health['disk_free_human']]),
        ],
        [
            'label' => __('Import / Export'),
            'tone' => ($health['import_export']['failed'] ?? 0) > 0 ? 'warning' : (($health['import_export']['queued'] ?? 0) + ($health['import_export']['running'] ?? 0) > 0 ? 'neutral' : 'ok'),
            'value' => __(':count active runs', ['count' => ($health['import_export']['queued'] ?? 0) + ($health['import_export']['running'] ?? 0)]),
            'detail' => __('Queued: :queued · Running: :running · Failed: :failed', ['queued' => $health['import_export']['queued'] ?? 0, 'running' => $health['import_export']['running'] ?? 0, 'failed' => $health['import_export']['failed'] ?? 0]),
        ],
        [
            'label' => __('HR Compliance'),
            'tone' => array_sum($health['hr_compliance']) > 0 ? 'warning' : 'ok',
            'value' => __(':count reminders', ['count' => array_sum($health['hr_compliance'])]),
            'detail' => __('Probation: :probation · Contract: :contract · Documents/Profile: :profile · HR Tasks: :tasks', [
                'probation' => $health['hr_compliance']['probation_due'],
                'contract' => $health['hr_compliance']['contract_due'],
                'profile' => $health['hr_compliance']['incomplete_profiles'],
                'tasks' => $health['hr_compliance']['overdue_hr_tasks'],
            ]),
        ],
        [
            'label' => __('Backup Integrity'),
            'tone' => $backupTone,
            'value' => $backupLabel,
            'detail' => __('Last success: :value', ['value' => $health['backup']['last_success_at'] ? $formatTime($health['backup']['last_success_at']) : __('No completed backup')]),
        ],
        [
            'label' => __('Realtime'),
            'tone' => 'neutral',
            'value' => $health['realtime']['reverb_enabled'] ? __('Reverb') : __('Polling / fallback'),
            'detail' => __('Broadcast: :driver · Polling: :interval', ['driver' => $health['realtime']['broadcast_connection'], 'interval' => $health['realtime']['polling_fallback']]),
        ],
    ];
    $runtimeRows = [
        __('Application Version') => $health['app_version'],
        __('PHP Version') => $health['php_version'],
        __('Database') => $health['database_driver'].' · '.$health['database_version'],
        __('Cache Driver') => $health['cache_driver'],
        __('Session Driver') => $health['session_driver'],
        __('Queue Connection') => $health['queue_connection'],
        __('Broadcast Connection') => $health['realtime']['broadcast_connection'],
        __('Polling Fallback') => $health['realtime']['polling_fallback'],
    ];
    $licenseRows = [
        __('Payroll') => $health['license']['payroll_locked'] ? __('Locked') : __('Available'),
        __('Reporting') => $health['license']['reporting_locked'] ? __('Locked') : __('Available'),
        __('System Backup') => $health['license']['system_backup_locked'] ? __('Locked') : __('Available'),
    ];
@endphp

<x-app-layout>
    <x-admin.page-shell
        :title="__('Operational Health')"
        :description="__('Monitor queue, scheduler, database, storage, backup integrity, and runtime posture.')"
        :show-description="true">
        <x-slot name="actions">
            <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:flex-wrap sm:justify-end">
                <a href="{{ route('admin.system-maintenance') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <x-heroicon-m-wrench-screwdriver class="h-4 w-4" />
                    {{ __('Maintenance') }}
                </a>
                <a href="{{ route('admin.operational-health') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950">
                    <x-heroicon-m-arrow-path class="h-4 w-4" />
                    {{ __('Refresh') }}
                </a>
            </div>
        </x-slot>

    <div class="grid gap-3 lg:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.65fr)]">
        <x-admin.insight-panel class="overflow-hidden rounded-2xl bg-white/90 dark:bg-slate-900/90">
            <div class="px-4 pb-2 pt-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Current State') }}</p>
                        <h2 class="mt-1 text-base font-bold text-slate-950 dark:text-white">{{ __('Operational Readiness Snapshot') }}</h2>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full border px-3 py-1 text-xs font-bold {{ $statusTone }}">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <dl class="grid gap-2 p-3 md:grid-cols-3">
                <div class="rounded-xl bg-slate-50/80 p-3 dark:bg-white/[0.035]">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Database') }}</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-950 dark:text-white">
                        {{ $health['database']['latency_ms'] !== null ? $health['database']['latency_ms'].' ms' : __('Attention') }}
                    </dd>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $health['database']['ok'] ? __('Connectivity check passed') : __('Connectivity check failed') }}</p>
                </div>
                <div class="rounded-xl bg-slate-50/80 p-3 dark:bg-white/[0.035]">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Email delivery') }}</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-950 dark:text-white">{{ $health['email_delivery']['sent_last_24h'] }}</dd>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $health['email_delivery']['queued'] }} {{ __('queued') }} · {{ $health['email_delivery']['failed'] }} {{ __('failed') }}</p>
                </div>
                <div class="rounded-xl bg-slate-50/80 p-3 dark:bg-white/[0.035]">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Queue') }}</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-950 dark:text-white">{{ $health['queue_backlog_count'] }}</dd>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('pending jobs') }} · {{ $health['failed_jobs_count'] }} {{ __('failed') }}</p>
                </div>
                <div class="rounded-xl bg-slate-50/80 p-3 dark:bg-white/[0.035]">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Disk Free') }}</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-950 dark:text-white">{{ $health['disk_free_human'] }}</dd>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ $health['storage_writable'] ? __('storage/app writable') : __('storage/app not writable') }}
                        @if($health['disk_used_percent'] !== null)
                            · {{ $health['disk_used_percent'] }}% {{ __('used') }}
                        @endif
                    </p>
                </div>
            </dl>
        </x-admin.insight-panel>

        <x-admin.insight-panel class="overflow-hidden rounded-2xl bg-white/90 dark:bg-slate-900/90">
            <div class="px-4 pb-2 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Alerts') }}</p>
                <h2 class="mt-1 text-base font-bold text-slate-950 dark:text-white">{{ count($health['alerts']) }} {{ __('active') }}</h2>
            </div>

            @if(! empty($health['alerts']))
                <ul class="space-y-2 p-3 pt-1">
                    @foreach($health['alerts'] as $alert)
                        @php
                            $alertTone = $alert['level'] === 'critical'
                                ? 'bg-rose-500'
                                : ($alert['level'] === 'warning' ? 'bg-amber-500' : 'bg-slate-400');
                        @endphp
                        <li class="rounded-xl bg-slate-50/80 p-3 dark:bg-white/[0.035]">
                            <div class="flex items-start gap-3">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $alertTone }}"></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $alert['level'] }} · {{ $alert['code'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ __($alert['message']) }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="px-4 py-5 text-sm text-slate-600 dark:text-slate-300">
                    {{ __('No active operational alerts. Queue, scheduler, storage, database, and backup checks are within the configured thresholds.') }}
                </div>
            @endif
        </x-admin.insight-panel>
    </div>

    <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,0.45fr)]">
        <x-admin.insight-panel class="overflow-hidden">
            <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Subsystem Checks') }}</h2>
            </div>
            <div class="divide-y divide-slate-200/70 dark:divide-slate-800">
                @foreach($subsystemChecks as $check)
                    <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(12rem,0.35fr)_minmax(0,1fr)]">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $checkToneClasses[$check['tone']] ?? $checkToneClasses['neutral'] }}"></span>
                            <span class="text-sm font-semibold text-slate-950 dark:text-white">{{ $check['label'] }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $check['value'] }}</p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.insight-panel>

        <div class="space-y-3">
            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Runtime Posture') }}</h2>
                </div>
                <dl class="divide-y divide-slate-200/70 dark:divide-slate-800">
                    @foreach($runtimeRows as $label => $value)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                            <dd class="truncate text-right text-xs font-bold text-slate-900 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Feature Locks') }}</h2>
                </div>
                <dl class="divide-y divide-slate-200/70 dark:divide-slate-800">
                    @foreach($licenseRows as $label => $value)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</dt>
                            <dd class="text-sm font-bold text-slate-950 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.insight-panel>
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-[minmax(0,0.7fr)_minmax(20rem,0.3fr)]">
        <x-admin.insight-panel class="overflow-hidden">
            <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Operational Workload') }}</h2>
            </div>
            <dl class="grid divide-y divide-slate-200/70 dark:divide-slate-800 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                <div class="px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Queue Backlog') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['queue_backlog_count'] }}</dd>
                </div>
                <div class="px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Import/Export Queued') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['import_export']['queued'] }}</dd>
                </div>
                <div class="px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Import/Export Running') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['import_export']['running'] }}</dd>
                </div>
                <div class="px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Last Export Done') }}</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $health['import_export']['last_completed_at'] ? $formatTime($health['import_export']['last_completed_at']) : __('None recorded') }}</dd>
                </div>
            </dl>
        </x-admin.insight-panel>

        <x-admin.insight-panel class="overflow-hidden">
            <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Storage Usage') }}</h2>
            </div>
            <div class="px-4 py-3">
                <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, max(0, $health['disk_used_percent'] ?? 0)) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ __('Used') }}: {{ $health['disk_used_percent'] ?? 0 }}% · {{ __('Total') }}: {{ $health['disk_total_human'] }}
                </p>
            </div>
        </x-admin.insight-panel>
    </div>

    <x-admin.insight-panel class="overflow-hidden">
        <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Backup Integrity Detail') }}</h2>
        </div>
        <dl class="grid divide-y divide-slate-200/70 dark:divide-slate-800 md:grid-cols-4 md:divide-x md:divide-y-0">
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Last Success') }}</dt>
                <dd class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $health['backup']['last_success_at'] ? $formatTime($health['backup']['last_success_at']) : __('No completed backup') }}</dd>
            </div>
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Last Failure') }}</dt>
                <dd class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $health['backup']['last_failed_at'] ? $formatTime($health['backup']['last_failed_at']) : __('None recorded') }}</dd>
            </div>
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Checksum') }}</dt>
                <dd class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $health['backup']['checksum_matches_meta'] === null ? __('Not available') : ($health['backup']['checksum_matches_meta'] ? __('OK') : __('Mismatch')) }}</dd>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $shortChecksum($health['backup']['checksum_sha256']) }}</p>
            </div>
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('File') }}</dt>
                <dd class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $health['backup']['file_present'] ? __('Present') : __('Missing') }}</dd>
            </div>
        </dl>
    </x-admin.insight-panel>

    <x-admin.insight-panel class="overflow-hidden">
        <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('HR Compliance Reminders') }}</h2>
        </div>
        <dl class="grid divide-y divide-slate-200/70 dark:divide-slate-800 sm:grid-cols-5 sm:divide-x sm:divide-y-0">
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Probation') }}</dt>
                <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['hr_compliance']['probation_due'] }}</dd>
            </div>
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Contracts') }}</dt>
                <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['hr_compliance']['contract_due'] }}</dd>
            </div>
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Profiles') }}</dt>
                <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['hr_compliance']['incomplete_profiles'] }}</dd>
            </div>
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('HR Tasks') }}</dt>
                <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['hr_compliance']['overdue_hr_tasks'] }}</dd>
            </div>
            <div class="px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Auto Disable') }}</dt>
                <dd class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $health['hr_compliance']['auto_disable_due'] }}</dd>
            </div>
        </dl>
    </x-admin.insight-panel>

    <x-admin.insight-panel class="overflow-hidden">
        <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Database Table Summary') }}</h2>
        </div>
        @if(! empty($health['tables']))
            <div class="md:overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Table') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Rows') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Size') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @foreach($health['tables'] as $table)
                            <tr>
                                <td class="px-4 py-2 font-medium text-slate-900 dark:text-white">{{ $table['name'] }}</td>
                                <td class="px-4 py-2 text-right text-slate-600 dark:text-slate-300">{{ $table['rows'] === null ? __('Unknown') : number_format($table['rows']) }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-slate-900 dark:text-white">{{ $table['size'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                {{ __('Table size summary is available on MySQL-compatible databases when information_schema access is allowed.') }}
            </div>
        @endif
    </x-admin.insight-panel>
</x-admin.page-shell>
</x-app-layout>
