<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-6 lg:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                
                {{-- Header --}}
                <div class="px-5 py-4 lg:px-8 lg:py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 relative z-10">
                    <div class="flex items-center gap-3">
                        <x-secondary-button href="{{ url()->previous() }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                            <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                        </x-secondary-button>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                             <span class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            </span>
                            {{ __('Inbox') }}
                        </h3>
                    </div>
                    @if(!$announcements->isEmpty())
                        <span class="px-2.5 py-1 rounded-full bg-primary-50 dark:bg-primary-900/30 text-xs font-bold text-primary-600 dark:text-primary-400 border border-primary-100 dark:border-primary-800">
                            {{ $announcements->count() }}
                        </span>
                    @endif
                </div>

                <div class="p-0">
                    @if($announcements->isEmpty() && $notifications->isEmpty())
                        <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-full mb-4">
                                <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('No new notifications') }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-xs mx-auto">{{ __('We\'ll let you know when something important arrives.') }}</p>
                        </div>
                    @else
                        <ul role="list" class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            {{-- User Notifications --}}
                            @foreach($notifications as $notification)
                                <li class="group relative hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors duration-200">
                                    <div class="p-5 sm:p-6 flex gap-4">
                                        <!-- Icon -->
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-600 text-white shadow-md shadow-indigo-200 dark:shadow-none">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate pr-4">
                                                    {{ $notification->data['title'] ?? 'Notification' }}
                                                </h3>
                                                <span class="flex-shrink-0 text-[10px] font-medium text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed text-pretty">
                                                {{ $notification->data['message'] ?? '' }}
                                            </div>
                                            @if(isset($notification->data['url']) || isset($notification->data['action_url']))
                                                <a href="{{ $notification->data['url'] ?? $notification->data['action_url'] }}" class="mt-2 inline-flex items-center text-xs font-medium text-primary-600 hover:text-primary-500">
                                                    {{ __('View Details') }} &rarr;
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="absolute bottom-0 left-20 right-0 h-px bg-gray-50 dark:bg-gray-800"></div>
                                </li>
                            @endforeach

                            {{-- Announcements --}}
                            @foreach($announcements as $announcement)
                                <li class="group relative hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors duration-200">
                                    <div class="p-5 sm:p-6 flex gap-4">
                                        <!-- Icon / Status -->
                                        <div class="flex-shrink-0 mt-0.5">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-white shadow-md shadow-primary-200 dark:shadow-none">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate pr-4">
                                                    {{ $announcement->title }}
                                                </h3>
                                                <span class="flex-shrink-0 text-[10px] font-medium text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                                    {{ $announcement->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed text-pretty">
                                                {!! Str::limit(strip_tags($announcement->content), 200) !!}
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="absolute bottom-0 left-20 right-0 h-px bg-gray-50 dark:bg-gray-800"></div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <p class="text-[10px] text-gray-400 dark:text-gray-600 uppercase tracking-widest">{{ __('End of Notifications') }}</p>
            </div>
        </div>
    </div>
</div>
