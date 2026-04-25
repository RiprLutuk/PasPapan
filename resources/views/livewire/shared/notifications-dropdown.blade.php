@php($allNotificationsUrl = auth()->user()->can('manageAdminNotifications') ? route('admin.notifications') : route('notifications'))

<div class="relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false" wire:poll.10s>
    <button type="button" @click="open = ! open" class="topbar-tool topbar-tool--icon relative"
        :aria-expanded="open.toString()" aria-haspopup="menu" aria-controls="notifications-panel">
        <span class="sr-only">{{ __('View notifications') }}</span>
        
        <x-heroicon-o-bell class="h-5 w-5" />

        {{-- Count Badge --}}
        @if($unreadCount > 0)
            <span class="absolute right-0 top-0 inline-flex h-6 w-6 translate-x-1/4 -translate-y-1/4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-800">
                {{ $unreadCount > 99 ? '99' : $unreadCount }}
            </span>
        @endif
    </button>

    <div id="notifications-panel" x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="fixed inset-x-4 top-16 mt-2 z-50 w-auto origin-top rounded-xl bg-white dark:bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:absolute sm:right-0 sm:inset-x-auto sm:top-full sm:mt-2 sm:w-80 sm:origin-top-right"
        style="display: none;">
        
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-200">{{ __('Notifications') }}</h3>
            @if($unreadCount > 0)
                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-primary-50 px-2 py-1 text-[11px] font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @if($items->isEmpty())
                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-inbox class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                    {{ __('No new notifications') }}
                </div>
            @else
                @foreach($items as $item)
                    @if($item['type'] === 'notification')
                        @php($notification = $item['data'])
                        <div class="relative border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50 last:border-0 group">
                            @php($targetUrl = \App\Support\Helpers::normalizeInternalUrl($notification->data['url'] ?? $notification->data['action_url'] ?? null) ?? '#')
                            <a href="{{ $targetUrl }}" wire:click="markAsRead('{{ $notification->id }}')" @click="open = false" class="block pr-16">
                                <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-300">
                                    @if(is_null($notification->read_at))
                                        <span class="h-2 w-2 flex-shrink-0 rounded-full bg-blue-500"></span>
                                    @endif
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </h4>
                                <p class="mt-1 line-clamp-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ $notification->data['message'] ?? '' }}
                                </p>
                                <span class="mt-1.5 block text-[10px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                            </a>

                            <div class="absolute right-3 top-3 flex items-center gap-1">
                                @if(is_null($notification->read_at))
                                    <button wire:click.stop="markAsRead('{{ $notification->id }}')" @click="open = false" class="rounded-full p-1 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30" title="{{ __('Mark as read') }}" aria-label="{{ __('Mark as read') }}">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                    </button>
                                @endif
                                <button wire:click.stop="markAsRead('{{ $notification->id }}')" @click="open = false" class="rounded-full p-1 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30" title="{{ __('Dismiss') }}" aria-label="{{ __('Dismiss') }}">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    @else
                        @php($announcement = $item['data'])
                        <div class="border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50 last:border-0 group">
                            <div class="flex items-start justify-between">
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-300">{{ $announcement->title }}</h4>
                                <button wire:click="dismiss({{ $announcement->id }})" @click="open = false" class="rounded p-1 text-xs text-gray-400 opacity-0 transition-opacity hover:text-red-500 group-hover:opacity-100" title="{{ __('Dismiss') }}" aria-label="{{ __('Dismiss') }}">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="mt-1 line-clamp-3 text-xs text-gray-600 dark:text-gray-400">
                                {{ Str::limit(strip_tags($announcement->content), 100) }}
                            </p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[10px] text-gray-400">{{ $announcement->created_at->diffForHumans() }}</span>
                                @if($announcement->priority === 'high')
                                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-600 dark:bg-red-900/30 dark:text-red-400">{{ __('Important') }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        <div class="border-t border-gray-100 px-3 py-2 dark:border-gray-700">
            <a href="{{ $allNotificationsUrl }}"
                @click="open = false"
                class="inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:hover:text-primary-300">
                {{ __('Show All') }}
            </a>
        </div>
    </div>
</div>
