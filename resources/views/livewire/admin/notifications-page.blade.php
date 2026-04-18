<x-admin.page-shell
    :title="__('Notifications')"
    :description="__('Review unread alerts, historical notifications, and active announcements in a full-page admin view.')">
    <x-slot name="actions">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                {{ __('Unread') }}: {{ $notificationCount }}
            </span>
            @if($announcementCount > 0)
                <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                    {{ __('Announcements') }}: {{ $announcementCount }}
                </span>
            @endif
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                {{ __('Inbox') }}: {{ $unreadCount }}
            </span>
        </div>
    </x-slot>

    <x-slot name="toolbar">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="$toggle('showUnreadOnly')"
                    aria-pressed="{{ $showUnreadOnly ? 'true' : 'false' }}"
                    aria-controls="admin-notifications-list"
                    class="inline-flex min-h-[2.75rem] items-center rounded-full px-3 py-1.5 text-sm font-semibold transition {{ $showUnreadOnly ? 'bg-primary-700 text-white shadow-sm dark:bg-primary-500' : 'border border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200' }}">
                    {{ $showUnreadOnly ? __('Unread Only') : __('Show All') }}
                </button>

                @if($notificationCount > 0)
                    <button
                        type="button"
                        wire:click="markAllAsRead"
                        class="inline-flex min-h-[2.75rem] items-center rounded-xl bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/35">
                        {{ __('Mark All as Read') }}
                    </button>
                @endif
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300" role="status" aria-live="polite">
                {{ __('Unread notifications') }}: {{ $notificationCount }}
                @if($announcementCount > 0)
                    <span class="ml-2">{{ __('Active announcements') }}: {{ $announcementCount }}</span>
                @endif
            </p>
        </div>
    </x-slot>

    @if($announcements->isEmpty() && $notifications->isEmpty())
        <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('No notifications yet') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('New approvals, system messages, and announcements will appear here.') }}</p>
        </div>
    @else
        <div id="admin-notifications-list" class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
            <section class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <div class="border-b border-slate-200/70 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Notification History') }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        {{ __('All user-specific notifications are listed here, including read items when the filter allows them.') }}
                    </p>
                </div>

                <div class="divide-y divide-slate-200/70 dark:divide-slate-800">
                    @forelse($notifications as $notification)
                        @php($targetUrl = \App\Support\Helpers::normalizeInternalUrl($notification->data['url'] ?? $notification->data['action_url'] ?? null))
                        <article class="px-5 py-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                @if($targetUrl)
                                    <a href="{{ $targetUrl }}"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="block min-w-0 flex-1 rounded-2xl transition focus:outline-none focus:ring-2 focus:ring-primary-500/40">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-950 dark:text-white">
                                                {{ $notification->data['title'] ?? __('Notification') }}
                                            </h3>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ is_null($notification->read_at) ? 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                                {{ is_null($notification->read_at) ? __('Unread') : __('Read') }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>
                                    </a>
                                @else
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-950 dark:text-white">
                                                {{ $notification->data['title'] ?? __('Notification') }}
                                            </h3>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ is_null($notification->read_at) ? 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                                {{ is_null($notification->read_at) ? __('Unread') : __('Read') }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>
                                    </div>
                                @endif

                                <time class="text-xs font-medium uppercase tracking-[0.12em] text-slate-400" datetime="{{ $notification->created_at?->toIso8601String() }}">
                                    {{ $notification->created_at->diffForHumans() }}
                                </time>
                            </div>

                            @if(is_null($notification->read_at))
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <button
                                        type="button"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="inline-flex min-h-[2.75rem] items-center rounded-xl bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/35">
                                        {{ __('Mark as Read') }}
                                    </button>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No notifications found for this filter.') }}
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <div class="border-b border-slate-200/70 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Announcements') }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        {{ __('Active announcements remain visible until dismissed or expired.') }}
                    </p>
                </div>

                <div class="divide-y divide-slate-200/70 dark:divide-slate-800">
                    @forelse($announcements as $announcement)
                        <article class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-950 dark:text-white">
                                            {{ $announcement->title }}
                                        </h3>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $announcement->priority === 'high' ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300' : 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' }}">
                                            {{ $announcement->priority === 'high' ? __('Important') : ucfirst($announcement->priority) }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($announcement->content), 220) }}
                                    </p>
                                </div>

                                <time class="text-xs font-medium uppercase tracking-[0.12em] text-slate-400" datetime="{{ $announcement->created_at?->toIso8601String() }}">
                                    {{ $announcement->created_at->diffForHumans() }}
                                </time>
                            </div>

                            <div class="mt-3">
                                <button
                                    type="button"
                                    wire:click="dismissAnnouncement({{ $announcement->id }})"
                                    class="inline-flex min-h-[2.75rem] items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:text-red-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-red-900/30 dark:hover:text-red-300">
                                    {{ __('Dismiss Announcement') }}
                                </button>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No active announcements right now.') }}
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        @if($notifications->hasPages())
            <div>
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</x-admin.page-shell>
