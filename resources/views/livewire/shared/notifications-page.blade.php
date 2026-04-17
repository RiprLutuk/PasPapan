@php($backRoute = auth()->user()->isAdmin ? route('admin.dashboard') : route('home'))

<div class="user-page-shell">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="user-page-container user-page-container--narrow">
        <section aria-labelledby="notifications-title" class="user-page-surface">
            <x-user.page-header
                :back-href="$backRoute"
                :title="__('Inbox')"
                title-id="notifications-title">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </x-slot>
                @if($unreadCount > 0)
                    <x-slot name="actions">
                        <span class="rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-sm font-semibold text-primary-800 dark:border-primary-800 dark:bg-primary-900/30 dark:text-primary-200">
                            {{ $unreadCount }}
                        </span>
                    </x-slot>
                @endif
            </x-user.page-header>

                <div class="px-5 py-4 sm:px-6 lg:px-8 border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/20 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="$toggle('showUnreadOnly')"
                            class="inline-flex min-h-[2.75rem] items-center rounded-full px-3 py-1.5 text-sm font-semibold transition {{ $showUnreadOnly ? 'bg-primary-700 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100' }}">
                            {{ $showUnreadOnly ? __('Unread Only') : __('Show All') }}
                        </button>
                        @if(Auth::user()->unreadNotifications()->count() > 0)
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ __('Unread notifications') }}: {{ Auth::user()->unreadNotifications()->count() }}
                            </span>
                        @endif
                    </div>

                    @if(Auth::user()->unreadNotifications()->count() > 0)
                        <button
                            type="button"
                            wire:click="markAllAsRead"
                            class="inline-flex min-h-[2.75rem] items-center justify-center rounded-xl bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-800 transition hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-200 dark:hover:bg-primary-900/50">
                            {{ __('Mark All as Read') }}
                        </button>
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
                            <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ __('No new notifications') }}</h2>
                            <p class="mt-1 max-w-xs mx-auto text-sm text-gray-700 dark:text-gray-300">{{ __('We\'ll let you know when something important arrives.') }}</p>
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
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-1 flex items-center justify-between">
                                                <h2 class="truncate pr-4 text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $notification->data['title'] ?? 'Notification' }}
                                                </h2>
                                                <span class="whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                                {{ $notification->data['message'] ?? '' }}
                                        </div>
                                        @if(isset($notification->data['url']) || isset($notification->data['action_url']))
                                                <a href="{{ $notification->data['url'] ?? $notification->data['action_url'] }}" wire:click="markAsRead('{{ $notification->id }}')" class="mt-2 inline-flex min-h-[2.75rem] items-center text-sm font-semibold text-primary-700 hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-200">
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
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-1 flex items-center justify-between">
                                                <h2 class="truncate pr-4 text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $announcement->title }}
                                                </h2>
                                                <span class="whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $announcement->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                                {!! Str::limit(strip_tags($announcement->content), 200) !!}
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="dismissAnnouncement({{ $announcement->id }})"
                                                class="mt-3 inline-flex min-h-[2.75rem] items-center text-sm font-semibold text-gray-800 transition hover:text-red-700 dark:text-gray-100 dark:hover:text-red-300">
                                                {{ __('Dismiss') }}
                                            </button>
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
        </section>

        @if($notifications->hasPages())
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ __('End of Notifications') }}</p>
        </div>
    </div>
</div>
