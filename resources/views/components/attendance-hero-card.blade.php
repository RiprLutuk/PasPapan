@props(['attendance'])

<div class="space-y-4">
    {{-- Main Status Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800 relative overflow-hidden">
        {{-- Background Decoration --}}
        <div class="absolute top-0 right-0 -mt-2 -mr-2 w-20 h-20 bg-blue-50 dark:bg-blue-900/20 rounded-full blur-xl opacity-70 pointer-events-none"></div>

        <div class="relative z-10">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Attendance') }}</p>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        {{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('l, d F Y') }}
                    </h3>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                    {{ __('Completed') }}
                </span>
            </div>

            {{-- Shift & Barcode Info --}}
            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 mb-4">
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="truncate">
                        {{ $attendance->shift->name ?? 'Regular Shift' }}
                        {{-- <span class="text-xs text-gray-400 ml-1">
                            ({{ \Carbon\Carbon::parse($attendance->shift?->start_time ?? '08:00')->format('H:i') }} - {{ \Carbon\Carbon::parse($attendance->shift?->end_time ?? '17:00')->format('H:i') }})
                        </span> --}}
                    </span>
                </div>
                <div class="h-4 w-px bg-gray-300 dark:bg-gray-600 flex-shrink-0"></div>
                <div class="flex items-center gap-2 min-w-0">
                     <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="truncate">{{ $attendance->barcode->name ?? '-' }}</span>
                </div>
            </div>

            {{-- Grid --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Check In --}}
                <div class="flex flex-col p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Check In') }}</span>
                        @if($attendance->latitude_in)
                            <a href="https://www.google.com/maps?q={{ $attendance->latitude_in }},{{ $attendance->longitude_in }}" target="_blank" class="text-[10px] flex items-center gap-1 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 rounded-full hover:bg-blue-200 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ __('Location') }}
                            </a>
                        @endif
                    </div>
                    <span class="text-base font-bold text-blue-700 dark:text-blue-300">
                        {{ \App\Helpers::format_time($attendance->time_in) }}
                    </span>
                </div>

                {{-- Check Out --}}
                <div class="flex flex-col p-3 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Check Out') }}</span>
                        @if($attendance->latitude_out)
                            <a href="https://www.google.com/maps?q={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}" target="_blank" class="text-[10px] flex items-center gap-1 text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/40 px-2 py-0.5 rounded-full hover:bg-orange-200 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ __('Location') }}
                            </a>
                        @endif
                    </div>
                    <span class="text-base font-bold text-orange-700 dark:text-orange-300">
                        {{ \App\Helpers::format_time($attendance->time_out) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Message --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800 text-center">
        <div class="mb-4 inline-flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-full shadow-sm">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Attendance Complete!') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('You\'ve successfully completed today\'s attendance') }}</p>
    </div>
</div>
