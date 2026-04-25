@php
    $canExportAttendances = auth()->user()->can('exportAttendances');
    $canImportAttendances = auth()->user()->can('importAttendances');
    $defaultTab = $canExportAttendances ? 'export' : 'import';
@endphp

<div x-data="{ activeTab: @js($defaultTab) }">
    <x-admin.page-shell
        :title="__('Attendance Data Management')"
        :description="__('Export and import attendance data in bulk.')"
    >
        <div class="space-y-6">
            <x-admin.panel>
                <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/20">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                                {{ __('Workflow') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {{ __('Prepare an export report or import historical attendance records in one place.') }}
                            </p>
                        </div>

                        @if ($canExportAttendances || $canImportAttendances)
                        <div class="inline-flex rounded-xl bg-gray-200 p-1 dark:bg-gray-700" role="tablist" aria-label="{{ __('Workflow') }}">
                            @if ($canExportAttendances)
                            <button
                                type="button"
                                id="attendance-export-tab"
                                role="tab"
                                aria-controls="attendance-export-panel"
                                x-bind:aria-selected="(activeTab === 'export').toString()"
                                x-bind:tabindex="activeTab === 'export' ? 0 : -1"
                                @click="activeTab = 'export'"
                                :class="activeTab === 'export'
                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-600 dark:text-white'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="wcag-touch-target inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-700"
                            >
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                {{ __('Export') }}
                            </button>
                            @endif
                            @if ($canImportAttendances)
                            <button
                                type="button"
                                id="attendance-import-tab"
                                role="tab"
                                aria-controls="attendance-import-panel"
                                x-bind:aria-selected="(activeTab === 'import').toString()"
                                x-bind:tabindex="activeTab === 'import' ? 0 : -1"
                                @click="activeTab = 'import'"
                                :class="activeTab === 'import'
                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-600 dark:text-white'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="wcag-touch-target inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-700"
                            >
                                <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                                {{ __('Import') }}
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    @if (! $canExportAttendances && ! $canImportAttendances)
                        <x-admin.alert tone="warning" class="p-6">
                            {{ __('Read-only access. No import or export action is assigned for this workspace.') }}
                        </x-admin.alert>
                    @endif

                    @if ($canExportAttendances)
                    <div x-cloak x-show="activeTab === 'export'" x-transition.opacity.duration.200ms id="attendance-export-panel" role="tabpanel" aria-labelledby="attendance-export-tab" tabindex="0">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-primary-100 bg-primary-50/70 p-6 dark:border-primary-900/40 dark:bg-primary-900/10">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-primary-600 shadow-sm dark:bg-gray-800 dark:text-primary-400">
                                        <x-heroicon-o-document-chart-bar class="h-6 w-6" />
                                    </div>
                                    <h4 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">
                                        {{ __('Export Attendance Report') }}
                                    </h4>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        {{ __('Choose a date range, then optionally narrow the result by division, job title, or education before downloading the spreadsheet.') }}
                                    </p>
                                </div>

                                <x-admin.alert tone="warning" class="p-6">
                                    <h5 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">
                                        {{ __('Export Notes') }}
                                    </h5>
                                    <ul class="mt-4 space-y-2 text-sm text-amber-800 dark:text-amber-200">
                                        <li>{{ __('Use preview first if you want to verify the report scope before exporting.') }}</li>
                                        <li>{{ __('Advanced filters help reduce large datasets before generating Excel output.') }}</li>
                                    </ul>
                                </x-admin.alert>
                            </div>

                            <form wire:submit.prevent="export" class="space-y-6">
                                <x-admin.page-tools
                                    :title="__('Filter Export Dataset')"
                                    :description="__('Choose the report period first, then open advanced filters if you need a narrower export scope.')"
                                    grid-class="grid grid-cols-1 items-end gap-5 sm:grid-cols-2"
                                >
                                    <div>
                                        <label for="start_date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ __('Start Date') }}
                                        </label>
                                        <x-forms.input
                                            type="date"
                                            id="start_date"
                                            wire:model.live="start_date"
                                            class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label for="end_date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ __('End Date') }}
                                        </label>
                                        <x-forms.input
                                            type="date"
                                            id="end_date"
                                            wire:model.live="end_date"
                                            class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                        />
                                    </div>
                                </x-admin.page-tools>

                                @error('end_date')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror

                                <div x-data="{ expanded: false }" class="rounded-2xl border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-700 dark:bg-gray-900/30">
                                    <button
                                        type="button"
                                        @click="expanded = !expanded"
                                        x-bind:aria-expanded="expanded.toString()"
                                        aria-controls="attendance-advanced-filters"
                                        class="wcag-touch-target flex items-center gap-2 rounded-lg text-sm font-medium text-gray-600 transition-colors hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-primary-400 dark:focus:ring-offset-gray-900"
                                    >
                                        <x-heroicon-o-funnel class="h-4 w-4" />
                                        {{ __('Advanced Filters') }}
                                        <x-heroicon-o-chevron-down class="h-4 w-4 transition-transform" x-bind:class="{ 'rotate-180': expanded }" />
                                    </button>

                                    <div x-show="expanded" x-collapse id="attendance-advanced-filters" class="mt-5 grid gap-5 md:grid-cols-3">
                                        <div class="space-y-1.5">
                                            <label for="division" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                {{ __('Division') }}
                                            </label>
                                            <x-forms.select
                                                id="division"
                                                wire:model.live="division"
                                                class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                            >
                                                <option value="">{{ __('All Divisions') }}</option>
                                                @foreach ($divisions as $div)
                                                    <option value="{{ $div->id }}">{{ $div->name }}</option>
                                                @endforeach
                                            </x-forms.select>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label for="jobTitle" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                {{ __('Job Title') }}
                                            </label>
                                            <x-forms.select
                                                id="jobTitle"
                                                wire:model.live="job_title"
                                                class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                            >
                                                <option value="">{{ __('All Job Titles') }}</option>
                                                @foreach ($jobTitles as $job)
                                                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                                                @endforeach
                                            </x-forms.select>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label for="education" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                {{ __('Education') }}
                                            </label>
                                            <x-forms.select
                                                id="education"
                                                wire:model.live="education"
                                                class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                            >
                                                <option value="">{{ __('All Educations') }}</option>
                                                @foreach ($educations as $edu)
                                                    <option value="{{ $edu->id }}">{{ $edu->name }}</option>
                                                @endforeach
                                            </x-forms.select>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-stretch justify-end gap-3 sm:flex-row">
                                    @if ($previewing && $mode == 'export')
                                        <x-actions.secondary-button type="button" wire:click="preview" class="justify-center">
                                            <x-heroicon-o-eye class="mr-2 h-4 w-4" />
                                            {{ __('Preview') }}
                                        </x-actions.secondary-button>
                                    @endif

                                    <x-actions.button size="lg" wire:loading.attr="disabled">
                                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                        {{ __('Export') }}
                                    </x-actions.button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if ($canImportAttendances)
                    <div x-cloak x-show="activeTab === 'import'" x-transition.opacity.duration.200ms id="attendance-import-panel" role="tabpanel" aria-labelledby="attendance-import-tab" tabindex="0" style="display: none;">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-6 dark:border-gray-700 dark:bg-gray-900/40">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                                        <x-heroicon-o-document-arrow-up class="h-6 w-6" />
                                    </div>
                                    <h4 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">
                                        {{ __('Import Attendance Dataset') }}
                                    </h4>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        {{ __('Upload an Excel file to import attendance history in bulk. Use the official template to match columns and formatting.') }}
                                    </p>

                                    <x-actions.button
                                        type="button"
                                        wire:click="downloadTemplate"
                                        variant="secondary"
                                        size="sm"
                                        class="mt-5"
                                    >
                                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                        {{ __('Download Template') }}
                                    </x-actions.button>
                                </div>

                                <x-admin.alert tone="warning" class="p-6">
                                    <h5 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">
                                        {{ __('Before Uploading') }}
                                    </h5>
                                    <ul class="mt-4 space-y-2 text-sm text-amber-800 dark:text-amber-200">
                                        <li>{{ __('Keep the template headers unchanged to preserve import mapping.') }}</li>
                                        <li>{{ __('Use valid employee NIP values and avoid duplicate dates for the same employee.') }}</li>
                                        <li>{{ __('Review skipped rows after import and correct them before retrying.') }}</li>
                                    </ul>
                                </x-admin.alert>
                            </div>

                            <div class="space-y-5">
                                <form
                                    x-data="{ file: null, dragging: false }"
                                    @drop.prevent="dragging = false; file = $event.dataTransfer.files[0]; $refs.file.files = $event.dataTransfer.files; $wire.upload('file', file)"
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    wire:submit.prevent="import"
                                    class="space-y-5"
                                >
                                    <div
                                        :class="dragging ? 'border-primary-500 bg-primary-50/60 dark:bg-primary-900/10' : 'border-gray-300 dark:border-gray-600'"
                                        class="rounded-2xl border-2 border-dashed p-8 text-center transition-all duration-200"
                                    >
                                        <input type="file" class="hidden" x-ref="file" wire:model.live="file" x-on:change="file = $refs.file.files[0]">

                                        <button type="button" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900" @click="$refs.file.click()" aria-label="{{ __('Choose import file') }}">
                                            <template x-if="!file">
                                                <div>
                                                    <x-heroicon-o-cloud-arrow-up class="mx-auto h-12 w-12 text-gray-400" />
                                                    <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Click to upload or drag a file here') }}</p>
                                                    <p class="mt-1 text-xs text-gray-400">{{ __('XLSX or CSV, maximum 10MB') }}</p>
                                                </div>
                                            </template>

                                            <template x-if="file">
                                                <div>
                                                    <x-heroicon-o-check-circle class="mx-auto h-12 w-12 text-green-500" />
                                                    <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white" x-text="file.name"></p>
                                                    <p class="mt-1 text-xs text-gray-500" x-text="(file.size / 1024).toFixed(2) + ' {{ __('KB') }}'"></p>
                                                    <span class="mt-3 inline-flex rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ __('Ready to import') }}
                                                    </span>
                                                </div>
                                            </template>
                                        </button>
                                    </div>

                                    <div x-show="file" class="flex justify-between gap-3">
                                        <x-actions.button
                                            type="button"
                                            @click="file = null; $refs.file.value = null; $wire.set('file', null)"
                                            variant="secondary"
                                            size="sm"
                                        >
                                            <x-heroicon-o-x-mark class="h-4 w-4" />
                                            {{ __('Remove') }}
                                        </x-actions.button>
                                    </div>

                                    @if (\App\Helpers\Editions::reportingLocked())
                                        <x-actions.danger-button
                                            class="w-full justify-center gap-2 py-3 sm:w-auto"
                                            type="button"
                                            @click.prevent="$dispatch('feature-lock', { title: @js(__('Import Locked')), message: @js(__('Importing attendance is an Enterprise feature. Please upgrade.')) })"
                                        >
                                            {{ __('Import') }}
                                            <x-heroicon-o-lock-closed class="h-4 w-4" />
                                        </x-actions.danger-button>
                                    @else
                                        <div x-show="file" class="flex justify-end" style="display: none;">
                                            <x-actions.danger-button class="w-full justify-center gap-2 py-3 sm:w-auto" wire:click="import" wire:loading.attr="disabled" wire:target="import">
                                                <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                                                {{ __('Import') }}
                                            </x-actions.danger-button>
                                        </div>
                                    @endif
                                </form>

                                @if (!empty($importErrors))
                                    <x-admin.alert tone="danger" class="p-6">
                                        <h5 class="text-sm font-semibold text-red-800 dark:text-red-200">
                                            {{ __('Import Errors') }}
                                        </h5>
                                        <ul class="mt-3 max-h-48 list-disc space-y-1 overflow-y-auto pl-5 text-sm text-red-700 dark:text-red-300">
                                            @foreach ($importErrors as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </x-admin.alert>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </x-admin.panel>

            <div wire:poll.5s>
                <x-admin.import-export-run-list
                    :runs="$recentRuns"
                    :title="__('Attendance import/export jobs')"
                    :description="__('Large attendance imports and exports now run in the background. Progress updates here automatically.')"
                />
            </div>

            @if ($importResult)
                <x-admin.panel>
                    <div class="p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h4 class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-gray-100">
                                <x-heroicon-o-check-badge class="h-6 w-6 text-primary-500" />
                                {{ __('Import Result') }}
                            </h4>
                            <x-actions.icon-button type="button" wire:click="$set('importResult', null)"
                                variant="neutral" label="{{ __('Close import result') }}">
                                <x-heroicon-o-x-mark class="h-5 w-5" />
                            </x-actions.icon-button>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div class="rounded-xl bg-gradient-to-br from-green-400 to-green-600 p-6 text-center text-white shadow-lg">
                                <div class="text-4xl font-bold">{{ $importResult['imported'] }}</div>
                                <div class="mt-1 text-sm opacity-90">{{ __('Success') }}</div>
                            </div>
                            <div class="rounded-xl bg-gradient-to-br from-red-400 to-red-600 p-6 text-center text-white shadow-lg">
                                <div class="text-4xl font-bold">{{ $importResult['skipped'] }}</div>
                                <div class="mt-1 text-sm opacity-90">{{ __('Skipped') }}</div>
                            </div>
                        </div>

                        @if (!empty($importErrors))
                            <details class="group mt-6">
                                <summary class="flex cursor-pointer items-center gap-2 select-none text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                    <x-heroicon-o-chevron-right class="h-4 w-4 transition-transform group-open:rotate-90" />
                                    {{ __('Show Error Details') }} ({{ count($importErrors) }})
                                </summary>
                                <ul class="mt-3 max-h-40 list-disc space-y-1 overflow-y-auto rounded-lg bg-red-50 p-4 pl-5 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
                                    @foreach ($importErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                </x-admin.panel>
            @endif

            @if ($mode && $previewing)
                <x-admin.panel>
                    <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/20">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                            {{ __('Preview') . ' ' . __($mode) }}
                        </h4>
                    </div>

                    @if ($mode == 'import' && $skippedRows > 0)
                        <x-admin.alert tone="warning" class="mx-6 mt-4 rounded-r-md">
                            <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                {{ __('Warning') }}: <span class="font-bold">{{ $skippedRows }}</span>
                                {{ __('rows were skipped (Invalid NIP or Duplicate Date).') }}
                            </p>
                        </x-admin.alert>
                    @endif

                    <div class="hidden overflow-x-auto md:block">
                        @php
                            $thClass = 'px-6 py-3 text-left text-xs font-medium uppercase tracking-wider whitespace-nowrap text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-300';
                            $tdClass = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700';
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="{{ $thClass }}">{{ __('No.') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Date') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Name') }}</th>
                                    <th class="{{ $thClass }}">{{ __('NIP') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Time In') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Time Out') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Shift') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Barcode Id') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Coordinates') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Status') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Note') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Attachment') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @foreach ($attendances as $attendance)
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="{{ $tdClass }} text-center text-gray-500">{{ $loop->iteration }}</td>
                                        <td class="{{ $tdClass }}">{{ $attendance->date?->format('Y-m-d') }}</td>
                                        <td class="{{ $tdClass }} font-medium">{{ $attendance->user?->name }}</td>
                                        <td class="{{ $tdClass }} font-mono text-xs">{{ $attendance->user?->nip }}</td>
                                        <td class="{{ $tdClass }} font-mono text-xs">{{ $attendance->time_in?->format('H:i:s') }}</td>
                                        <td class="{{ $tdClass }} font-mono text-xs">{{ $attendance->time_out?->format('H:i:s') }}</td>
                                        <td class="{{ $tdClass }}">{{ $attendance->shift?->name }}</td>
                                        <td class="{{ $tdClass }} font-mono text-xs">{{ $attendance->barcode_id }}</td>
                                        <td class="{{ $tdClass }}">
                                            @if ($attendance->latitude_in && $attendance->longitude_in)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ $attendance->latitude_in }},{{ $attendance->longitude_in }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open check-in location for') }} {{ $attendance->user?->name }}" class="rounded text-xs font-semibold text-primary-600 underline hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800">{{ __('IN') }}</a>
                                            @endif
                                            @if ($attendance->latitude_out && $attendance->longitude_out)
                                                <span class="mx-1 text-gray-300">|</span>
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open check-out location for') }} {{ $attendance->user?->name }}" class="rounded text-xs font-semibold text-primary-600 underline hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800">{{ __('OUT') }}</a>
                                            @endif
                                        </td>
                                        <td class="{{ $tdClass }}">
                                            <span class="rounded px-2 py-1 text-xs {{ $attendance->status === 'present' ? 'bg-green-100 text-green-700' : ($attendance->status === 'late' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                                                {{ __($attendance->status) }}
                                            </span>
                                        </td>
                                        <td class="{{ $tdClass }}">
                                            <div class="w-48 truncate" title="{{ $attendance->note }}">{{ $attendance->note }}</div>
                                        </td>
                                        <td class="{{ $tdClass }}">
                                            @if ($attendance->attachment_url && is_string($attendance->attachment_url))
                                                <a href="{{ $attendance->attachment_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open attendance attachment for') }} {{ $attendance->user?->name }}" class="block h-10 w-10 overflow-hidden rounded border border-gray-200 transition-colors hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                                    <img src="{{ $attendance->attachment_url }}" alt="{{ __('Attendance attachment for') }} {{ $attendance->user?->name }}" class="h-full w-full object-cover">
                                                </a>
                                            @else
                                                <span class="text-gray-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-4 p-4 md:hidden">
                        @foreach ($attendances as $attendance)
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700/50">
                                <div class="mb-3 flex items-start justify-between">
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white">{{ $attendance->user?->name }}</p>
                                        <p class="font-mono text-xs text-gray-500">{{ $attendance->user?->nip }}</p>
                                    </div>
                                    <span class="rounded px-2 py-1 text-xs font-bold {{ $attendance->status === 'present' ? 'bg-green-100 text-green-700' : ($attendance->status === 'late' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700') }}">
                                        {{ __($attendance->status) }}
                                    </span>
                                </div>

                                <div class="mb-3 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs uppercase text-gray-500">{{ __('Date') }}</p>
                                        <p class="font-medium text-gray-900 dark:text-gray-200">{{ $attendance->date?->format('Y-m-d') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase text-gray-500">{{ __('Shift') }}</p>
                                        <p class="font-medium text-gray-900 dark:text-gray-200">{{ $attendance->shift?->name ?? '-' }}</p>
                                    </div>
                                </div>

                                <div class="mb-3 grid grid-cols-2 gap-3 border-t border-gray-200 pt-3 text-xs dark:border-gray-600">
                                    <div>
                                        <span class="text-gray-500">{{ __('IN') }}:</span>
                                        <span class="ml-1 font-mono font-semibold text-gray-700 dark:text-gray-300">{{ $attendance->time_in?->format('H:i') ?? '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">{{ __('OUT') }}:</span>
                                        <span class="ml-1 font-mono font-semibold text-gray-700 dark:text-gray-300">{{ $attendance->time_out?->format('H:i') ?? '-' }}</span>
                                    </div>
                                </div>

                                @if ($attendance->note || $attendance->attachment_url)
                                    <div class="flex items-center gap-2 border-t border-gray-200 pt-2 dark:border-gray-600">
                                        @if ($attendance->note)
                                            <p class="flex-1 truncate text-xs italic text-gray-600 dark:text-gray-400">{{ $attendance->note }}</p>
                                        @endif
                                        @if ($attendance->attachment_url && is_string($attendance->attachment_url))
                                            <a href="{{ $attendance->attachment_url }}" target="_blank" rel="noopener noreferrer" class="wcag-touch-target inline-flex items-center gap-1 rounded text-xs font-medium text-primary-600 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-700">
                                                <x-heroicon-o-paper-clip class="h-3 w-3" />
                                                {{ __('Attachment') }}
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-admin.panel>
            @endif
        </div>
    </x-admin.page-shell>
</div>
