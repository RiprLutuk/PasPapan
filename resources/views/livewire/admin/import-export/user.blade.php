<div x-data="{ activeTab: 'export' }">
    <x-admin.page-shell
        :title="__('Employee Data Management')"
        :description="__('Export and import employee data in bulk.')"
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
                                {{ __('Choose whether you want to export existing data or import a new file.') }}
                            </p>
                        </div>

                        <div class="inline-flex rounded-xl bg-gray-200 p-1 dark:bg-gray-700" role="tablist" aria-label="{{ __('Workflow') }}">
                            <button
                                type="button"
                                id="user-export-tab"
                                role="tab"
                                aria-controls="user-export-panel"
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
                            <button
                                type="button"
                                id="user-import-tab"
                                role="tab"
                                aria-controls="user-import-panel"
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
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <div x-cloak x-show="activeTab === 'export'" x-transition.opacity.duration.200ms id="user-export-panel" role="tabpanel" aria-labelledby="user-export-tab" tabindex="0">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                            <div class="rounded-2xl border border-primary-100 bg-primary-50/70 p-6 dark:border-primary-900/40 dark:bg-primary-900/10">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-primary-600 shadow-sm dark:bg-gray-800 dark:text-primary-400">
                                    <x-heroicon-o-document-arrow-down class="h-6 w-6" />
                                </div>
                                <h4 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ __('Export User Dataset') }}
                                </h4>
                                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    {{ __('Choose which account groups should be included in the export file. The generated spreadsheet is ready for reporting or backup purposes.') }}
                                </p>

                                <div class="mt-6 space-y-3">
                                    <div class="rounded-xl border border-white/70 bg-white/80 px-4 py-3 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-300">
                                        {{ __('Employee accounts are exported with profile and payroll-related fields.') }}
                                    </div>
                                    <div class="rounded-xl border border-white/70 bg-white/80 px-4 py-3 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-300">
                                        {{ __('Admin and Superadmin groups can be included in the same export batch.') }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <label class="group relative flex cursor-pointer flex-col rounded-2xl border border-gray-200 bg-white p-4 transition-colors hover:border-primary-300 hover:bg-primary-50/40 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-700 dark:hover:bg-primary-900/10">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Employee') }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Regular users') }}</p>
                                            </div>
                                            <x-forms.checkbox value="user" id="user" wire:model.live="groups" class="mt-0.5 rounded-full" />
                                        </div>
                                    </label>

                                    <label class="group relative flex cursor-pointer flex-col rounded-2xl border border-gray-200 bg-white p-4 transition-colors hover:border-primary-300 hover:bg-primary-50/40 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-700 dark:hover:bg-primary-900/10">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Admin') }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Managers') }}</p>
                                            </div>
                                            <x-forms.checkbox value="admin" id="admin" wire:model.live="groups" class="mt-0.5 rounded-full" />
                                        </div>
                                    </label>

                                    <label class="group relative flex cursor-pointer flex-col rounded-2xl border border-gray-200 bg-white p-4 transition-colors hover:border-primary-300 hover:bg-primary-50/40 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-700 dark:hover:bg-primary-900/10">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Superadmin') }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Full access') }}</p>
                                            </div>
                                            <x-forms.checkbox value="superadmin" id="superadmin" wire:model.live="groups" class="mt-0.5 rounded-full" />
                                        </div>
                                    </label>
                                </div>

                                @error('groups')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex justify-end">
                                    @php
                                        $lockedIcon = \App\Helpers\Editions::reportingLocked() ? ' 🔒' : '';
                                    @endphp

                                    @if (\App\Helpers\Editions::reportingLocked())
                                        <x-actions.button
                                            class="w-full justify-center gap-2 py-3 sm:w-auto"
                                            type="button"
                                            @click.prevent="$dispatch('feature-lock', { title: @js(__('Export Locked')), message: @js(__('Exporting users is an Enterprise feature. Please upgrade.')) })"
                                        >
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                            {{ __('Export') }}{{ $lockedIcon }}
                                        </x-actions.button>
                                    @else
                                        <x-actions.button wire:click="export" size="lg" class="w-full sm:w-auto">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                            {{ __('Export') }}
                                        </x-actions.button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-cloak x-show="activeTab === 'import'" x-transition.opacity.duration.200ms id="user-import-panel" role="tabpanel" aria-labelledby="user-import-tab" tabindex="0" style="display: none;">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-6 dark:border-gray-700 dark:bg-gray-900/40">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                                        <x-heroicon-o-document-arrow-up class="h-6 w-6" />
                                    </div>
                                    <h4 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">
                                        {{ __('Import User Dataset') }}
                                    </h4>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        {{ __('Upload an Excel file to create or update user records in bulk. Use the official template to avoid column mismatches.') }}
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
                                        <li>{{ __('Use the downloaded template without changing the header row.') }}</li>
                                        <li>{{ __('Prepare XLSX or CSV files with a maximum size of 10MB.') }}</li>
                                        <li>{{ __('Fix validation errors from the report, then re-import the corrected rows.') }}</li>
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

                                    @error('file')
                                        <x-admin.alert tone="danger" class="rounded-xl px-4 py-3 text-sm">
                                            {{ $message }}
                                        </x-admin.alert>
                                    @enderror

                                    <div wire:loading wire:target="import" class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                            <svg class="h-4 w-4 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            <span>{{ __('Processing import...') }}</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="animate-progress-indeterminate h-2 rounded-full bg-primary-600"></div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        @php
                                            $lockedIcon = \App\Helpers\Editions::reportingLocked() ? ' 🔒' : '';
                                        @endphp

                                        @if (\App\Helpers\Editions::reportingLocked())
                                            <x-actions.danger-button
                                                class="w-full justify-center py-3 sm:w-auto"
                                                type="button"
                                                @click.prevent="$dispatch('feature-lock', { title: @js(__('Import Locked')), message: @js(__('Importing users is an Enterprise feature. Please upgrade.')) })"
                                            >
                                                {{ __('Import') }}{{ $lockedIcon }}
                                            </x-actions.danger-button>
                                        @else
                                            <div x-show="file" style="display: none;">
                                                <x-actions.danger-button class="w-full justify-center gap-2 py-3 sm:w-auto" wire:loading.attr="disabled" wire:target="import">
                                                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                                                    {{ __('Import') }}
                                                </x-actions.danger-button>
                                            </div>
                                        @endif
                                    </div>
                                </form>

                                @if (!empty($importErrors))
                                    <x-admin.alert tone="danger" class="p-6">
                                        <div class="flex items-start gap-3">
                                            <div class="rounded-xl bg-red-100 p-2 text-red-600 dark:bg-red-900/30 dark:text-red-300">
                                                <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h5 class="text-sm font-semibold text-red-800 dark:text-red-200">
                                                    {{ __('Import Completed with Issues') }}
                                                </h5>
                                                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                                    {{ count($importErrors) }} {{ __('rows were skipped due to validation errors. Valid rows were imported successfully.') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-4 overflow-hidden rounded-xl border border-red-100 bg-white dark:border-red-900/30 dark:bg-gray-800">
                                            <table class="min-w-full divide-y divide-red-100 dark:divide-red-900/30">
                                                <thead class="bg-red-50/60 dark:bg-red-900/20">
                                                    <tr>
                                                        <th class="w-20 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-300">{{ __('Row') }}</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-300">{{ __('Error Details') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-red-100 dark:divide-red-900/30">
                                                    @foreach ($importErrors as $error)
                                                        <tr>
                                                            <td class="px-4 py-3 text-sm font-medium text-red-800 dark:text-red-200">
                                                                {{ __('Row') }} {{ $error['row'] }}
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-red-700 dark:text-red-300">
                                                                <ul class="list-disc list-inside space-y-1">
                                                                    @foreach ($error['errors'] as $msg)
                                                                        <li>{{ $msg }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </x-admin.alert>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </x-admin.panel>

            @if ($previewing && $users && $users->count() > 0)
                <x-admin.panel>
                    <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/20">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                            {{ __('Preview Data') }}
                        </h4>
                    </div>

                    <div class="overflow-x-auto">
                        @php
                            $thClass = 'px-6 py-3 text-left text-xs font-medium uppercase tracking-wider whitespace-nowrap text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-300';
                            $tdClass = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700';
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="{{ $thClass }}">#</th>
                                    <th class="{{ $thClass }}">{{ __('NIP') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Name') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Email') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Group') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Phone') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Basic Salary') }}</th>
                                    <th class="{{ $thClass }}">{{ __('Role') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @foreach ($users->take(10) as $user)
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="{{ $tdClass }} text-gray-500">{{ $loop->iteration }}</td>
                                        <td class="{{ $tdClass }} font-mono text-xs">{{ $user->nip }}</td>
                                        <td class="{{ $tdClass }} font-medium">{{ $user->name }}</td>
                                        <td class="{{ $tdClass }} text-gray-500">{{ $user->email }}</td>
                                        <td class="{{ $tdClass }}">
                                            <span class="inline-flex rounded-lg px-2 py-1 text-xs {{ $user->group === 'admin' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                                {{ __(ucfirst($user->group)) }}
                                            </span>
                                        </td>
                                        <td class="{{ $tdClass }}">{{ $user->phone }}</td>
                                        <td class="{{ $tdClass }} font-mono text-xs">{{ number_format($user->basic_salary, 0) }}</td>
                                        <td class="{{ $tdClass }}">
                                            <div class="text-xs">
                                                <div class="font-medium">{{ $user->jobTitle?->name ?? '-' }}</div>
                                                <div class="text-gray-500">{{ $user->division?->name ?? '-' }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($users->count() > 10)
                        <div class="border-t border-gray-100 px-6 py-3 text-center text-xs italic text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            {{ __('Showing first 10 rows of :count records...', ['count' => $users->count()]) }}
                        </div>
                    @endif
                </x-admin.panel>
            @endif
        </div>
    </x-admin.page-shell>
</div>
