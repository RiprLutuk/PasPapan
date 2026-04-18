<div>
    <x-admin.page-shell
        :title="__('Divisions')"
        :description="__('Manage company divisions and departments.')"
    >
    <x-slot name="actions">
        <x-actions.button wire:click="showCreating" size="icon" label="{{ __('Add Division') }}">
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
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Division Name') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($divisions as $division)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $division->name }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="edit({{ $division->id }})" variant="primary" label="{{ __('Edit division') }}: {{ $division->name }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="confirmDeletion({{ $division->id }}, @js($division->name))" variant="danger" label="{{ __('Delete division') }}: {{ $division->name }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-building-office
                                            class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                        <p class="font-medium">{{ __('No divisions found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="grid grid-cols-1 sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($divisions as $division)
                    <div class="p-4 flex justify-between items-center group">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $division->name }}</span>
                        <div class="flex items-center gap-3">
                            <x-actions.icon-button wire:click="edit({{ $division->id }})" variant="primary" label="{{ __('Edit division') }}: {{ $division->name }}">
                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                            </x-actions.icon-button>
                            <x-actions.icon-button wire:click="confirmDeletion({{ $division->id }}, @js($division->name))" variant="danger" label="{{ __('Delete division') }}: {{ $division->name }}">
                                <x-heroicon-m-trash class="h-5 w-5" />
                            </x-actions.icon-button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.panel>
    </x-admin.page-shell>

    <!-- Modals (Retaining functionality) -->
    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Division') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?</x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete"
                wire:loading.attr="disabled">{{ __('Confirm Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">{{ __('New Division') }}</x-slot>
        <x-slot name="content">
            <form wire:submit="create">
                <div>
                    <x-forms.label for="create_name" value="{{ __('Division Name') }}" />
                    <x-forms.input id="create_name" class="mt-1 block w-full" type="text" wire:model="name" />
                    <x-forms.input-error for="name" class="mt-2" />
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('creating')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="create"
                wire:loading.attr="disabled">{{ __('Save') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model="editing">
        <x-slot name="title">{{ __('Edit Division') }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="update">
                <div>
                    <x-forms.label for="edit_name" value="{{ __('Division Name') }}" />
                    <x-forms.input id="edit_name" class="mt-1 block w-full" type="text" wire:model="name" />
                    <x-forms.input-error for="name" class="mt-2" />
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
