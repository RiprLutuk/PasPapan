<x-admin.page-shell :title="__('Manage Kasbon')">
    <x-slot name="toolbar">
        <x-admin.page-tools>
            <div class="md:col-span-2 xl:col-span-8">
                <x-forms.label for="cash-advance-search" value="{{ __('Search cash advance requests') }}"
                    class="mb-1.5 block" />
                <div class="relative w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <x-forms.input id="cash-advance-search" wire:model.live.debounce.300ms="search" type="search"
                        placeholder="{{ __('Search employee name...') }}" class="w-full pl-11" />
                </div>
            </div>

            @if ($activeTab === 'requests')
                <div class="xl:col-span-4">
                    <x-forms.label for="cash-advance-status-filter" value="{{ __('Status') }}" class="mb-1.5 block" />
                    <x-forms.select id="cash-advance-status-filter" wire:model.live="statusFilter" class="w-full">
                        <option value="all">{{ __('All statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                        <option value="paid">{{ __('Paid') }}</option>
                    </x-forms.select>
                </div>
            @endif
        </x-admin.page-tools>
    </x-slot>

    @include('livewire.shared.finance.cash-advance-manager-content')
</x-admin.page-shell>
