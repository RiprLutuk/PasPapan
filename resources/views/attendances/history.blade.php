<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Attendance') }}
        </h2>
    </x-slot>

    <div class="user-page-shell">
        <div class="user-page-container user-page-container--wide">
            <section aria-labelledby="attendance-history-title" class="user-page-surface">
                <x-user.page-header
                    :back-href="route('home')"
                    :title="__('Attendance History')"
                    title-id="attendance-history-title">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-user.page-header>

                <div class="user-page-body">
                    @livewire('user.attendance-history-component')
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
