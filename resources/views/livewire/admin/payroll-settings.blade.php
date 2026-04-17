<div>
    <x-admin.page-shell
        :title="__('Payroll Configurations')"
        :description="__('Manage allowances, deductions, and tax rules.')"
    >
        <x-slot name="actions">
            <button
                wire:click="create"
                type="button"
                title="{{ __('Add Component') }}"
                aria-label="{{ __('Add Component') }}"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-lg shadow-primary-500/30 transition-all duration-200 hover:scale-[1.02] hover:from-primary-500 hover:to-primary-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path>
                </svg>
            </button>
        </x-slot>

        <x-slot name="toolbar">
            <div class="max-w-sm">
                <x-forms.input
                    type="text"
                    wire:model.live="search"
                    placeholder="{{ __('Search components...') }}"
                    class="w-full rounded-xl border-gray-300 text-sm shadow-sm"
                />
            </div>
        </x-slot>

        <div class="overflow-hidden rounded-2xl border border-gray-200/50 bg-white/80 shadow-xl backdrop-blur-xl dark:border-gray-700/50 dark:bg-gray-800/80">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Calculation') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Value') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Active') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-transparent dark:divide-gray-700">
                        @forelse ($components as $component)
                            <tr class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $component->name }}</div>
                                    @if($component->is_taxable)
                                        <span class="mt-2 inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-900/20 dark:text-yellow-300 dark:ring-yellow-400/20">
                                            {{ __('Taxable') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $component->type === 'allowance' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/20 dark:text-green-300 dark:ring-green-400/20' : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/20 dark:text-red-300 dark:ring-red-400/20' }}">
                                        {{ __(ucfirst($component->type)) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ str_replace('_', ' ', ucfirst($component->calculation_type)) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-900 dark:text-gray-200">
                                    @if($component->calculation_type == 'percentage_basic')
                                        {{ $component->percentage }}%
                                    @else
                                        Rp {{ number_format($component->amount, 0, ',', '.') }}
                                        @if($component->calculation_type == 'daily_presence')
                                            <span class="text-xs text-gray-500">/{{ __('day') }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <button wire:click="toggleActive({{ $component->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 {{ $component->is_active ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $component->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $component->id }})" class="text-gray-400 transition-colors hover:text-blue-600" title="{{ __('Edit') }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </button>
                                        <button wire:click="confirmDelete({{ $component->id }})" class="text-gray-400 transition-colors hover:text-red-600" title="{{ __('Delete') }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="mb-3 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <p>{{ __('No components found.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200/50 px-6 py-4 dark:border-gray-700/50">
                {{ $components->links() }}
            </div>
        </div>
    </x-admin.page-shell>

    {{-- Create/Edit Modal --}}
    <x-overlays.dialog-modal wire:model.live="showModal">
        <x-slot name="title">
            {{ $selectedId ? __('Edit Component') : __('Add New Component') }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Name --}}
                <div class="col-span-2">
                    <x-forms.label for="name" value="{{ __('Component Name') }}" />
                    <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model="name" placeholder="{{ __('e.g. Uang Makan') }}" />
                    <x-forms.input-error for="name" class="mt-2" />
                </div>

                {{-- Type --}}
                <div>
                    <x-forms.label for="type" value="{{ __('Type') }}" />
                    <select id="type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-primary-600 dark:focus:ring-primary-600">
                        <option value="allowance">{{ __('Allowance (+)') }}</option>
                        <option value="deduction">{{ __('Deduction (-)') }}</option>
                    </select>
                </div>

                {{-- Calculation Type --}}
                <div>
                    <x-forms.label for="calculation_type" value="{{ __('Calculation Method') }}" />
                    <select id="calculation_type" wire:model.live="calculation_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-primary-600 dark:focus:ring-primary-600">
                        <option value="fixed">{{ __('Fixed Amount') }}</option>
                        <option value="daily_presence">{{ __('Daily Rate (x Attendance)') }}</option>
                        <option value="percentage_basic">{{ __('% of Basic Salary') }}</option>
                    </select>
                </div>

                {{-- Amount / Percentage --}}
                <div class="col-span-2">
                    @if($calculation_type === 'percentage_basic')
                        <x-forms.label for="percentage" value="{{ __('Percentage (%)') }}" />
                        <div class="relative mt-1">
                            <x-forms.input id="percentage" type="number" step="0.01" class="block w-full pr-12" wire:model="percentage" placeholder="5.00" />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        <x-forms.input-error for="percentage" class="mt-2" />
                    @else
                        <x-forms.label for="amount" value="{{ __('Amount (Rp)') }}" />
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <x-forms.input id="amount" type="number" class="block w-full pl-12" wire:model="amount" placeholder="0" />
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $calculation_type === 'daily_presence' ? __('Multiplied by days present.') : __('Fixed amount per month.') }}</p>
                        <x-forms.input-error for="amount" class="mt-2" />
                    @endif
                </div>

                {{-- Taxable Toggle --}}
                <div class="col-span-2 flex items-center">
                    <x-forms.checkbox id="is_taxable" wire:model="is_taxable" />
                    <div class="ml-2">
                        <x-forms.label for="is_taxable" value="{{ __('Is Taxable Income?') }}" />
                        <p class="text-xs text-gray-500">{{ __('Enable if this component should be included in PPh 21 calculation base (Not fully implemented yet).') }}</p>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.button class="ms-3" wire:click="save" wire:loading.attr="disabled">
                {{ $selectedId ? __('Update') : __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    {{-- Delete Confirmation --}}
    <x-overlays.confirmation-modal wire:model.live="confirmingDeletion">
        <x-slot name="title">
            {{ __('Delete Component') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete this component? This will not affect past payroll records, but will be removed from future calculations.') }}
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('confirmingDeletion', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.danger-button class="ms-3" wire:click="delete" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>
</div>
