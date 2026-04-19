<x-admin.page-shell :title="__('Holiday Calendar')" :description="__('Manage public holidays and company days off.')">
    <x-slot name="actions">
        <x-actions.button wire:click="create" size="icon" label="{{ __('Add Holiday') }}">
            <x-heroicon-m-plus class="h-5 w-5" />
        </x-actions.button>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools>

            <div class="md:col-span-2 xl:col-span-6">
                <x-forms.label for="holiday-search" value="{{ __('Search holidays') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    <x-forms.input id="holiday-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search holiday name or description...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="holiday-month-filter" value="{{ __('Month') }}" class="mb-1.5 block" />
                <x-forms.select id="holiday-month-filter" wire:model.live="monthFilter" class="w-full">
                    <option value="all">{{ __('All months') }}</option>
                    @foreach (range(1, 12) as $month)
                        <option value="{{ $month }}">
                            {{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }}</option>
                    @endforeach
                </x-forms.select>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="holiday-recurring-filter" value="{{ __('Recurrence') }}" class="mb-1.5 block" />
                <x-forms.select id="holiday-recurring-filter" wire:model.live="recurringFilter" class="w-full">
                    <option value="all">{{ __('All holidays') }}</option>
                    <option value="recurring">{{ __('Recurring yearly') }}</option>
                    <option value="one_time">{{ __('One-time only') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <!-- Content -->
    <x-admin.panel>
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
                                @if ($holiday->is_recurring)
                                    <x-admin.status-badge tone="accent">{{ __('Yes') }}</x-admin.status-badge>
                                @else
                                    <x-admin.status-badge tone="neutral">{{ __('No') }}</x-admin.status-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <x-actions.icon-button wire:click="edit({{ $holiday->id }})" variant="primary"
                                        label="{{ __('Edit holiday') }}: {{ $holiday->name }}">
                                        <x-heroicon-m-pencil-square class="h-5 w-5" />
                                    </x-actions.icon-button>
                                    <x-actions.icon-button wire:click="delete({{ $holiday->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this holiday?') }}"
                                        variant="danger" label="{{ __('Delete holiday') }}: {{ $holiday->name }}">
                                        <x-heroicon-m-trash class="h-5 w-5" />
                                    </x-actions.icon-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <x-heroicon-o-calendar-days
                                        class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
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
                        @if ($holiday->is_recurring)
                            <x-admin.status-badge tone="accent">{{ __('Recurring') }}</x-admin.status-badge>
                        @endif
                    </div>
                    @if ($holiday->description)
                        <p class="text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-2 rounded">
                            {{ $holiday->description }}</p>
                    @endif
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/50 mt-2">
                        <x-actions.button type="button" wire:click="edit({{ $holiday->id }})" variant="soft-primary"
                            size="sm"
                            label="{{ __('Edit holiday') }}: {{ $holiday->name }}">{{ __('Edit') }}</x-actions.button>
                        <x-actions.button type="button" wire:click="delete({{ $holiday->id }})"
                            wire:confirm="{{ __('Are you sure?') }}" variant="soft-danger" size="sm"
                            label="{{ __('Delete holiday') }}: {{ $holiday->name }}">{{ __('Delete') }}</x-actions.button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
            {{ $holidays->links() }}
        </div>
    </x-admin.panel>

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
                        <x-forms.input id="date" type="date" class="mt-1 block w-full" wire:model="date"
                            required />
                        <x-forms.input-error for="date" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="name" value="{{ __('Holiday Name') }}" />
                        <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model="name"
                            placeholder="{{ __('e.g. Christmas Day') }}" required />
                        <x-forms.input-error for="name" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="description" value="{{ __('Description') }} (Optional)" />
                        <x-forms.input id="description" type="text" class="mt-1 block w-full"
                            wire:model="description" />
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
