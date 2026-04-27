@php($announcementPollInterval = \App\Support\AnnouncementRefresh::pollInterval())

<div @if (\App\Support\AnnouncementRefresh::shouldPoll()) wire:poll.{{ $announcementPollInterval }}="syncAnnouncementState" @endif>
    @if ($announcement)
        <div
            x-data="{ show: @entangle('showModal') }"
            x-show="show"
            x-trap.inert.noscroll="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[70] overflow-y-auto px-4 py-6 sm:px-6"
            style="display: none;"
        >
            <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"></div>

            <div class="relative mx-auto flex min-h-full max-w-2xl items-center justify-center">
                <div class="w-full overflow-hidden rounded-3xl border border-red-200/70 bg-white shadow-2xl dark:border-red-900/40 dark:bg-slate-900">
                    <div class="border-b border-red-100 bg-gradient-to-r from-red-600 via-rose-600 to-orange-500 px-6 py-5 text-white dark:border-red-900/40">
                        <div class="flex items-start gap-4">
                            <div class="rounded-2xl bg-white/15 p-3 backdrop-blur">
                                <x-heroicon-o-megaphone class="h-6 w-6" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/75">{{ __('Important Announcement') }}</p>
                                <h2 class="mt-2 text-xl font-semibold leading-tight">{{ $announcement->title }}</h2>
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-white/80">
                                    <span class="rounded-full bg-white/15 px-2.5 py-1 font-semibold">{{ __('High Priority') }}</span>
                                    <span class="rounded-full bg-white/15 px-2.5 py-1 font-semibold">
                                        {{ ($announcement->modal_behavior ?? 'acknowledge') === 'once' ? __('Show Once') : __('Require Confirmation') }}
                                    </span>
                                    <span>{{ __('Published') }} {{ $announcement->publish_date->translatedFormat('d M Y') }}</span>
                                    @if ($announcement->expire_date)
                                        <span>{{ __('Visible until') }} {{ $announcement->expire_date->translatedFormat('d M Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-6">
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-5 text-sm leading-7 text-slate-700 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-200">
                            {!! nl2br(e($announcement->content)) !!}
                        </div>

                        @if (($announcement->modal_behavior ?? 'acknowledge') === 'once')
                            <div class="mt-5 rounded-2xl border border-sky-200/70 bg-sky-50/80 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200">
                                {{ __('This message is shown once per user while the announcement is active.') }}
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-amber-200/70 bg-amber-50/80 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
                                {{ __('This message will keep appearing on user pages until you press the confirmation button or until its display period ends.') }}
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end border-t border-slate-200 bg-slate-50/80 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
                        <x-actions.button type="button" wire:click="dismiss" variant="primary">
                            {{ ($announcement->modal_behavior ?? 'acknowledge') === 'once' ? __('Close') : __('I Understand') }}
                        </x-actions.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
