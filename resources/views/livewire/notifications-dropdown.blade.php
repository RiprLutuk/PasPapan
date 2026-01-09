<div class="flex items-center" wire:poll.10s>
    <div class="relative ms-3" x-data="{ open: false }">
        <button @click="open = !open" @click.away="open = false" class="relative p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <span class="sr-only">{{ __('View notifications') }}</span>
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            
            @if($notifications->count() > 0 || $announcements->count() > 0)
            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-600 ring-2 ring-white transform translate-x-1/4 -translate-y-1/4"></span>
            @endif
        </button>

        <div x-show="open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="fixed left-4 right-4 top-[calc(4rem+env(safe-area-inset-top))] z-50 mt-2 sm:absolute sm:top-auto sm:left-auto sm:right-0 sm:w-80 origin-top-right rounded-md bg-white dark:bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
             style="display: none;">
             
            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-200">{{ __('Notifications') }}</h3>
                @if($notifications->count() > 0)
                    <button wire:click="markAllAsRead" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-900">{{ __('Mark all read') }}</button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                {{-- Announcements Section --}}
                @if($announcements->count() > 0)
                    <div class="px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-700/30">
                        <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">📢 {{ __('Announcements') }}</p>
                    </div>
                    @foreach($announcements as $announcement)
                        <div class="relative px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out border-b border-gray-100 dark:border-gray-700 group" wire:key="ann-{{ $announcement->id }}">
                            <button 
                                wire:click="dismissAnnouncement({{ $announcement->id }})"
                                class="absolute top-2 right-2 p-1 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-all"
                                title="{{ __('Dismiss') }}"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            <div class="flex items-start gap-2 pr-6">
                                @if($announcement->priority === 'high')
                                    <span class="flex-shrink-0 mt-1 h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                                @elseif($announcement->priority === 'normal')
                                    <span class="flex-shrink-0 mt-1 h-2 w-2 rounded-full bg-amber-500"></span>
                                @else
                                    <span class="flex-shrink-0 mt-1 h-2 w-2 rounded-full bg-gray-400"></span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-200 truncate">
                                        {{ $announcement->title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ Str::limit(strip_tags($announcement->content), 80) }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ $announcement->publish_date->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- System Notifications --}}
                @if($notifications->count() > 0)
                    <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-700/30">
                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-400">🔔 {{ __('System') }}</p>
                    </div>
                @endif
                @forelse($notifications as $notification)
                    <button wire:click="markAsRead('{{ $notification->id }}')" class="w-full text-left block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-200">
                            {{ $notification->data['message'] ?? __('New Notification') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </button>
                @empty
                    @if($announcements->count() == 0)
                    <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No new notifications.') }}
                    </div>
                    @endif
                @endforelse
            </div>
        </div>
    </div>
</div>
