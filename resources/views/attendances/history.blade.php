<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Attendance') }}
        </h2>
    </x-slot>

    <div class="py-6 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-5 lg:p-10">
                    <div class="flex items-center justify-between mb-6">
                         <x-secondary-button href="{{ url()->previous() }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                            <x-heroicon-o-arrow-left class="h-5 w-5 text-gray-500 dark:text-gray-300" />
                        </x-secondary-button>
                        
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ __('Attendance History') }}
                        </h2>
                        
                        <div class="w-10"></div>
                    </div>

                    @livewire('attendance-history-component')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
