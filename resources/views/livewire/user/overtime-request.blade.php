<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="overtime-request-title" class="user-page-surface relative">
            <x-user.page-header
                :back-href="!$showModal ? route('home') : null"
                :title="$showModal ? __('New Request') : __('Overtime Request')"
                title-id="overtime-request-title"
                class="border-b-0">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50 via-white to-sky-50 text-indigo-700 ring-1 ring-inset ring-indigo-100 shadow-sm dark:from-indigo-900/30 dark:via-gray-800 dark:to-sky-900/20 dark:text-indigo-300 dark:ring-indigo-800/60">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    @if($showModal)
                        <button wire:click="close" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                            <span>{{ __('Back') }}</span>
                        </button>
                    @else
                        <button wire:click="create" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>{{ __('New Request') }}</span>
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if($showModal)
                    {{-- Create Form --}}
                    <div class="mx-auto max-w-3xl rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:p-8">
                        <form wire:submit.prevent="store" class="space-y-6">
                            
                            {{-- Date --}}
                            <div>
                                <x-forms.label for="date" value="{{ __('Overtime Date') }}" />
                                <x-forms.input id="date" type="date" class="mt-1 block w-full" wire:model="date" />
                                <x-forms.input-error for="date" class="mt-2" />
                            </div>

                            {{-- Time Range --}}
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <x-forms.label for="start_time" value="{{ __('Start Time') }}" />
                                    <x-forms.input id="start_time" type="time" class="mt-1 block w-full" wire:model="start_time" />
                                    <x-forms.input-error for="start_time" class="mt-2" />
                                </div>
                                <div>
                                    <x-forms.label for="end_time" value="{{ __('End Time') }}" />
                                    <x-forms.input id="end_time" type="time" class="mt-1 block w-full" wire:model="end_time" />
                                    <x-forms.input-error for="end_time" class="mt-2" />
                                </div>
                            </div>

                            {{-- Reason --}}
                            <div>
                                <x-forms.label for="reason" value="{{ __('Reason') }}" />
                                <textarea id="reason" wire:model="reason" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" placeholder="e.g. Project Deadline"></textarea>
                                <x-forms.input-error for="reason" class="mt-2" />
                            </div>

                            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                                <x-actions.secondary-button wire:click="close" wire:loading.attr="disabled">
                                    {{ __('Cancel') }}
                                </x-actions.secondary-button>

                                <x-actions.button wire:loading.attr="disabled">
                                    {{ __('Submit Request') }}
                                </x-actions.button>
                            </div>
                        </form>
                    </div>

                @else
                    {{-- History List --}}
                    @if($overtimes->isEmpty())
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No Overtime Requests') }}</h3>
                            <p class="user-empty-state__copy">{{ __('You haven\'t submitted any overtime requests yet.') }}</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                     @foreach($overtimes as $overtime)
                                <div class="p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/50 sm:p-6">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 rounded-xl flex items-center justify-center bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                            <x-heroicon-o-clock class="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white capitalize">
                                                {{ $overtime->date->format('d M Y') }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">
                                                {{ $overtime->start_time->format('H:i') }} - {{ $overtime->end_time->format('H:i') }}
                                                <span class="mx-1">•</span>
                                                {{ $overtime->duration_text }}
                                            </p>
                                            <p class="text-[10px] text-gray-400 italic line-clamp-1">{{ $overtime->reason }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                         <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($overtime->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400
                                            @elseif($overtime->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400
                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400 @endif">
                                            {{ ucfirst($overtime->status) }}
                                        </span>
                                    </div>
                                    </div>
                                </div>
                             @endforeach
                        </div>
                        <div class="rounded-b-2xl border-t border-gray-100 p-4 dark:border-gray-700">
                            {{ $overtimes->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
