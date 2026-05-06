@php($announcementPollInterval = \App\Support\AnnouncementRefresh::pollInterval())

<div @if (\App\Support\AnnouncementRefresh::shouldPoll()) wire:poll.{{ $announcementPollInterval }}="syncAnnouncementState" @endif>
    @if ($announcement)
        <div
            x-data="{ show: @entangle('showModal'), acknowledged: @entangle('hasReadAndUnderstood').live }"
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
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-md transition-opacity"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl dark:bg-slate-900 ring-1 ring-slate-900/5 dark:ring-white/10" @click.away="!@js(($announcement->modal_behavior ?? 'acknowledge') === 'acknowledge') && $wire.dismiss()">
                        <div class="relative overflow-hidden bg-white px-6 pb-4 pt-6 sm:px-8 sm:pt-8 dark:bg-slate-900">
                            <div class="relative flex items-start gap-5">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-rose-50 ring-1 ring-rose-100/50 dark:bg-rose-500/10 dark:ring-rose-500/20">
                                    <x-heroicon-o-document-text class="h-7 w-7 text-rose-600 dark:text-rose-400" />
                                </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-400">
                                        {{ __('Important Policy') }}
                                    </span>
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                        {{ $announcement->publish_date->translatedFormat('d F Y') }}
                                    </span>
                                </div>
                                <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 dark:text-white sm:text-3xl tracking-tight">
                                    {{ $announcement->title }}
                                </h2>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <x-heroicon-m-user class="h-4 w-4" />
                                    <span>{{ __('Published by') }} <span class="font-medium text-slate-700 dark:text-slate-300">{{ $announcement->creator?->name ?? 'System' }}</span></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="relative border-y border-slate-200 bg-slate-50/50 px-6 py-8 sm:px-8 dark:border-slate-800 dark:bg-slate-900/50">
                        <div class="prose prose-slate prose-sm sm:prose-base max-w-none dark:prose-invert">
                            {!! nl2br(e($announcement->content)) !!}
                        </div>
                    </div>

                    <div class="sticky bottom-0 z-20 border-t border-slate-200/80 bg-white/80 backdrop-blur-xl px-6 py-5 sm:px-8 dark:border-slate-800/80 dark:bg-slate-950/80">
                        @if (($announcement->modal_behavior ?? 'acknowledge') === 'acknowledge')
                            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/30 dark:bg-amber-900/10 dark:text-amber-300 flex items-start gap-3">
                                <x-heroicon-m-information-circle class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-500" />
                                <p>{{ __('You must read and acknowledge this policy to continue using the application. Your acknowledgement will be permanently recorded.') }}</p>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <label class="group relative flex cursor-pointer items-center gap-3">
                                    <div class="relative flex items-center justify-center">
                                        <input type="checkbox" x-model="acknowledged" class="peer h-6 w-6 cursor-pointer appearance-none rounded-md border-2 border-slate-300 bg-white transition-all checked:border-rose-600 checked:bg-rose-600 hover:border-rose-500 dark:border-slate-600 dark:bg-slate-800 dark:checked:border-rose-500 dark:checked:bg-rose-500 dark:hover:border-rose-400">
                                        <x-heroicon-m-check class="pointer-events-none absolute h-4 w-4 text-white opacity-0 transition-opacity peer-checked:opacity-100" />
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700 transition-colors group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white">
                                        {{ __('I have read, understood, and agree to this policy.') }}
                                    </span>
                                </label>

                                <x-actions.button type="button" wire:click="dismiss" variant="primary"
                                    wire:loading.attr="disabled"
                                    x-bind:disabled="!acknowledged"
                                    class="w-full sm:w-auto px-6 py-2.5 font-semibold shadow-md disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                >
                                    {{ __('Acknowledge & Continue') }} <x-heroicon-m-arrow-right class="ml-2 h-4 w-4" />
                                </x-actions.button>
                            </div>
                        @else
                            <div class="flex items-center justify-end">
                                <x-actions.button type="button" wire:click="dismiss" variant="primary" class="w-full sm:w-auto px-6 py-2.5">
                                    {{ __('Close Document') }}
                                </x-actions.button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
