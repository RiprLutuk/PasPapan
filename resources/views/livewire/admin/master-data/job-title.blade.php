<div>
    <x-admin.page-shell
        :title="__('Job Titles & Ranks')"
        :description="__('Define job titles, levels, and approval hierarchies.')"
    >
        <x-slot name="actions">
            <x-actions.button wire:click="showCreating" label="{{ __('Add Job Title') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
                <span>{{ __('Add Job Title') }}</span>
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">

                <div class="lg:col-span-2">
                    <x-forms.label for="job-title-search" value="{{ __('Search job titles') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <x-forms.input
                            id="job-title-search"
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by title, division, or level...') }}"
                            class="w-full pl-11"
                        />
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <x-forms.label for="job-title-division-filter" value="{{ __('Division') }}" class="mb-1.5 block" />
                    <x-forms.select id="job-title-division-filter" wire:model.live="divisionFilter" class="w-full">
                        <option value="all">{{ __('All divisions') }}</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </x-forms.select>
                </div>

                <div class="lg:col-span-1">
                    <x-forms.label for="job-title-per-page" value="{{ __('Rows per page') }}" class="mb-1.5 block" />
                    <x-forms.select id="job-title-per-page" wire:model.live="perPage" class="w-full">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </x-forms.select>
                </div>
            </x-admin.page-tools>
        </x-slot>

        <x-admin.panel>
            <div class="flex flex-col gap-2 border-b border-gray-200/70 px-6 py-5 dark:border-gray-700/70 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Job Title Directory') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        @if ($jobTitles->count())
                            {{ __('Showing :from-:to of :total job titles.', ['from' => $jobTitles->firstItem(), 'to' => $jobTitles->lastItem(), 'total' => $jobTitles->total()]) }}
                        @else
                            {{ __('No job titles match the current selection.') }}
                        @endif
                    </p>
                </div>

                <x-admin.status-badge tone="primary">{{ __('Master data') }}</x-admin.status-badge>
            </div>

            @if ($jobTitles->count())
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Job Title') }}</th>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Division') }}</th>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Level / Rank') }}</th>
                                <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($jobTitles as $jobTitle)
                                <tr class="group transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ $jobTitle->name }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Approval and organization structure reference.') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                        {{ $jobTitle->division->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($jobTitle->jobLevel)
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                                {{ $jobTitle->jobLevel->rank == 1
                                                    ? 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30'
                                                    : ($jobTitle->jobLevel->rank == 2
                                                        ? 'bg-indigo-50 text-indigo-700 ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-400 dark:ring-indigo-400/30'
                                                        : ($jobTitle->jobLevel->rank == 3
                                                            ? 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30'
                                                            : 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20')) }}">
                                                {{ $jobTitle->jobLevel->name }} ({{ __('Rank') }} {{ $jobTitle->jobLevel->rank }})
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <x-actions.icon-button wire:click="edit({{ $jobTitle->id }})" variant="primary" label="{{ __('Edit job title') }}: {{ $jobTitle->name }}">
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button wire:click="confirmDeletion({{ $jobTitle->id }}, @js($jobTitle->name))" variant="danger" label="{{ __('Delete job title') }}: {{ $jobTitle->name }}">
                                                <x-heroicon-m-trash class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:hidden">
                    @foreach ($jobTitles as $jobTitle)
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ $jobTitle->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $jobTitle->division->name ?? '-' }}</p>
                                </div>

                                @if ($jobTitle->jobLevel)
                                    <x-admin.status-badge tone="info">{{ __('Rank') }} {{ $jobTitle->jobLevel->rank }}</x-admin.status-badge>
                                @endif
                            </div>

                            @if ($jobTitle->jobLevel)
                                <div class="mt-4 rounded-xl bg-slate-50 p-3 dark:bg-slate-900/60">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Level') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $jobTitle->jobLevel->name }}
                                    </p>
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700/50">
                                <x-actions.button type="button" wire:click="edit({{ $jobTitle->id }})" variant="soft-primary" size="sm" label="{{ __('Edit job title') }}: {{ $jobTitle->name }}">{{ __('Edit') }}</x-actions.button>
                                <x-actions.button type="button" wire:click="confirmDeletion({{ $jobTitle->id }}, @js($jobTitle->name))" variant="soft-danger" size="sm" label="{{ __('Delete job title') }}: {{ $jobTitle->name }}">{{ __('Delete') }}</x-actions.button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($jobTitles->hasPages())
                    <div class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                        {{ $jobTitles->onEachSide(1)->links() }}
                    </div>
                @endif
            @else
                <x-admin.empty-state
                    :title="filled($search) || $divisionFilter !== 'all' ? __('No matching job titles found') : __('No job titles found')"
                    :description="filled($search) || $divisionFilter !== 'all'
                        ? __('Try changing the keyword or division filter to see more results.')
                        : __('Add job titles to define roles, levels, and approval structures.')"
                    class="m-6 border-0 bg-transparent p-6 shadow-none dark:bg-transparent"
                >
                    <x-slot name="icon">
                        <x-heroicon-o-briefcase class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                    </x-slot>

                    <x-slot name="actions">
                        <x-actions.button type="button" wire:click="showCreating">
                            {{ __('Create Job Title') }}
                        </x-actions.button>
                    </x-slot>
                </x-admin.empty-state>
            @endif
        </x-admin.panel>
    </x-admin.page-shell>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Job Title') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?</x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">{{ __('Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">{{ __('Create Job Title') }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="create">
                <div class="space-y-4">
                    <div>
                        <x-forms.label for="name" value="{{ __('Name') }}" />
                        <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model.defer="name" />
                        <x-forms.input-error for="name" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="division_id" value="{{ __('Division') }}" />
                        <div class="mt-1">
                            <x-forms.tom-select id="division_id" wire:model.defer="division_id"
                                placeholder="{{ __('Select Division') }}" :options="$divisions->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
                        </div>
                        <x-forms.input-error for="division_id" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="job_level_id" value="{{ __('Job Level') }}" />
                        <div class="mt-1">
                            <x-forms.tom-select id="job_level_id" wire:model.defer="job_level_id"
                                placeholder="{{ __('Select Level') }}" :options="$jobLevels->map(
                                    fn($l) => ['id' => $l->id, 'name' => $l->name . ' (Rank ' . $l->rank . ')'],
                                )" />
                        </div>
                        <x-forms.input-error for="job_level_id" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Rank 1 (Highest) to 4 (Lowest).') }}</p>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('creating', false)" wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="create" wire:loading.attr="disabled">{{ __('Save') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model="editing">
        <x-slot name="title">{{ __('Edit Job Title') }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="update">
                <div class="space-y-4">
                    <div>
                        <x-forms.label for="edit_name" value="{{ __('Name') }}" />
                        <x-forms.input id="edit_name" type="text" class="mt-1 block w-full" wire:model.defer="name" />
                        <x-forms.input-error for="name" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="edit_division_id" value="{{ __('Division') }}" />
                        <div class="mt-1">
                            <x-forms.tom-select id="edit_division_id" wire:model.defer="division_id"
                                placeholder="{{ __('Select Division') }}" :options="$divisions->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
                        </div>
                        <x-forms.input-error for="division_id" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="edit_job_level_id" value="{{ __('Job Level') }}" />
                        <div class="mt-1">
                            <x-forms.tom-select id="edit_job_level_id" wire:model.defer="job_level_id"
                                placeholder="{{ __('Select Level') }}" :options="$jobLevels->map(
                                    fn($l) => ['id' => $l->id, 'name' => $l->name . ' (Rank ' . $l->rank . ')'],
                                )" />
                        </div>
                        <x-forms.input-error for="job_level_id" class="mt-2" />
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('editing', false)" wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="update" wire:loading.attr="disabled">{{ __('Update') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
