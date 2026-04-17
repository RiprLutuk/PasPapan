<div class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10 2xl:px-10">
    <div class="w-full">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    {{ __('Shift Management') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Configure work schedules and time slots.') }}
                </p>
            </div>
            <x-actions.button wire:click="showCreating" title="{{ __('Add Shift') }}" aria-label="{{ __('Add Shift') }}" class="h-10 w-10 justify-center !px-0 !py-0 !bg-primary-600 hover:!bg-primary-700">
                <x-heroicon-m-plus class="h-4 w-4" />
            </x-actions.button>
        </div>

        <!-- Content -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Shift Name') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Start Time') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('End Time') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($shifts as $shift)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $shift->name }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-mono">
                                    {{ $shift->start_time }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-mono">
                                    {{ $shift->end_time ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $shift->id }})"
                                            class="text-gray-400 hover:text-blue-600 transition-colors"
                                            title="{{ __('Edit') }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </button>
                                        <button
                                            wire:click="confirmDeletion({{ $shift->id }}, '{{ $shift->name }}')"
                                            class="text-gray-400 hover:text-red-600 transition-colors"
                                            title="{{ __('Delete') }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-clock class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                        <p class="font-medium">{{ __('No shifts found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="grid grid-cols-1 sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($shifts as $shift)
                    <div class="p-4 space-y-2">
                        <div class="flex justify-between items-start">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $shift->name }}</h4>
                            <span
                                class="text-xs font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $shift->start_time }}
                                - {{ $shift->end_time ?? '?' }}</span>
                        </div>
                        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/50 mt-2">
                            <button wire:click="edit({{ $shift->id }})"
                                class="text-blue-600 dark:text-blue-400 text-sm font-medium">Edit</button>
                            <button wire:click="confirmDeletion({{ $shift->id }}, '{{ $shift->name }}')"
                                class="text-red-600 dark:text-red-400 text-sm font-medium">Delete</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modals -->
    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Shift') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?</x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete"
                wire:loading.attr="disabled">{{ __('Confirm Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">{{ __('New Shift') }}</x-slot>
        <x-slot name="content">
            <form wire:submit="create">
                <div class="space-y-4">
                    <div>
                        <x-forms.label for="create_name" value="{{ __('Shift Name') }}" />
                        <x-forms.input id="create_name" class="mt-1 block w-full" type="text" wire:model="form.name" />
                        <x-forms.input-error for="form.name" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="create_start_time" value="{{ __('Start Time') }}" />
                            <x-forms.input id="create_start_time" class="mt-1 block w-full" type="time"
                                wire:model="form.start_time" />
                            <x-forms.input-error for="form.start_time" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_end_time" value="{{ __('End Time') }}" />
                            <x-forms.input id="create_end_time" class="mt-1 block w-full" type="time"
                                wire:model="form.end_time" />
                            <x-forms.input-error for="form.end_time" class="mt-2" />
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('creating')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="create" wire:loading.attr="disabled">{{ __('Save') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model="editing">
        <x-slot name="title">{{ __('Edit Shift') }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="update">
                <div class="space-y-4">
                    <div>
                        <x-forms.label for="edit_name" value="{{ __('Shift Name') }}" />
                        <x-forms.input id="edit_name" class="mt-1 block w-full" type="text" wire:model="form.name" />
                        <x-forms.input-error for="form.name" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="edit_start_time" value="{{ __('Start Time') }}" />
                            <x-forms.input id="edit_start_time" class="mt-1 block w-full" type="time"
                                wire:model="form.start_time" />
                            <x-forms.input-error for="form.start_time" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_end_time" value="{{ __('End Time') }}" />
                            <x-forms.input id="edit_end_time" class="mt-1 block w-full" type="time"
                                wire:model="form.end_time" />
                            <x-forms.input-error for="form.end_time" class="mt-2" />
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('editing')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="update"
                wire:loading.attr="disabled">{{ __('Update') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
