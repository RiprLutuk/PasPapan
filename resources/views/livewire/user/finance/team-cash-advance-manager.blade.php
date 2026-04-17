<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Team Kasbon')"
                title-id="team-kasbon-title"
                class="border-b-0">
                <x-slot name="icon">
                    <span class="text-lg leading-none">💸</span>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="user-filter-grid lg:max-w-3xl">
                        <div class="relative w-full">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                            </div>
                            <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search Employee...') }}" class="block w-full rounded-lg border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
                        </div>
                        @if($activeTab === 'requests')
                        <select wire:model.live="statusFilter" class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                            <option value="paid">{{ __('Paid') }}</option>
                            <option value="all">{{ __('All Status') }}</option>
                        </select>
                        @endif
                    </div>
                </div>

                @include('livewire.shared.finance.cash-advance-manager-content')
            </div>
        </div>
    </div>
</div>
