<div>
    <x-admin.page-shell :title="__('Leave Types')" :description="__('Manage annual leave, sick leave, and special leave categories.')">
        <x-slot name="actions">
            <x-actions.button wire:click="showCreating" label="{{ __('Add Leave Type') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
                <span>{{ __('Add Leave Type') }}</span>
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <x-forms.label for="leave-type-search" value="{{ __('Search leave types') }}" class="mb-1.5 block" />
                    <x-forms.input id="leave-type-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search name, code, or description...') }}" class="w-full" />
                </div>

                <div class="lg:col-span-2">
                    <x-forms.label for="leave-type-per-page" value="{{ __('Rows per page') }}" class="mb-1.5 block" />
                    <x-forms.select id="leave-type-per-page" wire:model.live="perPage" class="w-full">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </x-forms.select>
                </div>
            </x-admin.page-tools>
        </x-slot>

        <x-admin.panel>
            <div class="border-b border-gray-200/70 px-6 py-5 dark:border-gray-700/70">
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Leave Type Directory') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Annual leave can use quota. Sick and special leave types do not reduce sick quota because sick quota is no longer enforced.') }}
                </p>
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Name') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Behavior') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($leaveTypes as $leaveType)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $leaveType->name }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $leaveType->code }}</div>
                                    @if ($leaveType->description)
                                        <div class="mt-1 max-w-md truncate text-xs text-slate-500 dark:text-slate-400">{{ $leaveType->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <x-admin.status-badge :tone="$leaveType->category === \App\Models\LeaveType::CATEGORY_SICK ? 'warning' : ($leaveType->counts_against_quota ? 'primary' : 'info')">
                                            {{ $categories[$leaveType->category] ?? $leaveType->category }}
                                        </x-admin.status-badge>
                                        @if ($leaveType->counts_against_quota)
                                            <x-admin.status-badge tone="success">{{ __('Uses quota') }}</x-admin.status-badge>
                                        @else
                                            <x-admin.status-badge tone="neutral">{{ __('No quota') }}</x-admin.status-badge>
                                        @endif
                                        @if ($leaveType->requires_attachment)
                                            <x-admin.status-badge tone="warning">{{ __('Attachment') }}</x-admin.status-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <x-admin.status-badge :tone="$leaveType->is_active ? 'success' : 'neutral'" pill>
                                        {{ $leaveType->is_active ? __('Active') : __('Inactive') }}
                                    </x-admin.status-badge>
                                    @if ($leaveType->is_system)
                                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('System default') }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="edit({{ $leaveType->id }})" variant="primary"
                                            label="{{ __('Edit leave type') }}: {{ $leaveType->name }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        @unless ($leaveType->is_system)
                                            <x-actions.icon-button wire:click="confirmDeletion({{ $leaveType->id }})" variant="danger"
                                                label="{{ __('Delete leave type') }}: {{ $leaveType->name }}">
                                                <x-heroicon-m-trash class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ __('No leave types found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:hidden">
                @foreach ($leaveTypes as $leaveType)
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-semibold text-slate-950 dark:text-white">{{ $leaveType->name }}</h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $categories[$leaveType->category] ?? $leaveType->category }}</p>
                            </div>
                            <x-admin.status-badge :tone="$leaveType->is_active ? 'success' : 'neutral'">
                                {{ $leaveType->is_active ? __('Active') : __('Inactive') }}
                            </x-admin.status-badge>
                        </div>
                        <div class="mt-4 flex justify-end gap-2">
                            <x-actions.button type="button" wire:click="edit({{ $leaveType->id }})" variant="soft-primary" size="sm">
                                {{ __('Edit') }}
                            </x-actions.button>
                            @unless ($leaveType->is_system)
                                <x-actions.button type="button" wire:click="confirmDeletion({{ $leaveType->id }})" variant="soft-danger" size="sm">
                                    {{ __('Delete') }}
                                </x-actions.button>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($leaveTypes->hasPages())
                <div class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                    {{ $leaveTypes->onEachSide(1)->links() }}
                </div>
            @endif
        </x-admin.panel>
    </x-admin.page-shell>

    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">{{ __('New Leave Type') }}</x-slot>
        <x-slot name="content">
            @include('livewire.admin.master-data.partials.leave-type-form', ['formId' => 'create'])
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="create" wire:loading.attr="disabled">{{ __('Save') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model="editing">
        <x-slot name="title">{{ __('Edit Leave Type') }}</x-slot>
        <x-slot name="content">
            @include('livewire.admin.master-data.partials.leave-type-form', ['formId' => 'edit'])
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="update" wire:loading.attr="disabled">{{ __('Update') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Leave Type') }}</x-slot>
        <x-slot name="content">
            {{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?
            <x-forms.input-error for="delete" class="mt-2" />
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">{{ __('Confirm Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>
</div>
