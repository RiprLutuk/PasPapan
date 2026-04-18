<div>
    <x-admin.page-shell
        :title="__('Job Titles & Ranks')"
        :description="__('Define job titles, levels, and approval hierarchies.')"
    >
    <x-slot name="actions">
        <x-actions.button wire:click="showCreating" size="icon" label="{{ __('Add Job Title') }}">
            <x-heroicon-m-plus class="h-5 w-5" />
        </x-actions.button>
    </x-slot>

        <!-- Content -->
        <x-admin.panel>
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
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
                        @forelse ($jobTitles as $jobTitle)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $jobTitle->name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
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
                                            {{ $jobTitle->jobLevel->name }} (Rank {{ $jobTitle->jobLevel->rank }})
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
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-briefcase
                                            class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                        <p class="font-medium">{{ __('No job titles found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="grid grid-cols-1 sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($jobTitles as $jobTitle)
                    <div class="p-4 space-y-2">
                        <div class="flex justify-between items-start">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $jobTitle->name }}</h4>
                            @if ($jobTitle->jobLevel)
                                <span
                                    class="text-xs font-semibold bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">Rank
                                    {{ $jobTitle->jobLevel->rank }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500">{{ $jobTitle->division->name ?? '-' }}</p>
                        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/50 mt-2">
                            <x-actions.button type="button" wire:click="edit({{ $jobTitle->id }})" variant="soft-primary" size="sm" label="{{ __('Edit job title') }}: {{ $jobTitle->name }}">{{ __('Edit') }}</x-actions.button>
                            <x-actions.button type="button" wire:click="confirmDeletion({{ $jobTitle->id }}, @js($jobTitle->name))" variant="soft-danger" size="sm" label="{{ __('Delete job title') }}: {{ $jobTitle->name }}">{{ __('Delete') }}</x-actions.button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.panel>
    </x-admin.page-shell>

    <!-- Modals -->
    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Job Title') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?</x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete"
                wire:loading.attr="disabled">{{ __('Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <!-- Create/Edit Modal -->
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
            <x-actions.secondary-button wire:click="$set('creating', false)"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
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
            <x-actions.secondary-button wire:click="$set('editing', false)"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="update" wire:loading.attr="disabled">{{ __('Update') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
