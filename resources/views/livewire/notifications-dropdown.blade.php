<div class="relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false">
    <button @click="open = ! open" class="relative p-1 rounded-full text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all">
        <span class="sr-only">{{ __('View notifications') }}</span>
        
        {{-- Bell Icon --}}
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>

        {{-- Red Dot Badge --}}
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-800 animate-pulse"></span>
        @endif
    </button>

    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-xl bg-white dark:bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        style="display: none;">
        
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-200">{{ __('Notifications') }}</h3>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @if($announcements->isEmpty())
                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    {{ __('No new notifications') }}
                </div>
            @else
                @foreach($announcements as $announcement)
                    <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0 group">
                        <div class="flex justify-between items-start">
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-300">{{ $announcement->title }}</h4>
                            <button wire:click="dismiss({{ $announcement->id }})" class="text-xs text-gray-400 hover:text-red-500 p-1 rounded opacity-0 group-hover:opacity-100 transition-opacity" title="{{ __('Dismiss') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-3">
                            {{ Str::limit(strip_tags($announcement->content), 100) }}
                        </p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-[10px] text-gray-400">{{ $announcement->created_at->diffForHumans() }}</span>
                            @if($announcement->priority === 'high')
                                <span class="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">{{ __('Important') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
