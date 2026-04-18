<x-admin.page-shell :title="__('Manage Kasbon')">
    <x-slot name="toolbar">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-3xl">
            <div class="relative w-full">
                <label for="cash-advance-search" class="sr-only">{{ __('Search Employee') }}</label>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                </div>
                <input id="cash-advance-search" wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search Employee...') }}" class="block w-full rounded-lg border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
            </div>
            @if($activeTab === 'requests')
            <label for="cash-advance-status-filter" class="sr-only">{{ __('Filter by status') }}</label>
            <select id="cash-advance-status-filter" wire:model.live="statusFilter" class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="all">{{ __('All Status') }}</option>
            </select>
            @endif
        </div>
    </x-slot>

    @include('livewire.shared.finance.cash-advance-manager-content')
</x-admin.page-shell>
