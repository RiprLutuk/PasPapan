@props(['attendance'])

<div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
    
    {{-- Top Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                {{ __('Attendance') }}
            </p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
            </h2>
        </div>
        
        {{-- Completed Badge --}}
        <div class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1.5 rounded-full border border-emerald-100 dark:border-emerald-800">
            <div class="flex h-2.5 w-2.5 items-center justify-center rounded-full bg-emerald-500">
                <svg class="h-1.5 w-1.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 leading-none">
                {{ __('Finished') }}
            </span>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="grid grid-cols-2 gap-4 mb-8">
        {{-- Check In --}}
        <div class="bg-gray-50/50 dark:bg-gray-700/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 relative overflow-hidden group">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-2 h-2 rounded-full bg-primary-500 ring-2 ring-primary-100 dark:ring-primary-900/50"></div>
                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    {{ __('Check In') }}
                </span>
            </div>
            <div class="text-2xl font-black text-gray-900 dark:text-white font-mono tracking-tight">
                {{ $attendance?->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : '--:--' }}
            </div>
             @if($attendance?->time_in)
                <div class="absolute bottom-0 right-0 p-2 opacity-5">
                     <svg class="w-12 h-12 text-primary-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"></path></svg>
                </div>
            @endif
        </div>

        {{-- Check Out --}}
        <div class="bg-gray-50/50 dark:bg-gray-700/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 relative overflow-hidden group">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-2 h-2 rounded-full bg-orange-500 ring-2 ring-orange-100 dark:ring-orange-900/50"></div>
                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    {{ __('Check Out') }}
                </span>
            </div>
            <div class="text-2xl font-black text-gray-900 dark:text-white font-mono tracking-tight">
                {{ $attendance?->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i') : '--:--' }}
            </div>
             @if($attendance?->time_out)
                <div class="absolute bottom-0 right-0 p-2 opacity-5">
                    <svg class="w-12 h-12 text-orange-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"></path></svg>
                </div>
            @endif
        </div>
    </div>

    {{-- Completion Summary --}}
    <div class="bg-emerald-50/50 dark:bg-emerald-900/20 rounded-2xl p-4 border border-emerald-100/50 dark:border-emerald-800/30">
        <div class="flex items-center gap-4">
            <div class="h-10 w-10 flex-shrink-0 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ __('Good job, you\'re done!') }}
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mt-0.5">
                    {{ __('You have successfully completed today\'s attendance.') }}
                </p>
            </div>
        </div>
    </div>
</div>
