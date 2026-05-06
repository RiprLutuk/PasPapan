<div>
    <x-admin.page-shell :title="__('Education Levels')" :description="__('Manage education levels (SMA, D3, S1, etc).')">
        <x-slot name="actions">
            <x-actions.button wire:click="showCreating" label="{{ __('Add Education') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
                <span>{{ __('Add Education') }}</span>
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-forms.label for="education-search" value="{{ __('Search education') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <x-forms.input id="education-search" type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by education name...') }}" class="w-full pl-11" />
                    </div>
                </div>

                <div>
                    <x-forms.label for="education-per-page" value="{{ __('Rows per page') }}" class="mb-1.5 block" />
                    <x-forms.select id="education-per-page" wire:model.live="perPage" class="w-full">
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
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Education Directory') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        @if ($educations->count())
                            {{ __('Showing :from-:to of :total education levels.', ['from' => $educations->firstItem(), 'to' => $educations->lastItem(), 'total' => $educations->total()]) }}
                        @else
                            {{ __('No education levels match the current selection.') }}
                        @endif
                    </p>
                </div>

                <x-admin.status-badge tone="primary">{{ __('Master data') }}</x-admin.status-badge>
            </div>

            @if ($educations->count())
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Level Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($educations as $education)
                                <tr class="group transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            {{ $education->name }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('Available for employee data mapping.') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <x-actions.icon-button wire:click="edit({{ $education->id }})"
                                                variant="primary"
                                                label="{{ __('Edit education level') }}: {{ $education->name }}">
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button
                                                wire:click="confirmDeletion({{ $education->id }})"
                                                variant="danger"
                                                label="{{ __('Delete education level') }}: {{ $education->name }}">
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
                    @foreach ($educations as $education)
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-semibold text-slate-950 dark:text-white">
                                        {{ $education->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ __('Available for employee data mapping.') }}</p>
                                </div>

                                <x-admin.status-badge tone="primary">{{ __('Level') }}</x-admin.status-badge>
                            </div>

                            <div
                                class="mt-4 flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700/50">
                                <x-actions.button type="button" wire:click="edit({{ $education->id }})"
                                    variant="soft-primary" size="sm"
                                    label="{{ __('Edit education level') }}: {{ $education->name }}">
                                    {{ __('Edit') }}
                                </x-actions.button>
                                <x-actions.button type="button"
                                    wire:click="confirmDeletion({{ $education->id }})"
                                    variant="soft-danger" size="sm"
                                    label="{{ __('Delete education level') }}: {{ $education->name }}">
                                    {{ __('Delete') }}
                                </x-actions.button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($educations->hasPages())
                    <div
                        class="border-t border-gray-200/60 bg-gray-50/70 px-4 py-2.5 dark:border-gray-700/60 dark:bg-gray-900/40">
                        {{ $educations->onEachSide(1)->links() }}
                    </div>
                @endif
            @else
                <x-admin.empty-state :title="filled($search) ? __('No matching education levels found') : __('No education levels found')" :description="filled($search)
                    ? __('Try changing the keyword to see more results.')
                    : __('Create education options to keep employee records standardized.')"
                    class="m-4 border-0 bg-transparent p-4 shadow-none dark:bg-transparent">
                    <x-slot name="icon">
                        <x-heroicon-o-academic-cap class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                    </x-slot>

                    <x-slot name="actions">
                        <x-actions.button type="button" wire:click="showCreating">
                            {{ __('Create Education') }}
                        </x-actions.button>
                    </x-slot>
                </x-admin.empty-state>
            @endif
        </x-admin.panel>
    </x-admin.page-shell>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Education') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?</x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete"
                wire:loading.attr="disabled">{{ __('Confirm Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">{{ __('New Education') }}</x-slot>
        <x-slot name="content">
            <form wire:submit="create">
                <div>
                    <x-forms.label for="create_name" value="{{ __('Education Name') }}" />
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
        <x-slot name="title">{{ __('Edit Education') }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="update">
                <div>
                    <x-forms.label for="edit_name" value="{{ __('Education Name') }}" />
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
