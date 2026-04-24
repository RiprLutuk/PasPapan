@php($backRoute = route('home'))

<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="notifications-title" class="user-page-surface">
            <x-user.page-header
                :back-href="$backRoute"
                :title="__('Inbox')"
                title-id="notifications-title">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-100 text-primary-700 ring-1 ring-inset ring-primary-200 shadow-sm dark:bg-primary-900/30 dark:text-primary-300 dark:ring-primary-800/60">
                        <x-heroicon-o-bell class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    <div class="flex items-center gap-2">
                        @if($unreadCount > 0)
                            <span class="rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-sm font-semibold text-primary-800 dark:border-primary-800 dark:bg-primary-900/30 dark:text-primary-200" role="status" aria-live="polite">
                                {{ $unreadCount }}
                            </span>
                        @endif
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('Notifications') }}
                        </span>
                    </div>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div class="border-b border-gray-100 bg-gray-50/80 px-5 py-4 dark:border-gray-700 dark:bg-gray-900/20 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="$toggle('showUnreadOnly')"
                                aria-pressed="{{ $showUnreadOnly ? 'true' : 'false' }}"
                                aria-controls="notifications-list"
                                class="inline-flex min-h-[2.75rem] items-center rounded-full px-3 py-1.5 text-sm font-semibold transition {{ $showUnreadOnly ? 'bg-primary-700 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100' }}">
                                {{ $showUnreadOnly ? __('Unread Only') : __('Show All') }}
                            </button>

                            <p class="text-sm text-gray-700 dark:text-gray-300" role="status" aria-live="polite">
                                {{ __('Unread notifications') }}: {{ $notificationCount }}
                                @if($announcementCount > 0)
                                    <span class="ml-2">{{ __('Announcements') }}: {{ $announcementCount }}</span>
                                @endif
                            </p>
                        </div>

                        @if($notificationCount > 0)
                            <button
                                type="button"
                                wire:click="markAllAsRead"
                                class="inline-flex min-h-[2.75rem] items-center justify-center rounded-xl bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-800 transition hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-200 dark:hover:bg-primary-900/50">
                                {{ __('Mark All as Read') }}
                            </button>
                        @endif
                    </div>
                </div>

                @if($announcements->isEmpty() && $notifications->isEmpty())
                    <div class="flex flex-col items-center justify-center px-4 py-16 text-center">
                        <div class="mb-4 rounded-full bg-gray-50 p-4 dark:bg-gray-700/50">
                            <x-heroicon-o-inbox class="h-10 w-10 text-gray-400 dark:text-gray-500" />
                        </div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ __('No new notifications') }}</h2>
                        <p class="mt-1 max-w-xs text-sm text-gray-700 dark:text-gray-300">{{ __('We\'ll let you know when something important arrives.') }}</p>
                    </div>
                @else
                    <div id="notifications-list" class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($notifications as $notification)
                            @php($targetUrl = \App\Support\Helpers::normalizeInternalUrl($notification->data['url'] ?? $notification->data['action_url'] ?? null))
                            <article class="group relative hover:bg-gray-50/80 dark:hover:bg-gray-700/30">
                                <div class="flex gap-4 p-5 sm:p-6">
                                    <div class="mt-0.5 flex-shrink-0">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md shadow-primary-500/25 dark:bg-primary-500 dark:shadow-none" aria-hidden="true">
                                            <x-heroicon-o-bell class="h-5 w-5" />
                                        </span>
                                    </div>

                                    @if($targetUrl)
                                        <a href="{{ $targetUrl }}"
                                            wire:click="markAsRead('{{ $notification->id }}')"
                                            class="block min-w-0 flex-1 rounded-2xl transition focus:outline-none focus:ring-2 focus:ring-primary-500/40">
                                            <div class="mb-1 flex items-start justify-between gap-3">
                                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $notification->data['title'] ?? __('Notification') }}
                                                </h2>
                                                <time class="whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300" datetime="{{ $notification->created_at?->toIso8601String() }}">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </time>
                                            </div>

                                            <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                                {{ $notification->data['message'] ?? '' }}
                                            </p>

                                            @if(is_null($notification->read_at))
                                                <div class="mt-3">
                                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                                        {{ __('Unread') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </a>
                                    @else
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-1 flex items-start justify-between gap-3">
                                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $notification->data['title'] ?? __('Notification') }}
                                                </h2>
                                                <time class="whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300" datetime="{{ $notification->created_at?->toIso8601String() }}">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </time>
                                            </div>

                                            <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                                {{ $notification->data['message'] ?? '' }}
                                            </p>

                                            @if(is_null($notification->read_at))
                                                <div class="mt-3">
                                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                                        {{ __('Unread') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-gray-600 dark:text-gray-300 sm:px-6">
                                {{ __('No notifications found for this filter.') }}
                            </div>
                        @endforelse

                        @foreach($announcements as $announcement)
                            <article class="group relative hover:bg-gray-50/80 dark:hover:bg-gray-700/30">
                                <div class="flex gap-4 p-5 sm:p-6">
                                    <div class="mt-0.5 flex-shrink-0">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/25 dark:bg-amber-500 dark:shadow-none" aria-hidden="true">
                                            <x-heroicon-o-megaphone class="h-5 w-5" />
                                        </span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="mb-1 flex items-start justify-between gap-3">
                                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $announcement->title }}
                                            </h2>
                                            <time class="whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300" datetime="{{ $announcement->created_at?->toIso8601String() }}">
                                                {{ $announcement->created_at->diffForHumans() }}
                                            </time>
                                        </div>

                                        <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($announcement->content), 200) }}
                                        </p>

                                        <div class="mt-3 flex flex-wrap items-center gap-3">
                                            @if($announcement->priority === 'high')
                                                <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/20 dark:text-red-300">
                                                    {{ __('Important') }}
                                                </span>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="dismissAnnouncement({{ $announcement->id }})"
                                                class="inline-flex min-h-[2.75rem] items-center text-sm font-semibold text-gray-800 transition hover:text-red-700 dark:text-gray-100 dark:hover:text-red-300">
                                                {{ __('Dismiss') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @if($notifications->hasPages())
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
