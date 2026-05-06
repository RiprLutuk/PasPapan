<div>
    <x-admin.page-shell :title="__('Payroll Configurations')" :description="__('Manage allowances, deductions, and tax rules.')">
        <x-slot name="actions">
            <x-actions.button wire:click="create" type="button" size="icon" label="{{ __('Add Component') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path>
                </svg>
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="lg:col-span-1">
                    <x-forms.label for="payroll-search" value="{{ __('Search components') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <x-forms.input id="payroll-search" type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by name, type, or method...') }}" class="w-full pl-11" />
                    </div>
                </div>

                <div>
                    <x-forms.label for="payroll-type-filter" value="{{ __('Component Type') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-type-filter" wire:model.live="typeFilter" class="w-full">
                        <option value="all">{{ __('All types') }}</option>
                        <option value="allowance">{{ __('Allowance') }}</option>
                        <option value="deduction">{{ __('Deduction') }}</option>
                    </x-forms.select>
                </div>

                <div>
                    <x-forms.label for="payroll-active-filter" value="{{ __('Status') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-active-filter" wire:model.live="activeFilter" class="w-full">
                        <option value="all">{{ __('All statuses') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </x-forms.select>
                </div>

                <div>
                    <x-forms.label for="payroll-per-page" value="{{ __('Rows per page') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-per-page" wire:model.live="perPage" class="w-full">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </x-forms.select>
                </div>
            </x-admin.page-tools>
        </x-slot>

        <x-admin.panel>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Name') }}</th>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Type') }}</th>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Calculation') }}</th>
                            <th
                                class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Value') }}</th>
                            <th
                                class="px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Active') }}</th>
                            <th
                                class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-transparent dark:divide-gray-700">
                        @forelse ($components as $payrollComponent)
                            <tr class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $payrollComponent->name }}</div>
                                    @if ($payrollComponent->is_taxable)
                                        <x-admin.status-badge tone="warning" class="mt-2">
                                            {{ __('Taxable') }}
                                        </x-admin.status-badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-admin.status-badge :tone="$payrollComponent->type === 'allowance' ? 'success' : 'danger'">
                                        {{ __(ucfirst($payrollComponent->type)) }}
                                    </x-admin.status-badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ str_replace('_', ' ', ucfirst($payrollComponent->calculation_type)) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 font-mono text-sm text-gray-900 dark:text-gray-200">
                                    @if ($payrollComponent->calculation_type == 'percentage_basic')
                                        {{ $payrollComponent->percentage }}%
                                    @else
                                        Rp {{ number_format($payrollComponent->amount, 0, ',', '.') }}
                                        @if ($payrollComponent->calculation_type == 'daily_presence')
                                            <span class="text-xs text-gray-500">/{{ __('day') }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <x-forms.switch wire:click="toggleActive({{ $payrollComponent->id }})"
                                        :checked="$payrollComponent->is_active" :label="__('Toggle payroll component') . ': ' . $payrollComponent->name" />
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="edit({{ $payrollComponent->id }})"
                                            variant="primary"
                                            label="{{ __('Edit payroll component') }}: {{ $payrollComponent->name }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="confirmDelete({{ $payrollComponent->id }})"
                                            variant="danger"
                                            label="{{ __('Delete payroll component') }}: {{ $payrollComponent->name }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex justify-center">
                                        <x-admin.empty-state :title="filled($search) || $typeFilter !== 'all' || $activeFilter !== 'all' ? __('No matching components found.') : __('No components found.')">
                                            <x-slot name="icon">
                                                <svg class="h-12 w-12 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                    </path>
                                                </svg>
                                            </x-slot>
                                        </x-admin.empty-state>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($components->hasPages())
                <div
                    class="border-t border-gray-200/60 bg-gray-50/70 px-4 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                    {{ $components->links() }}
                </div>
            @endif
        </x-admin.panel>
    </x-admin.page-shell>

    {{-- Create/Edit Modal --}}
    <x-overlays.dialog-modal wire:model.live="showModal">
        <x-slot name="title">
            {{ $selectedId ? __('Edit Component') : __('Add New Component') }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Name --}}
                <div class="col-span-2">
                    <x-forms.label for="name" value="{{ __('Component Name') }}" />
                    <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model="name"
                        placeholder="{{ __('e.g. Uang Makan') }}" />
                    <x-forms.input-error for="name" class="mt-2" />
                </div>

                {{-- Type --}}
                <div>
                    <x-forms.label for="type" value="{{ __('Type') }}" />
                    <x-forms.select id="type" wire:model.live="type" class="mt-1 block w-full">
                        <option value="allowance">{{ __('Allowance (+)') }}</option>
                        <option value="deduction">{{ __('Deduction (-)') }}</option>
                    </x-forms.select>
                </div>

                {{-- Calculation Type --}}
                <div>
                    <x-forms.label for="calculation_type" value="{{ __('Calculation Method') }}" />
                    <x-forms.select id="calculation_type" wire:model.live="calculation_type"
                        class="mt-1 block w-full">
                        <option value="fixed">{{ __('Fixed Amount') }}</option>
                        <option value="daily_presence">{{ __('Daily Rate (x Attendance)') }}</option>
                        <option value="percentage_basic">{{ __('% of Basic Salary') }}</option>
                    </x-forms.select>
                </div>

                {{-- Amount / Percentage --}}
                <div class="col-span-2">
                    @if ($calculation_type === 'percentage_basic')
                        <x-forms.label for="percentage" value="{{ __('Percentage (%)') }}" />
                        <div class="relative mt-1">
                            <x-forms.input id="percentage" type="number" step="0.01" class="block w-full pr-12"
                                wire:model="percentage" placeholder="5.00" />
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
                            <x-forms.input id="amount" type="number" class="block w-full pl-12"
                                wire:model="amount" placeholder="0" />
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $calculation_type === 'daily_presence' ? __('Multiplied by days present.') : __('Fixed amount per month.') }}
                        </p>
                        <x-forms.input-error for="amount" class="mt-2" />
                    @endif
                </div>

                {{-- Taxable Toggle --}}
                <div class="col-span-2 flex items-center">
                    <x-forms.checkbox id="is_taxable" wire:model="is_taxable" />
                    <div class="ml-2">
                        <x-forms.label for="is_taxable" value="{{ __('Is Taxable Income?') }}" />
                        <p class="text-xs text-gray-500">
                            {{ __('Enable if this component should be included in PPh 21 calculation base (Not fully implemented yet).') }}
                        </p>
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
