<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Team Kasbon')"
                title-id="team-kasbon-title"
                class="border-b-0">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-50 via-white to-yellow-50 text-amber-700 ring-1 ring-inset ring-amber-100 shadow-sm dark:from-amber-900/30 dark:via-gray-800 dark:to-yellow-900/20 dark:text-amber-300 dark:ring-amber-800/60">
                        <x-heroicon-o-wallet class="h-5 w-5" />
                    </div>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="user-filter-grid lg:max-w-3xl">
                        <div class="relative w-full">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                            </div>
                            <x-forms.input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search Employee...') }}" class="block w-full border-0 py-2.5 pl-10 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6" />
                        </div>
                        @if($activeTab === 'requests')
                        <x-forms.select wire:model.live="statusFilter" class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                            <option value="paid">{{ __('Paid') }}</option>
                            <option value="all">{{ __('All Status') }}</option>
                        </x-forms.select>
                        @endif
                    </div>
                </div>

                @include('livewire.shared.finance.cash-advance-manager-content')
            </div>
        </div>
    </div>
</div>
