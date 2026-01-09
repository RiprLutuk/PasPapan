<div class="grid grid-cols-4 gap-y-6 gap-x-2">
    
    {{-- 1. History --}}
    <a href="{{ route('attendance-history') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('History') }}</span>
    </a>

    {{-- 2. Leave --}}
    <a href="{{ route('apply-leave') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Leave') }}</span>
    </a>

    {{-- 3. Overtime (Placeholder) --}}
    <div class="flex flex-col items-center gap-2 group opacity-60">
        <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400 flex items-center justify-center shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Overtime') }}</span>
    </div>

    {{-- 4. Reimbursement (Replaces Calendar) --}}
    <a href="{{ route('reimbursement') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Reimbursement') }}</span>
    </a>

    {{-- 5. Payslip (Placeholder) --}}
    <div class="flex flex-col items-center gap-2 group opacity-60">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 flex items-center justify-center shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Payslip') }}</span>
    </div>

    {{-- 6. Profile (Swapped from 8) --}}
     <a href="{{ route('profile.show') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-300 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Profile') }}</span>
    </a>
    
    {{-- 7. My Schedule (Replaces Forms) --}}
    <a href="{{ route('my-schedule') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('My Schedule') }}</span>
    </a>

    {{-- 8. Log Out (Replaces Finance & Moved found at pos 6) --}}
    <form method="POST" action="{{ route('logout') }}" x-data class="flex flex-col items-center gap-2 group cursor-pointer" @click.prevent="$root.submit();">
        @csrf
        <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Log Out') }}</span>
    </form>
</div>
