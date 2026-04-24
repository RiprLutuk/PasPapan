<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="shift-swap-title" class="user-page-surface">
            <x-user.page-header
                :back-href="route('my-schedule')"
                :title="__('Shift Swap Requests')"
                title-id="shift-swap-title"
                class="border-b-0">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-50 via-white to-emerald-50 text-sky-700 ring-1 ring-inset ring-sky-100 shadow-sm dark:from-sky-900/30 dark:via-gray-800 dark:to-emerald-900/20 dark:text-sky-300 dark:ring-sky-800/60">
                        <x-heroicon-o-arrows-right-left class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    <button type="button" wire:click="create"
                        class="wcag-touch-target inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:w-auto">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Request') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if (session()->has('success'))
                    <div class="mb-4 rounded-xl border border-green-100 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Schedule') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Requested Shift') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Replacement') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($requests as $request)
                                    <tr>
                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-semibold">{{ $request->schedule?->date?->translatedFormat('d M Y') ?? '-' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Current') }}: {{ $request->currentShift->name ?? '-' }}</div>
                                        </td>
                                        <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $request->requestedShift->name ?? '-' }}
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $request->replacementUser->name ?? __('Not specified') }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($request->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                                {{ $request->statusLabel() }}
                                            </span>
                                            @if ($request->reviewer)
                                                <div class="mt-1 text-[10px] text-gray-400">{{ __('by') }} {{ $request->reviewer->name }}</div>
                                            @endif
                                            @if ($request->rejection_note)
                                                <div class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $request->rejection_note }}</div>
                                            @endif
                                        </td>
                                        <td class="max-w-sm px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            <div class="line-clamp-2">{{ $request->reason }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No shift swap requests found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($requests->hasPages())
                    <div class="mt-4">{{ $requests->links() }}</div>
                @endif
            </div>
        </section>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="shift-swap-modal-title">
            <div class="flex min-h-screen items-center justify-center px-4 py-6">
                <div class="fixed inset-0 bg-gray-900/60" wire:click="close"></div>
                <div class="relative w-full max-w-2xl rounded-3xl bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h2 id="shift-swap-modal-title" class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('New Shift Swap Request') }}</h2>

                    <form wire:submit="store" class="mt-6 space-y-5">
                        <div>
                            <x-forms.label for="swap-schedule" value="{{ __('Schedule') }}" class="mb-1.5 block" />
                            <x-forms.select id="swap-schedule" wire:model.live="scheduleId" class="block w-full">
                                <option value="">{{ __('Choose schedule') }}</option>
                                @foreach ($schedules as $schedule)
                                    <option value="{{ $schedule->id }}">
                                        {{ $schedule->date->translatedFormat('d M Y') }} - {{ $schedule->shift->name ?? __('Off Day') }}
                                    </option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="scheduleId" class="mt-1" />
                        </div>

                        <div>
                            <x-forms.label for="swap-shift" value="{{ __('Requested Shift') }}" class="mb-1.5 block" />
                            <x-forms.select id="swap-shift" wire:model.live="requestedShiftId" class="block w-full">
                                <option value="">{{ __('Choose requested shift') }}</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="requestedShiftId" class="mt-1" />
                        </div>

                        <div>
                            <x-forms.label for="swap-replacement" value="{{ __('Replacement Employee') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                            <x-forms.select id="swap-replacement" wire:model.live="replacementUserId" class="block w-full">
                                <option value="">{{ __('No replacement specified') }}</option>
                                @foreach ($replacementUsers as $replacement)
                                    <option value="{{ $replacement->id }}">{{ $replacement->name }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="replacementUserId" class="mt-1" />
                        </div>

                        <div>
                            <x-forms.label for="swap-reason" value="{{ __('Reason') }}" class="mb-1.5 block" />
                            <x-forms.textarea id="swap-reason" wire:model.live="reason" rows="4" class="block w-full"
                                placeholder="{{ __('Explain why this shift needs to be changed or covered.') }}" />
                            <x-forms.input-error for="reason" class="mt-1" />
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 dark:border-gray-700 sm:flex-row sm:justify-end">
                            <x-actions.button type="button" wire:click="close" variant="secondary" class="w-full sm:w-auto">
                                {{ __('Cancel') }}
                            </x-actions.button>
                            <x-actions.button type="submit" variant="primary" class="w-full sm:w-auto">
                                {{ __('Submit Request') }}
                            </x-actions.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
