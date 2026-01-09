<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Present') }}</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $presentCount }} <span class="text-xs font-normal text-gray-400">{{ __('days') }}</span></p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
         <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Late') }}</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $lateCount }} <span class="text-xs font-normal text-gray-400">{{ __('days') }}</span></p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
         <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Leave/Sick') }}</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $leaveUsed }} <span class="text-xs font-normal text-gray-400">{{ __('days') }}</span></p>
    </div>
    
     <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-4 shadow-lg shadow-indigo-500/20 text-white relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mr-4 -mt-4 w-20 h-20 bg-white opacity-10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
        <p class="text-xs text-indigo-100 mb-1">{{ __('This Month') }}</p>
        <p class="text-lg font-bold">{{ $monthName }}</p>
        <div class="mt-3 flex items-center gap-1 text-xs text-indigo-100">
            <span>{{ __('Keep it up!') }}</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </div>
</div>
