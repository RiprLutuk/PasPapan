<x-admin.page-shell
    :title="__('Holiday Calendar')"
    :description="__('Manage public holidays and company days off.')"
>
    <x-slot name="actions">
        <x-actions.button wire:click="create" title="{{ __('Add Holiday') }}" aria-label="{{ __('Add Holiday') }}" class="h-10 w-10 justify-center !px-0 !py-0 !bg-primary-600 hover:!bg-primary-700">
            <x-heroicon-m-plus class="h-4 w-4" />
        </x-actions.button>
    </x-slot>

        <!-- Content -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Date') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Name') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Description') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Recurring') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($holidays as $holiday)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $holiday->date->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $holiday->name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    {{ $holiday->description ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($holiday->is_recurring)
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-700/50">
                                            {{ __('Yes') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                            {{ __('No') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $holiday->id }})" class="text-gray-400 hover:text-blue-600 transition-colors" title="{{ __('Edit') }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </button>
                                        <button wire:click="delete({{ $holiday->id }})" wire:confirm="{{ __('Are you sure you want to delete this holiday?') }}" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Delete') }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-calendar-days class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                        <p class="font-medium">{{ __('No holidays found') }}</p>
                                        <p class="text-sm mt-1">{{ __('Add holidays to manage work schedules.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="grid grid-cols-1 sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($holidays as $holiday)
                    <div class="p-4 space-y-2">
                        <div class="flex justify-between items-start">
                             <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $holiday->name }}</h4>
                                <p class="text-sm text-gray-500">{{ $holiday->date->translatedFormat('d M Y') }}</p>
                             </div>
                             @if($holiday->is_recurring)
                                <span class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded">{{ __('Recurring') }}</span>
                             @endif
                        </div>
                        @if($holiday->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-2 rounded">{{ $holiday->description }}</p>
                        @endif
                        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/50 mt-2">
                             <button wire:click="edit({{ $holiday->id }})" class="text-blue-600 dark:text-blue-400 text-sm font-medium">{{ __('Edit') }}</button>
                             <button wire:click="delete({{ $holiday->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 dark:text-red-400 text-sm font-medium">{{ __('Delete') }}</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                {{ $holidays->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <x-overlays.dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Holiday') : __('Add Holiday') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="save">
                <div class="space-y-4">
                     <div>
                        <x-forms.label for="date" value="{{ __('Date') }}" />
                        <x-forms.input id="date" type="date" class="mt-1 block w-full" wire:model="date" required />
                        <x-forms.input-error for="date" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="name" value="{{ __('Holiday Name') }}" />
                        <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model="name" placeholder="{{ __('e.g. Christmas Day') }}" required />
                        <x-forms.input-error for="name" class="mt-2" />
                    </div>
                     <div>
                        <x-forms.label for="description" value="{{ __('Description') }} (Optional)" />
                        <x-forms.input id="description" type="text" class="mt-1 block w-full" wire:model="description" />
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <x-forms.checkbox id="is_recurring" wire:model="is_recurring" />
                        <x-forms.label for="is_recurring" value="{{ __('Recurring yearly') }}" />
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="save" wire:loading.attr="disabled">
                {{ $editMode ? __('Update') : __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
