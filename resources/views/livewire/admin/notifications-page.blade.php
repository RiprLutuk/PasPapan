<x-admin.page-shell
    :title="__('Notifications')"
    :description="__('Review unread alerts, historical notifications, and active announcements in a full-page admin view.')">
    <x-slot name="actions">
        <div class="flex flex-wrap items-center gap-2">
            <x-admin.status-badge tone="primary" pill>
                {{ __('Unread') }}: {{ $notificationCount }}
            </x-admin.status-badge>
            @if($announcementCount > 0)
                <x-admin.status-badge tone="info" pill>
                    {{ __('Announcements') }}: {{ $announcementCount }}
                </x-admin.status-badge>
            @endif
            <x-admin.status-badge tone="neutral" pill>
                {{ __('Inbox') }}: {{ $unreadCount }}
            </x-admin.status-badge>
        </div>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools
            :title="__('Filter Notification Inbox')"
            :description="__('Search notification titles or messages, then focus on announcements, unread items, or the full inbox.')"
        >
            <x-slot name="summary">
                <div class="rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ __('Unread') }}: {{ $notificationCount }}
                    @if($announcementCount > 0)
                        <span class="ml-2">{{ __('Announcements') }}: {{ $announcementCount }}</span>
                    @endif
                </div>
            </x-slot>

            <div class="md:col-span-2 xl:col-span-7">
                <x-forms.label for="notification-search" value="{{ __('Search inbox') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <x-forms.input
                        id="notification-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search title or message...') }}"
                        class="w-full pl-11"
                    />
                </div>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="notification-content-filter" value="{{ __('View') }}" class="mb-1.5 block" />
                <x-forms.select id="notification-content-filter" wire:model.live="contentFilter" class="w-full">
                    <option value="all">{{ __('All inbox content') }}</option>
                    <option value="notifications">{{ __('Notifications only') }}</option>
                    <option value="announcements">{{ __('Announcements only') }}</option>
                    <option value="unread">{{ __('Unread only') }}</option>
                </x-forms.select>
            </div>

            <div class="xl:col-span-2">
                <x-forms.label for="notification-unread-toggle" value="{{ __('Read State') }}" class="mb-1.5 block" />
                <button
                    id="notification-unread-toggle"
                    type="button"
                    wire:click="$toggle('showUnreadOnly')"
                    aria-pressed="{{ $showUnreadOnly ? 'true' : 'false' }}"
                    aria-controls="admin-notifications-list"
                    class="inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-xl border px-4 py-3 text-sm font-semibold transition {{ $showUnreadOnly ? 'border-primary-600 bg-primary-600 text-white dark:border-primary-500 dark:bg-primary-500' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200' }}">
                    {{ $showUnreadOnly ? __('Unread only enabled') : __('Include read items') }}
                </button>
            </div>

            <x-slot name="actions">
                @if($notificationCount > 0)
                    <button
                        type="button"
                        wire:click="markAllAsRead"
                        class="inline-flex min-h-[2.75rem] items-center rounded-xl bg-primary-50 px-4 py-3 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/35">
                        {{ __('Mark All as Read') }}
                    </button>
                @endif
            </x-slot>
        </x-admin.page-tools>
    </x-slot>

    @if($announcements->isEmpty() && $notifications->isEmpty())
        <x-admin.empty-state
            :framed="true"
            :title="__('No notifications yet')"
            :description="__('New approvals, system messages, and announcements will appear here.')">
            <x-slot name="icon">
                <div class="rounded-xl bg-slate-100 p-4 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
            </x-slot>
        </x-admin.empty-state>
    @else
        <div id="admin-notifications-list" class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
            <x-admin.insight-panel class="overflow-hidden">
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
                                        class="block min-w-0 flex-1 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-primary-500/40">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-950 dark:text-white">
                                                {{ $notification->data['title'] ?? __('Notification') }}
                                            </h3>
                                            @if(is_null($notification->read_at))
                                                <x-admin.status-badge tone="info" pill>{{ __('Unread') }}</x-admin.status-badge>
                                            @else
                                                <x-admin.status-badge tone="neutral" pill>{{ __('Read') }}</x-admin.status-badge>
                                            @endif
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
                                            @if(is_null($notification->read_at))
                                                <x-admin.status-badge tone="info" pill>{{ __('Unread') }}</x-admin.status-badge>
                                            @else
                                                <x-admin.status-badge tone="neutral" pill>{{ __('Read') }}</x-admin.status-badge>
                                            @endif
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

                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                @if(is_null($notification->read_at))
                                    <button
                                        type="button"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="inline-flex min-h-[2.75rem] items-center rounded-xl bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/35">
                                        {{ __('Mark as Read') }}
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No notifications found for this filter.') }}
                        </div>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="overflow-hidden">
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
                                        @if($announcement->priority === 'high')
                                            <x-admin.status-badge tone="danger" pill>{{ __('Important') }}</x-admin.status-badge>
                                        @else
                                            <x-admin.status-badge tone="primary" pill>{{ ucfirst($announcement->priority) }}</x-admin.status-badge>
                                        @endif
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
            </x-admin.insight-panel>
        </div>

        @if($notifications->hasPages())
            <div>
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</x-admin.page-shell>
