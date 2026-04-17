<x-app-layout>
    <section aria-labelledby="home-page-title" class="relative overflow-hidden rounded-b-[2.5rem] border-b border-transparent bg-gradient-to-br from-primary-700 to-primary-800 pb-20 pt-6 shadow-xl transition-all duration-300 dark:border-white/5 dark:from-gray-900 dark:to-primary-950">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNmZmYiLz48L3N2Zz0=')]"></div>
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
             <div class="mb-4 flex items-center justify-between text-white">
                {{-- Welcome Text (Left) --}}
                <div>
                    <p class="text-sm font-semibold text-primary-50/90">{{ __('Welcome back') }}</p>
                    <h1 id="home-page-title" class="text-2xl font-bold leading-tight text-white dark:text-gray-100">{{ Auth::user()->name }}</h1>
                </div>

                {{-- Profile Picture (Right) --}}
                <div class="h-12 w-12 shrink-0 overflow-hidden rounded-full border-2 border-white/20 shadow-lg dark:border-white/10">
                     <img class="h-full w-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                </div>
            </div>
        </div>
    </section>

    {{-- Overlapping Content Container --}}
    <div class="relative z-20 mx-auto -mt-20 max-w-7xl space-y-8 px-4 pb-12 sm:px-6 lg:px-8">
         
         {{-- Attendance Command Center (Floating) --}}
         <section aria-labelledby="attendance-summary-heading">
             <h2 id="attendance-summary-heading" class="sr-only">{{ __('Today attendance summary') }}</h2>
             @livewire('user.home-attendance-status')
         </section>

         {{-- Quick Actions Grid --}}
         <section aria-labelledby="my-menu-heading">
            <h2 id="my-menu-heading" class="sr-only">{{ __('Quick Access') }}</h2>
            @livewire('user.quick-actions')
         </section>

         {{-- Widgets --}}
         <section aria-labelledby="happening-now-heading">
            <div class="mb-3 flex items-center justify-between gap-3 px-1">
                <h2 id="happening-now-heading" class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Happening Now') }}</h2>
                <a href="{{ route('notifications') }}" class="text-sm font-semibold text-primary-700 transition hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-200">{{ __('View All') }}</a>
            </div>
            @livewire('user.upcoming-events-widget')
         </section>
    </div>

    @push('scripts')
    {{-- Scripts handled by layout --}}
    <script>
        if (sessionStorage.getItem('force_reload_next')) {
            sessionStorage.removeItem('force_reload_next');
            window.location.reload();
        }
    </script>
    @endpush
</x-app-layout>
