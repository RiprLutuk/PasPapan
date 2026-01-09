<x-app-layout>
    {{-- Brand Header (Red/Indigo Background) --}}
    <div class="relative bg-gradient-to-br from-primary-700 to-primary-800 dark:from-gray-900 dark:to-primary-950 pb-20 pt-6 sm:pt-10 rounded-b-[2.5rem] shadow-xl overflow-hidden border-b border-transparent dark:border-white/5 transition-all duration-300">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNmZmYiLz48L3N2Zz0=')]"></div>
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
             <div class="flex items-center justify-between text-white mb-4">
                {{-- Welcome Text (Left) --}}
                <div>
                    <p class="text-primary-100 dark:text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-0.5 leading-none">{{ __('Welcome back') }}</p>
                    <h1 class="text-xl font-bold leading-tight text-white dark:text-gray-100">{{ Str::before(Auth::user()->name, ' ') }}</h1>
                </div>

                {{-- Profile Picture (Right) --}}
                <div class="h-12 w-12 rounded-full border-2 border-white/20 dark:border-white/10 shadow-lg overflow-hidden shrink-0">
                     <img class="h-full w-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                </div>
            </div>
        </div>
    </div>

    {{-- Overlapping Content Container --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 -mt-20 relative z-20 pb-12 space-y-6">
         
         {{-- Attendance Command Center (Floating) --}}
         <div>
             @livewire('home-attendance-status')
         </div>

         {{-- Quick Actions Grid --}}
         <div>
            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-3 px-1 uppercase tracking-widest flex items-center gap-2">
                {{ __('My Menu') }}
            </h3>
            @livewire('quick-actions')
         </div>

         {{-- Widgets --}}
         <div>
            <div class="flex items-center justify-between mb-3 px-1">
                <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ __('Happening Now') }}</h3>
                <a href="{{ route('notifications') }}" class="text-[10px] font-bold text-primary-600 dark:text-primary-400 hover:text-primary-500 transition uppercase tracking-wide">{{ __('View All') }}</a>
            </div>
            @livewire('upcoming-events-widget')
         </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session()->has('flash.banner'))
                Swal.fire({
                    icon: 'success',
                    title: "{{ __('Success!') }}",
                    text: "{{ session('flash.banner') }}",
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: "{{ __('OK') }}"
                });
            @endif
        });
    </script>
    @endpush
</x-app-layout>
