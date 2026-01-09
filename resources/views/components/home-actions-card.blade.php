@props(['hasCheckedIn', 'hasCheckedOut', 'attendance'])

<div class="bg-white dark:bg-gray-800 rounded-[1.5rem] p-5 shadow-lg border border-gray-100 dark:border-gray-700 relative overflow-hidden">
    
    {{-- Top Header --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5">
                {{ __('Attendance') }}
            </p>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">
                {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
            </h2>
        </div>
        
        {{-- Live Badge --}}
        <div class="flex items-center gap-1.5 bg-primary-50 dark:bg-primary-900/30 px-2.5 py-1 rounded-full border border-primary-100 dark:border-primary-800">
            <span class="relative flex h-1.5 w-1.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-primary-500"></span>
            </span>
            <span class="text-[9px] font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wider">
                {{ __('Live') }}
            </span>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="grid grid-cols-2 gap-3 mb-5">
        {{-- Check In --}}
        <div class="bg-gray-50/80 dark:bg-gray-700/30 rounded-xl p-3 border border-gray-100 dark:border-gray-700/50">
            <div class="flex items-center gap-1.5 mb-1">
                <div class="w-1.5 h-1.5 rounded-full bg-primary-500"></div>
                <span class="text-[9px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    {{ __('Check In') }}
                </span>
            </div>
            <div class="text-lg font-bold text-gray-900 dark:text-white font-mono tracking-tight">
                {{ $attendance?->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : '--:--' }}
            </div>
        </div>

        {{-- Check Out --}}
        <div class="bg-gray-50/80 dark:bg-gray-700/30 rounded-xl p-3 border border-gray-100 dark:border-gray-700/50">
             <div class="flex items-center gap-1.5 mb-1">
                <div class="w-1.5 h-1.5 rounded-full bg-orange-500"></div>
                <span class="text-[9px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    {{ __('Check Out') }}
                </span>
            </div>
            <div class="text-lg font-bold text-gray-900 dark:text-white font-mono tracking-tight">
                {{ $attendance?->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i') : '--:--' }}
            </div>
        </div>
    </div>

    {{-- Actions --}}
    @if(!$hasCheckedIn)
         <p class="text-center text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-3">
            {{ __('Ready to start your shift?') }}
        </p>
        <div class="grid grid-cols-2 gap-3">
             <a href="{{ route('scan') }}" class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-500/30 transition-all group">
                <div class="p-1 bg-white/20 rounded-lg group-hover:bg-white/30 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </div>
                <span class="text-xs font-semibold">{{ __('Clock In') }}</span>
            </a>

            <button disabled class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-400 border border-gray-100 dark:border-gray-600 cursor-not-allowed">
                 <div class="p-1 bg-gray-200 dark:bg-gray-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </div>
                <span class="text-xs font-semibold">{{ __('Clock Out') }}</span>
            </button>
        </div>

    @elseif(!$hasCheckedOut)
        <p class="text-center text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-3">
            {{ __('Don\'t forget to clock out when you\'re done.') }}
        </p>
         <div class="grid grid-cols-2 gap-3">
            <button disabled class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-400 border border-gray-100 dark:border-gray-600 cursor-not-allowed">
                <div class="p-1 bg-gray-200 dark:bg-gray-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </div>
                <span class="text-xs font-semibold">{{ __('Clock In') }}</span>
            </button>

             <a href="{{ route('scan') }}" class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-white border-2 border-orange-500 text-orange-600 hover:bg-orange-50 shadow-lg shadow-orange-500/10 transition-all group">
                <div class="p-1 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </div>
                <span class="text-xs font-semibold">{{ __('Clock Out') }}</span>
            </a>
        </div>
    @endif
</div>
