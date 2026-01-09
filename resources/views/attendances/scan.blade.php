<x-app-layout>
    <div class="py-6 lg:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                 <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-gray-500 dark:text-gray-400">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Scan Attendance') }}</h1>
                
                <div class="w-10"></div>
            </div>

            {{-- Main Content --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden relative">
                 @livewire('scan-component')
            </div>
        </div>
    </div>
</x-app-layout>
