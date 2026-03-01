<div class="grid grid-cols-4 gap-y-6 gap-x-2">

    {{-- 1. History --}}
    <a href="{{ route('attendance-history') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('History') }}</span>
    </a>

    {{-- 2. Leave --}}
    <a href="{{ route('apply-leave') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Leave') }}</span>
    </a>

    {{-- 3. Overtime / Team Approvals --}}
    @if(Auth::user()->subordinates->isNotEmpty())
    <a href="{{ route('approvals') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Approvals') }}</span>
    </a>
    @else
    <a href="{{ route('overtime') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Overtime') }}</span>
    </a>
    @endif

    {{-- 4. Reimbursement (Replaces Calendar) --}}
    <a href="{{ route('reimbursement') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Reimbursement') }}</span>
    </a>

    {{-- 5. Payslip --}}
    {{-- 5. Payslip --}}
    @if(\App\Helpers\Editions::payrollLocked())
    <button type="button" @click.prevent="$dispatch('feature-lock', { title: 'Payroll Locked', 'message': 'Payroll Access is an Enterprise Feature 🔒. Please Upgrade.' })" class="flex flex-col items-center gap-2 group w-full">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300 relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center border border-white dark:border-gray-800">
                <span class="text-[10px]">🔒</span>
            </span>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Payslip') }}</span>
    </button>
    @else
    <a href="{{ route('my-payslips') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Payslip') }}</span>
    </a>
    @endif

    {{-- 6. Profile (Swapped from 8) --}}
    <a href="{{ route('profile.show') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-300 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Profile') }}</span>
    </a>

    {{-- 7. My Schedule (Replaces Forms) --}}
    <a href="{{ route('my-schedule') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('My Schedule') }}</span>
    </a>

    {{-- 8. Face ID --}}
    @if(\App\Helpers\Editions::attendanceLocked())
    <button type="button" @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })" class="flex flex-col items-center gap-2 group w-full">
        <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300 relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center border border-white dark:border-gray-800">
                <span class="text-[10px]">🔒</span>
            </span>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Face ID') }}</span>
    </button>
    @else
    <a href="{{ route('face.enrollment') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300 relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            @if(Auth::user()->hasFaceRegistered())
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </span>
            @endif
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Face ID') }}</span>
    </a>
    @endif

    {{-- 9. Team Kasbon --}}
    @if(Auth::user()->subordinates->isNotEmpty())
    @if(\App\Helpers\Editions::payrollLocked())
    <button type="button" @click.prevent="$dispatch('feature-lock', { title: 'Team Kasbon Locked', message: 'Team Kasbon Access is an Enterprise Feature 🔒. Please Upgrade.' })" class="flex flex-col items-center gap-2 group w-full">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300 relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center border border-white dark:border-gray-800">
                <span class="text-[10px]">🔒</span>
            </span>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Team Kasbon') }}</span>
    </button>
    @else
    <a href="{{ route('team-kasbon') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Team Kasbon') }}</span>
    </a>
    @endif
    @endif

    {{-- 10. Kasbon --}}
    @if(\App\Helpers\Editions::payrollLocked())
    <button type="button" @click.prevent="$dispatch('feature-lock', { title: 'Kasbon Locked', message: 'Kasbon Access is an Enterprise Feature 🔒. Please Upgrade.' })" class="flex flex-col items-center gap-2 group w-full">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300 relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center border border-white dark:border-gray-800">
                <span class="text-[10px]">🔒</span>
            </span>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Kasbon') }}</span>
    </button>
    @else
    <a href="{{ route('my-kasbon') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Kasbon') }}</span>
    </a>
    @endif

    {{-- 10. Log Out (Replaces Finance & Moved found at pos 6) --}}
    <form method="POST" action="{{ route('logout') }}" x-data class="flex flex-col items-center gap-2 group cursor-pointer" @click.prevent="$root.submit();">
        @csrf
        <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Log Out') }}</span>
    </form>
</div>