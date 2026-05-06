@php
    $canManageMaintenance = auth()->user()?->can('manageSystemMaintenance') ?? false;
    $canManageBackups = auth()->user()?->can('create', \App\Models\SystemBackupRun::class) ?? false;
@endphp

<x-admin.page-shell :title="__('System Maintenance')" :description="__('Operate cleanup, cache, backup, and recovery workflows from one place.')" x-data="{
    search: '',
    sectionFilter: 'all',
    matchesPanel(type, title, description) {
        const haystack = `${title} ${description}`.toLowerCase();
        const query = (this.search || '').toLowerCase().trim();
        const filterOk = this.sectionFilter === 'all' || this.sectionFilter === type;
        const searchOk = !query || haystack.includes(query);
        return filterOk && searchOk;
    },
}">
    <x-slot name="toolbar">
        <x-admin.page-tools>
            <x-slot name="actions">
                <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $maintenanceMode ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' }}">
                    {{ $maintenanceMode ? __('Maintenance mode active') : __('System available') }}
                </span>
            </x-slot>

            <div class="md:col-span-2 xl:col-span-8">
                <x-forms.label for="maintenance-search" value="{{ __('Search maintenance tasks') }}"
                    class="mb-1.5 block" />
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    <x-forms.input id="maintenance-search" type="search" x-model.debounce.200ms="search"
                        placeholder="{{ __('Search cleanup, cache, backup, or recovery tasks...') }}"
                        class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-4">
                <x-forms.label for="maintenance-section-filter" value="{{ __('Task Type') }}" class="mb-1.5 block" />
                <x-forms.select id="maintenance-section-filter" x-model="sectionFilter" class="w-full">
                    <option value="all">{{ __('All maintenance tasks') }}</option>
                    <option value="ops">{{ __('Operations') }}</option>
                    <option value="cleanup">{{ __('Cleanup') }}</option>
                    <option value="backup">{{ __('Backup') }}</option>
                    <option value="restore">{{ __('Restore') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div class="space-y-3">
        <dl class="grid grid-cols-3 gap-2 md:grid-cols-3 xl:grid-cols-6" role="region" aria-label="{{ __('System Statistics') }}">
            @foreach ($systemStats as $stat)
                <div @class([
                    'rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 dark:border-slate-700/80 dark:bg-slate-900/85',
                ])>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                        {{ $stat['label'] }}
                    </dt>
                    <dd class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $stat['value'] }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="grid gap-3 xl:grid-cols-[minmax(0,1.45fr)_minmax(17rem,1fr)]">
            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('System Health') }}</h2>
                </div>
                <div class="grid gap-2 px-4 py-3 md:grid-cols-2">
                    @foreach ($healthChecks as $check)
                        <div wire:key="health-check-{{ \Illuminate\Support\Str::slug($check['label']) }}" @class([
                            'rounded-lg border bg-white p-3 dark:bg-slate-900/70',
                            'border-emerald-200/70 dark:border-emerald-900/30' => $check['status'] === 'success',
                            'border-amber-200/70 dark:border-amber-900/30' => $check['status'] === 'warning',
                            'border-rose-200/70 dark:border-rose-900/30' => $check['status'] === 'danger',
                            'border-slate-200/70 dark:border-slate-700/70' => ! in_array($check['status'], ['success', 'warning', 'danger'], true),
                        ])>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'h-2 w-2 rounded-full',
                                        'bg-emerald-500 dark:bg-emerald-400' => $check['status'] === 'success',
                                        'bg-amber-500 dark:bg-amber-400' => $check['status'] === 'warning',
                                        'bg-rose-500 dark:bg-rose-400' => $check['status'] === 'danger',
                                        'bg-slate-400 dark:bg-slate-500' => ! in_array($check['status'], ['success', 'warning', 'danger'], true),
                                    ])  role="img" aria-label="{{ ucfirst($check['status']) }}"></span>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $check['label'] }}</p>
                                </div>
                                <p class="text-sm font-bold text-slate-950 dark:text-white">{{ $check['value'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.insight-panel>

            <div class="space-y-3">
                <x-admin.insight-panel class="overflow-hidden">
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Runtime Profile') }}</h2>
                    </div>
                    <dl class="grid gap-x-4 gap-y-2 px-4 py-3 sm:grid-cols-2">
                        @foreach ($environmentSummary as $label => $value)
                            <div wire:key="env-summary-{{ \Illuminate\Support\Str::slug($label) }}">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $label }}</dt>
                                <dd class="mt-1 text-xs font-medium text-slate-900 dark:text-white">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-admin.insight-panel>

                <x-admin.insight-panel class="overflow-hidden">
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Operator Notes') }}</h2>
                    </div>
                    @if (count($recommendedActions) > 0)
                        <ul class="space-y-1.5 px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                            @foreach ($recommendedActions as $action)
                                <li wire:key="recommended-action-{{ md5($action) }}" class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                                    <span>{{ $action }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="px-4 py-3 text-xs text-slate-500">{{ __('No immediate follow-up required.') }}</p>
                    @endif
                </x-admin.insight-panel>
            </div>
        </div>

        <div x-show="matchesPanel('ops', 'Operations Console', 'Control maintenance mode, clear framework caches, and inspect backup readiness.')">
            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-950 dark:text-white">{{ __('Operations Console') }}</h2>
                </div>
                <div class="grid gap-3 px-4 py-3 lg:items-start lg:grid-cols-3">
                    {{-- Maintenance Mode --}}
                    <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-3 dark:border-slate-700/70 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-950 dark:text-white">{{ __('Maintenance Mode') }}</h3>
                            @if ($canManageMaintenance)
                                <x-forms.switch wire:click="toggleMaintenanceMode" :checked="$maintenanceMode" size="lg"
                                    :label="__('Toggle maintenance mode')" checked-class="bg-amber-500"
                                    unchecked-class="bg-emerald-500" />
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __('Read-only') }}</span>
                            @endif
                        </div>
                        <x-admin.alert :tone="$maintenanceMode ? 'warning' : 'success'">
                            <p class="text-xs {{ $maintenanceMode ? 'text-amber-800 dark:text-amber-200' : 'text-emerald-800 dark:text-emerald-200' }}">
                                {{ $maintenanceMode ? __('App paused. Admins can still operate.') : __('Application is open to all users.') }}
                            </p>
                        </x-admin.alert>
                    </div>

                    {{-- Cache Toolkit --}}
                    <div class="rounded-lg border border-slate-200/70 bg-white p-3 dark:border-slate-700/70 dark:bg-slate-900/80">
                        <h3 class="text-xs font-bold text-slate-950 dark:text-white mb-2">{{ __('Cache Toolkit') }}</h3>
                        @if ($canManageMaintenance)
                            <x-actions.button type="button" wire:click="clearApplicationCaches" class="w-full justify-center" size="sm">
                                {{ __('Clear All Caches') }}
                            </x-actions.button>
                        @else
                            <p class="text-xs text-slate-600 dark:text-slate-300">{{ __('Read-only access') }}</p>
                        @endif
                    </div>

                    {{-- Backup Readiness --}}
                    <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-3 dark:border-slate-700/70 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-950 dark:text-white">{{ __('Backup Readiness') }}</h3>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $backupOverview['files'] }} {{ __('files') }}</span>
                        </div>
                        <dl class="grid grid-cols-3 gap-2" aria-label="{{ __('Backup Details') }}">
                            <div class="rounded-md bg-white px-2 py-1.5 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-700/70">
                                <dt class="text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Retained') }}</dt>
                                <dd class="text-xs font-bold text-slate-950 dark:text-white">{{ $backupOverview['files'] }}</dd>
                            </div>
                            <div class="rounded-md bg-white px-2 py-1.5 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-700/70">
                                <dt class="text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Storage') }}</dt>
                                <dd class="text-xs font-bold text-slate-950 dark:text-white">{{ $backupOverview['size'] }}</dd>
                            </div>
                            <div class="rounded-md bg-white px-2 py-1.5 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-700/70">
                                <dt class="text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Freshness') }}</dt>
                                <dd class="text-xs font-bold text-slate-950 dark:text-white">{{ $backupOverview['latest_age'] }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>

        <div x-show="matchesPanel('cleanup', 'Database Cleanup', 'Delete obsolete records, queue artifacts, cache rows, and managed file uploads.')">
            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Database Cleanup') }}</h2>
                </div>

                <div class="grid gap-4 px-5 py-5 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,1fr)]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($cleanupTargets as $target)
                            <label wire:key="cleanup-target-{{ $target['model'] }}" class="flex items-start gap-3 rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <x-forms.checkbox wire:model="{{ $target['model'] }}" class="mt-1" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="block text-sm font-semibold text-slate-950 dark:text-white">{{ $target['label'] }}</span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ number_format($target['count']) }} {{ $target['unit'] }}
                                        </span>
                                        @if (!empty($target['meta']))
                                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">
                                                {{ $target['meta'] }}
                                            </span>
                                        @endif
                                    </span>
                                    <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">{{ $target['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="space-y-4 rounded-xl border border-slate-200/70 bg-slate-50/70 p-4 dark:border-slate-700/70 dark:bg-slate-900/40">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Destructive Guardrail') }}</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            {{ __('Type CLEAN before executing any destructive task. Admin and superadmin accounts are never removed by the employee cleanup option.') }}
                        </p>

                        <div>
                            <x-forms.label for="cleanupConfirmation" value="{{ __('Type CLEAN to confirm') }}" />
                            <x-forms.input id="cleanupConfirmation" wire:model.defer="cleanupConfirmation"
                                class="mt-1 block w-full font-mono uppercase tracking-[0.2em]" />
                            <x-forms.input-error for="cleanupConfirmation" class="mt-2" />
                        </div>

                        <x-admin.alert tone="warning">
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                {{ __('Run a fresh SQL backup before deleting production data or queue history.') }}
                            </p>
                        </x-admin.alert>

                        @if ($canManageMaintenance)
                            <x-actions.danger-button type="button" wire:click="cleanDatabase"
                                wire:confirm="{{ __('Proceed with the selected cleanup tasks? This action cannot be undone.') }}"
                                class="w-full justify-center">
                                {{ __('Run Cleanup Tasks') }}
                            </x-actions.danger-button>
                        @else
                            <x-admin.alert tone="info">
                                <p class="text-sm">{{ __('Cleanup execution is restricted to maintenance managers.') }}</p>
                            </x-admin.alert>
                        @endif
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>

        <div x-show="matchesPanel('backup', 'Backup Center', 'Create signed SQL backups and manage retained maintenance snapshots.')" wire:poll.15s>
            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Backup Center') }}</h2>
                </div>

                <div class="grid gap-4 px-5 py-5 lg:grid-cols-[minmax(0,0.96fr)_minmax(0,1.04fr)]">
                    <div class="rounded-xl border border-slate-200/70 bg-slate-50/60 p-3.5 dark:border-slate-700/70 dark:bg-slate-900/40">
                        <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Create Backup') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            {{ __('Run direct downloads for immediate SQL export or queue longer backup jobs in the background.') }}
                        </p>

                        <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
                            <div class="rounded-lg border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Retained') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ $backupOverview['files'] }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Backup Storage') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ $backupOverview['size'] }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Latest Age') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ $backupOverview['latest_age'] }}</p>
                            </div>
                        </div>

                        @if ($canManageBackups)
                            <x-actions.button type="button" wire:click="downloadBackup" wire:loading.attr="disabled" wire:target="downloadBackup" class="mt-3 w-full justify-center">
                                {{ __('Generate and Download SQL Backup') }}
                            </x-actions.button>
                        @else
                            <x-admin.alert tone="info" class="mt-3">
                                <p class="text-sm">{{ __('Backup actions are read-only until a maintenance manager role is assigned and backup MFA requirements are satisfied.') }}</p>
                            </x-admin.alert>
                        @endif

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @if ($canManageBackups)
                                <x-actions.button type="button" variant="secondary" wire:click="queueDatabaseBackupJob" wire:loading.attr="disabled"
                                    wire:target="queueDatabaseBackupJob" class="w-full justify-center">
                                    {{ __('Queue DB Backup Job') }}
                                </x-actions.button>

                                <x-actions.button type="button" variant="secondary" wire:click="queueApplicationBackupJob" wire:loading.attr="disabled"
                                    wire:target="queueApplicationBackupJob" class="w-full justify-center">
                                    {{ __('Queue App Backup Job') }}
                                </x-actions.button>
                            @endif
                        </div>

                        <div class="mt-3 rounded-lg border border-slate-200/70 bg-white px-3.5 py-3 text-sm text-slate-600 dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300">
                            {{ __('Queued backups need an active queue worker on the `maintenance` queue. Application backups produce a ZIP snapshot of the codebase and config files, not a deployable image.') }}
                        </div>

                        @if ($latestBackup)
                            <div class="mt-3 rounded-lg border border-slate-200/70 bg-white px-3.5 py-3 text-sm text-slate-700 dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-200">
                                <p class="font-semibold">{{ __('Latest retained backup') }}</p>
                                <p class="mt-1">{{ $latestBackup['filename'] }}</p>
                                <p class="mt-1 text-xs">{{ $latestBackup['type_label'] }} · {{ $latestBackup['size_human'] }} · {{ $latestBackup['completed_at_human'] }}</p>
                            </div>
                        @endif

                        <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-lg border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Queued') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ number_format($backupJobSummary['queued']) }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Running') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ number_format($backupJobSummary['running']) }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Completed') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ number_format($backupJobSummary['completed']) }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Failed') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ number_format($backupJobSummary['failed']) }}</p>
                            </div>
                        </div>

                        <form wire:submit.prevent="saveBackupAutomationSettings" class="mt-3 space-y-3.5 rounded-xl border border-slate-200/70 bg-white p-3.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Backup Automation') }}</h4>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                        {{ __('Schedule routine backups and prune retained artifacts automatically.') }}
                                    </p>
                                </div>

                                <label class="flex items-center gap-3">
                                    <x-forms.checkbox wire:model.live="backupScheduleEnabled" />
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Automation enabled') }}</span>
                                </label>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <x-forms.label for="backupScheduleType" value="{{ __('Backup target') }}" />
                                    <x-forms.select id="backupScheduleType" wire:model.live="backupScheduleType" class="mt-1 w-full">
                                        <option value="database">{{ __('Database only') }}</option>
                                        <option value="application">{{ __('Application only') }}</option>
                                        <option value="both">{{ __('Database and application') }}</option>
                                    </x-forms.select>
                                    <x-forms.input-error for="backupScheduleType" class="mt-2" />
                                </div>

                                <div>
                                    <x-forms.label for="backupScheduleFrequency" value="{{ __('Frequency') }}" />
                                    <x-forms.select id="backupScheduleFrequency" wire:model.live="backupScheduleFrequency" class="mt-1 w-full">
                                        <option value="daily">{{ __('Daily') }}</option>
                                        <option value="weekly">{{ __('Weekly') }}</option>
                                    </x-forms.select>
                                    <x-forms.input-error for="backupScheduleFrequency" class="mt-2" />
                                </div>

                                @if ($backupScheduleFrequency === 'weekly')
                                    <div>
                                        <x-forms.label for="backupScheduleDay" value="{{ __('Run day') }}" />
                                        <x-forms.select id="backupScheduleDay" wire:model.live="backupScheduleDay" class="mt-1 w-full">
                                            <option value="monday">{{ __('Monday') }}</option>
                                            <option value="tuesday">{{ __('Tuesday') }}</option>
                                            <option value="wednesday">{{ __('Wednesday') }}</option>
                                            <option value="thursday">{{ __('Thursday') }}</option>
                                            <option value="friday">{{ __('Friday') }}</option>
                                            <option value="saturday">{{ __('Saturday') }}</option>
                                            <option value="sunday">{{ __('Sunday') }}</option>
                                        </x-forms.select>
                                        <x-forms.input-error for="backupScheduleDay" class="mt-2" />
                                    </div>
                                @endif

                                <div>
                                    <x-forms.label for="backupScheduleTime" value="{{ __('Run time') }}" />
                                    <x-forms.input id="backupScheduleTime" type="time" wire:model.defer="backupScheduleTime" class="mt-1 w-full" />
                                    <x-forms.input-error for="backupScheduleTime" class="mt-2" />
                                </div>

                                <div>
                                    <x-forms.label for="backupRetentionDays" value="{{ __('Retention days') }}" />
                                    <x-forms.input id="backupRetentionDays" type="number" min="1" max="365" wire:model.defer="backupRetentionDays" class="mt-1 w-full" />
                                    <x-forms.input-error for="backupRetentionDays" class="mt-2" />
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-200/70 bg-slate-50/80 px-3.5 py-3 text-sm text-slate-600 dark:border-slate-700/70 dark:bg-slate-950/40 dark:text-slate-300">
                                @if ($backupScheduleSummary['enabled'])
                                    <p class="font-medium text-slate-900 dark:text-white">
                                        {{ __('Next run: :time', ['time' => $backupScheduleSummary['next_run_human'] ?? __('Not available')]) }}
                                    </p>
                                    <p class="mt-1">
                                        {{ $backupScheduleSummary['type_label'] }} · {{ $backupScheduleSummary['frequency_label'] }} @ {{ $backupScheduleSummary['time'] }} · {{ __('Retention :days days', ['days' => $backupScheduleSummary['retention_days']]) }}
                                        @if ($backupScheduleSummary['next_run_relative'])
                                            · {{ $backupScheduleSummary['next_run_relative'] }}
                                        @endif
                                    </p>
                                @else
                                    <p>{{ __('Automation is disabled. Manual backups remain available, but no scheduled snapshots or retention cleanup will run.') }}</p>
                                @endif
                            </div>

                            @if ($canManageBackups)
                                <x-actions.button type="submit" class="w-full justify-center sm:w-auto">
                                    {{ __('Save Automation Policy') }}
                                </x-actions.button>
                            @endif
                        </form>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-xl border border-slate-200/70 bg-white p-3.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Retained Backups') }}</h3>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                        {{ __('Completed backup artifacts available for later download or cleanup.') }}
                                    </p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ count($backups) }} {{ __('files') }}
                                </span>
                            </div>

                            @if (count($backups) > 0)
                                <div class="mt-3 space-y-2.5">
                                    @foreach ($backups as $backup)
                                        <div wire:key="retained-backup-{{ $backup['id'] }}" class="flex flex-col gap-3 rounded-lg border border-slate-200/70 px-4 py-3 dark:border-slate-700/70 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="truncate text-sm font-semibold text-slate-950 dark:text-white">{{ $backup['filename'] }}</p>
                                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                        {{ $backup['type_label'] }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $backup['size_human'] }} · {{ $backup['completed_at_human'] }} @if ($backup['requested_by']) · {{ $backup['requested_by'] }} @endif
                                                </p>
                                            </div>

                                            @if ($canManageBackups)
                                                <div class="flex items-center gap-2">
                                                    <x-actions.icon-button wire:click="downloadExistingBackup({{ $backup['id'] }})" variant="primary"
                                                        label="{{ __('Download retained backup') }}: {{ $backup['filename'] }}">
                                                        <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                                                    </x-actions.icon-button>
                                                    <x-actions.icon-button wire:click="deleteBackup({{ $backup['id'] }})" variant="danger"
                                                        wire:confirm="{{ __('Delete this retained backup file?') }}"
                                                        label="{{ __('Delete retained backup') }}: {{ $backup['filename'] }}">
                                                        <x-heroicon-m-trash class="h-4 w-4" />
                                                    </x-actions.icon-button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-admin.empty-state :title="__('No retained backups yet')"
                                    :description="__('Generate the first SQL backup or queue a background snapshot to start backup history.')"
                                    class="py-6" />
                            @endif
                        </div>

                        <div class="rounded-xl border border-slate-200/70 bg-white p-3.5 dark:border-slate-700/70 dark:bg-slate-900/80">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Backup Job Runs') }}</h3>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                        {{ __('Recent queued backup activity across database and application snapshots.') }}
                                    </p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ count($backupRuns) }} {{ __('runs') }}
                                </span>
                            </div>

                            @if (count($backupRuns) > 0)
                                <div class="mt-3 space-y-2.5">
                                    @foreach ($backupRuns as $run)
                                        @php
                                            $statusClasses = match ($run['status']) {
                                                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                                'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                                'running' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                                                'queued' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                                'deleted' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                            };
                                        @endphp
                                        <div wire:key="backup-run-{{ $run['id'] }}" class="rounded-lg border border-slate-200/70 px-4 py-3 dark:border-slate-700/70">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $run['type_label'] }}</p>
                                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses }}">
                                                            {{ \Illuminate\Support\Str::headline($run['status']) }}
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                        {{ $run['created_at_human'] }} @if ($run['requested_by']) · {{ $run['requested_by'] }} @endif @if ($run['size_human']) · {{ $run['size_human'] }} @endif
                                                    </p>
                                                    @if ($run['file_name'])
                                                        <p class="mt-2 truncate text-sm text-slate-600 dark:text-slate-300">{{ $run['file_name'] }}</p>
                                                    @endif
                                                    @if ($run['error_message'])
                                                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $run['error_message'] }}</p>
                                                    @endif
                                                </div>

                                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $run['updated_at_human'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-admin.empty-state :title="__('No backup job runs yet')"
                                    :description="__('Queued backup activity will appear here after you submit database or application snapshot jobs.')"
                                    class="py-6" />
                            @endif
                        </div>
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>

        <div x-show="matchesPanel('restore', 'Restore Center', 'Recover the database from a signed SQL backup with explicit confirmation.')">
            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Restore Center') }}</h2>
                </div>

                <div class="grid gap-4 px-5 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,1fr)]">
                    @if ($canManageBackups)
                        <form wire:submit.prevent="restoreDatabase" class="space-y-5">
                            <x-admin.alert tone="danger">
                                <p class="text-sm text-rose-800 dark:text-rose-200">
                                    {{ __('Restoring a database will overwrite current records. Confirm the target environment and backup origin before continuing.') }}
                                </p>
                            </x-admin.alert>

                            <div>
                                <x-forms.label for="backupFile" value="{{ __('Signed SQL backup file') }}" />
                                <x-forms.file-input
                                    id="backupFile"
                                    wire:model="backupFile"
                                    accept=".sql"
                                    class="mt-1"
                                    :button-label="__('Choose signed SQL backup file')"
                                />
                                <x-forms.input-error for="backupFile" class="mt-2" />
                            </div>

                            <div>
                                <x-forms.label for="restoreConfirmation" value="{{ __('Type RESTORE to confirm') }}" />
                                <x-forms.input id="restoreConfirmation" wire:model.defer="restoreConfirmation"
                                    class="mt-1 block w-full font-mono uppercase tracking-[0.2em]" />
                                <x-forms.input-error for="restoreConfirmation" class="mt-2" />
                            </div>

                            <x-actions.danger-button type="submit" wire:loading.attr="disabled" class="w-full justify-center">
                                {{ __('Restore Database') }}
                            </x-actions.danger-button>

                            <div wire:loading wire:target="restoreDatabase" role="status" aria-live="polite"
                                class="text-sm text-slate-500">
                                {{ __('Restoring database snapshot. Keep this window open until the process completes.') }}
                            </div>
                        </form>
                    @else
                        <x-admin.alert tone="info">
                            <p class="text-sm">{{ __('Restore operations are restricted to maintenance managers with backup access.') }}</p>
                        </x-admin.alert>
                    @endif

                    <div class="rounded-xl border border-slate-200/70 bg-slate-50/60 p-4 dark:border-slate-700/70 dark:bg-slate-900/40">
                        <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Restore Requirements') }}</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                            <li>{{ __('Only `.sql` backups generated and signed by this application are accepted.') }}</li>
                            <li>{{ __('Foreign key checks are disabled only during replay and re-enabled automatically afterward.') }}</li>
                            <li>{{ __('A fresh backup is recommended immediately before every restore attempt.') }}</li>
                            <li>{{ __('The page reloads automatically after a successful restore.') }}</li>
                        </ul>
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>
    </div>
</x-admin.page-shell>
