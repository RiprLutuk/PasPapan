<div>
    <x-admin.page-shell :title="__('Shift Management')" :description="__('Configure work schedules, overnight coverage, and flexible time windows for your team.')">
        <x-slot name="actions">
            <x-actions.button wire:click="showCreating" label="{{ __('Add Shift') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
                <span>{{ __('Add Shift') }}</span>
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-slot name="actions">
                    @if ($hasFilters)
                        <x-actions.button type="button" wire:click="resetFilters" variant="ghost"
                            label="{{ __('Reset filters') }}">
                            {{ __('Reset Filters') }}
                        </x-actions.button>
                    @endif
                </x-slot>

                <div class="lg:col-span-2">
                    <x-forms.label for="shift-search" value="{{ __('Search shifts') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <x-forms.input id="shift-search" type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by shift name...') }}" class="w-full pl-11" />
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <x-forms.label for="shift-type-filter" value="{{ __('Shift type') }}" class="mb-1.5 block" />
                    <x-forms.select id="shift-type-filter" wire:model.live="typeFilter" class="w-full">
                        <option value="all">{{ __('All shifts') }}</option>
                        <option value="daytime">{{ __('Daytime') }}</option>
                        <option value="overnight">{{ __('Overnight') }}</option>
                        <option value="open-ended">{{ __('Open-ended') }}</option>
                    </x-forms.select>
                </div>

                <div class="lg:col-span-1">
                    <x-forms.label for="shift-per-page" value="{{ __('Rows per page') }}" class="mb-1.5 block" />
                    <x-forms.select id="shift-per-page" wire:model.live="perPage" class="w-full">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </x-forms.select>
                </div>
            </x-admin.page-tools>
        </x-slot>

        <x-admin.panel>
            <div
                class="flex flex-col gap-2 border-b border-gray-200/70 px-4 py-3 dark:border-gray-700/70 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Shift Directory') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        @if ($shifts->count())
                            {{ __('Showing :from-:to of :total shifts.', ['from' => $shifts->firstItem(), 'to' => $shifts->lastItem(), 'total' => $shifts->total()]) }}
                        @else
                            {{ __('No shifts match the current selection.') }}
                        @endif
                    </p>
                </div>

                @if ($typeFilter !== 'all')
                    <div class="flex flex-wrap items-center gap-2">
                        <x-admin.status-badge
                            tone="info">{{ ucfirst(str_replace('-', ' ', $typeFilter)) }}</x-admin.status-badge>
                    </div>
                @endif
            </div>

            @if ($shifts->count())
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Shift') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Time Window') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Duration') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($shifts as $shift)
                                <tr wire:key="shift-row-{{ $shift->id }}"
                                    class="group transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ $shift->name }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('Updated :time', ['time' => $shift->updated_at?->diffForHumans() ?? __('recently')]) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono text-sm font-medium text-slate-700 dark:text-slate-200">
                                            {{ $shift->formatted_start_time }}
                                            <span class="text-slate-400">-</span>
                                            {{ $shift->formatted_end_time ?? __('Flexible') }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            @if ($shift->shift_type === 'overnight')
                                                {{ __('Ends on the next day.') }}
                                            @elseif ($shift->shift_type === 'open-ended')
                                                {{ __('End time can be decided operationally.') }}
                                            @else
                                                {{ __('Same-day shift coverage.') }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($shift->duration_label)
                                            <span
                                                class="font-semibold text-slate-900 dark:text-white">{{ $shift->duration_label }}</span>
                                        @else
                                            <span
                                                class="text-slate-500 dark:text-slate-400">{{ __('Open ended') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($shift->shift_type === 'overnight')
                                            <x-admin.status-badge
                                                tone="warning">{{ __('Overnight') }}</x-admin.status-badge>
                                        @elseif ($shift->shift_type === 'open-ended')
                                            <x-admin.status-badge
                                                tone="accent">{{ __('Open-ended') }}</x-admin.status-badge>
                                        @else
                                            <x-admin.status-badge
                                                tone="success">{{ __('Daytime') }}</x-admin.status-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <x-actions.icon-button wire:click="edit({{ $shift->id }})"
                                                variant="primary" label="{{ __('Edit shift') }}: {{ $shift->name }}">
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button
                                                wire:click="confirmDeletion({{ $shift->id }})"
                                                variant="danger"
                                                label="{{ __('Delete shift') }}: {{ $shift->name }}">
                                                <x-heroicon-m-trash class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 lg:hidden">
                    @foreach ($shifts as $shift)
                        <div wire:key="shift-card-{{ $shift->id }}" class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-semibold text-slate-950 dark:text-white">
                                        {{ $shift->name }}</h3>
                                    <p class="mt-1 text-sm font-mono text-slate-500 dark:text-slate-400">
                                        {{ $shift->formatted_start_time }}
                                        <span class="text-slate-400">-</span>
                                        {{ $shift->formatted_end_time ?? __('Flexible') }}
                                    </p>
                                </div>

                                @if ($shift->shift_type === 'overnight')
                                    <x-admin.status-badge tone="warning">{{ __('Overnight') }}</x-admin.status-badge>
                                @elseif ($shift->shift_type === 'open-ended')
                                    <x-admin.status-badge tone="accent">{{ __('Open-ended') }}</x-admin.status-badge>
                                @else
                                    <x-admin.status-badge tone="success">{{ __('Daytime') }}</x-admin.status-badge>
                                @endif
                            </div>

                            <div class="mt-4 grid gap-3">
                                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/60">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ __('Duration') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $shift->duration_label ?? __('Open ended') }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-4 flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700/50">
                                <x-actions.button type="button" wire:click="edit({{ $shift->id }})"
                                    variant="soft-primary" size="sm"
                                    label="{{ __('Edit shift') }}: {{ $shift->name }}">
                                    {{ __('Edit') }}
                                </x-actions.button>
                                <x-actions.button type="button"
                                    wire:click="confirmDeletion({{ $shift->id }})"
                                    variant="soft-danger" size="sm"
                                    label="{{ __('Delete shift') }}: {{ $shift->name }}">
                                    {{ __('Delete') }}
                                </x-actions.button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($shifts->hasPages())
                    <div
                        class="border-t border-gray-200/60 bg-gray-50/70 px-4 py-2.5 dark:border-gray-700/60 dark:bg-gray-900/40">
                        {{ $shifts->onEachSide(1)->links() }}
                    </div>
                @endif
            @else
                <x-admin.empty-state :title="$hasFilters ? __('No matching shifts found') : __('No shifts have been created yet')" :description="$hasFilters
                    ? __('Try changing the keyword or shift type filter to see more results.')
                    : __('Create your first shift to standardize attendance windows for your team.')"
                    class="m-4 border-0 bg-transparent p-4 shadow-none dark:bg-transparent">
                    <x-slot name="icon">
                        <x-heroicon-o-clock class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                    </x-slot>

                    <x-slot name="actions">
                        @if ($hasFilters)
                            <x-actions.button type="button" wire:click="resetFilters" variant="secondary">
                                {{ __('Reset Filters') }}
                            </x-actions.button>
                        @else
                            <x-actions.button type="button" wire:click="showCreating">
                                {{ __('Create Shift') }}
                            </x-actions.button>
                        @endif
                    </x-slot>
                </x-admin.empty-state>
            @endif
        </x-admin.panel>
    </x-admin.page-shell>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Shift') }}</x-slot>
        <x-slot name="content">
            {{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('This action removes the shift from the master list. Make sure it is no longer needed in scheduling rules.') }}
            </p>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
                {{ __('Confirm Delete') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="showFormModal">
        <x-slot name="title">
            {{ $editing ? __('Edit Shift') : __('New Shift') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit.prevent="{{ $editing ? 'update' : 'create' }}">
                <div class="space-y-5">
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                        <p class="font-medium text-slate-900 dark:text-white">{{ __('Shift setup guidance') }}</p>
                        <p class="mt-1">
                            {{ __('Leave the end time blank for flexible shifts. If the end time is earlier than the start time, the system will treat it as an overnight shift.') }}
                        </p>
                    </div>

                    <div>
                        <x-forms.label for="shift_name" value="{{ __('Shift Name') }}" />
                        <x-forms.input id="shift_name" class="mt-1 block w-full" type="text"
                            wire:model="form.name" placeholder="{{ __('Example: Morning Shift') }}" />
                        <x-forms.input-error for="form.name" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-forms.label for="shift_start_time" value="{{ __('Start Time') }}" />
                            <x-forms.input id="shift_start_time" class="mt-1 block w-full" type="time"
                                wire:model="form.start_time" />
                            <x-forms.input-error for="form.start_time" class="mt-2" />
                        </div>

                        <div>
                            <x-forms.label for="shift_end_time" value="{{ __('End Time') }}" />
                            <x-forms.input id="shift_end_time" class="mt-1 block w-full" type="time"
                                wire:model="form.end_time" />
                            <x-forms.input-error for="form.end_time" class="mt-2" />
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="closeFormModal" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="{{ $editing ? 'update' : 'create' }}"
                wire:loading.attr="disabled">
                {{ $editing ? __('Update Shift') : __('Save Shift') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
