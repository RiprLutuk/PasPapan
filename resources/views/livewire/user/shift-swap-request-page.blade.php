<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="shift-swap-title" class="user-page-surface">
            <x-user.page-header :back-href="route('my-schedule')" :title="__('Shift Swap Requests')" title-id="shift-swap-title" class="border-b-0">
                <x-slot name="icon">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-50 via-white to-emerald-50 text-sky-700 ring-1 ring-inset ring-sky-100 shadow-sm dark:from-sky-900/30 dark:via-gray-800 dark:to-emerald-900/20 dark:text-sky-300 dark:ring-sky-800/60">
                        <x-heroicon-o-arrows-right-left class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    <button type="button" wire:click="create"
                        class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Request') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if (session()->has('success'))
                    <div
                        class="mb-4 rounded-xl border border-green-100 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                <div
                    class="hidden overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('Schedule Date') }}</th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('Requested Shift') }}</th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('Replacement') }}</th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('Status') }}</th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($requests as $request)
                                    <tr>
                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-semibold">
                                                {{ $request->effectiveScheduleDate()?->translatedFormat('d M Y') ?? '-' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Current') }}:
                                                {{ $request->currentShift->name ?? __('No current schedule') }}</div>
                                        </td>
                                        <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $request->requestedShift->name ?? '-' }}
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $request->replacementUser->name ?? __('Not specified') }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($request->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                                {{ $request->statusLabel() }}
                                            </span>
                                            @if ($request->reviewer)
                                                <div class="mt-1 text-[10px] text-gray-400">{{ __('by') }}
                                                    {{ $request->reviewer->name }}</div>
                                            @endif
                                            @if ($request->rejection_note)
                                                <div class="mt-1 text-xs text-red-600 dark:text-red-300">
                                                    {{ $request->rejection_note }}</div>
                                            @endif
                                        </td>
                                        <td class="max-w-sm px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                            <div class="line-clamp-2">{{ $request->reason }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No shift swap requests found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-4 md:hidden">
                    @forelse ($requests as $request)
                        <article
                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $request->effectiveScheduleDate()?->translatedFormat('d M Y') ?? '-' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Current') }}: {{ $request->currentShift->name ?? __('No current schedule') }}
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($request->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                    {{ $request->statusLabel() }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Requested Shift') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $request->requestedShift->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Replacement') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $request->replacementUser->name ?? __('Not specified') }}</p>
                                </div>
                            </div>

                            <div
                                class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Reason') }}</p>
                                <div class="mt-1">{{ $request->reason }}</div>
                            </div>

                            @if ($request->reviewer || $request->rejection_note)
                                <div class="mt-3 space-y-2 text-xs">
                                    @if ($request->reviewer)
                                        <div
                                            class="rounded-xl bg-gray-50 p-3 text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                                            {{ __('by') }} {{ $request->reviewer->name }}
                                        </div>
                                    @endif
                                    @if ($request->rejection_note)
                                        <div
                                            class="rounded-xl bg-red-50 p-3 text-red-600 dark:bg-red-900/20 dark:text-red-300">
                                            {{ $request->rejection_note }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @empty
                        <div
                            class="rounded-xl border border-gray-100 bg-white p-8 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            {{ __('No shift swap requests found.') }}
                        </div>
                    @endforelse
                </div>

                @if ($requests->hasPages())
                    <div class="mt-4">{{ $requests->links() }}</div>
                @endif
            </div>
        </section>
    </div>

    <x-overlays.dialog-modal wire:model.live="showModal">
        <x-slot name="title">{{ __('New Shift Swap Request') }}</x-slot>

        <x-slot name="content">
            @php($selectedSchedule = $schedules->first(fn($schedule) => (string) $schedule->id === (string) $scheduleId))
            @php($selectedScheduleDate = $selectedSchedule?->date ?? ($scheduleDate ? rescue(fn() => \Carbon\Carbon::parse($scheduleDate), null, false) : null))
            @php($selectedRequestedShift = $shifts->first(fn($shift) => (string) $shift->id === (string) $requestedShiftId))
            @php($selectedReplacement = $replacementUsers->first(fn($replacement) => (string) $replacement->id === (string) $replacementUserId))

            <form wire:submit="store" class="space-y-5">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <x-forms.label for="swap-schedule" value="{{ __('Schedule Date') }}" class="mb-1.5 block" />
                    <p class="mb-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        {{ __('Choose the work date you want to change first. The assigned shift for that date will be loaded automatically below.') }}
                    </p>
                    <div wire:ignore wire:key="swap-schedule-date-{{ $scheduleDate ?? 'empty' }}">
                        <x-forms.input id="swap-schedule" type="date" wire:model.live="scheduleDate"
                            value="{{ $scheduleDate }}" min="{{ now()->toDateString() }}" class="block w-full" />
                    </div>
                    <x-forms.input-error for="scheduleDate" class="mt-2" />
                    <x-forms.input-error for="scheduleId" class="mt-2" />
                </div>

                @if ($selectedSchedule)
                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ __('Current Schedule Snapshot') }}
                        </p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-2xl border border-white/70 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/70">
                                <div
                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                                    {{ __('Schedule Date') }}</div>
                                <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedSchedule->date->translatedFormat('l, d M Y') }}
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-white/70 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/70">
                                <div
                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                                    {{ __('Current') }}</div>
                                <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedSchedule->shift?->name ?? __('Off Day') }}
                                </div>
                                @if ($selectedSchedule->shift)
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($selectedSchedule->shift->start_time)->format('H:i') }}
                                        -
                                        {{ \Carbon\Carbon::parse($selectedSchedule->shift->end_time)->format('H:i') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($selectedScheduleDate)
                    <div
                        class="rounded-2xl border border-sky-100 bg-sky-50/60 p-4 text-sm text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/10 dark:text-sky-200">
                        {{ __('No current schedule is assigned for this date. The requested shift will be added to the schedule after approval.') }}
                    </div>
                @endif

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <x-forms.label for="swap-shift" value="{{ __('Requested Shift') }}" class="mb-1.5 block" />
                    <p class="mb-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        {{ __('Choose the replacement shift you want for that same date.') }}
                    </p>
                    <x-forms.select id="swap-shift" wire:model.live="requestedShiftId" class="block w-full">
                        <option value="">{{ __('Choose requested shift') }}</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }}
                                ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})
                            </option>
                        @endforeach
                    </x-forms.select>
                    <x-forms.input-error for="requestedShiftId" class="mt-2" />

                    @if ($selectedRequestedShift)
                        <div
                            class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3 dark:border-emerald-900/50 dark:bg-emerald-950/10">
                            <div
                                class="text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-700 dark:text-emerald-300">
                                {{ __('Requested Shift') }}</div>
                            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $selectedRequestedShift->name }}
                            </div>
                            <div class="mt-1 text-xs text-emerald-800 dark:text-emerald-200">
                                {{ \Carbon\Carbon::parse($selectedRequestedShift->start_time)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($selectedRequestedShift->end_time)->format('H:i') }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <x-forms.label for="swap-replacement"
                        value="{{ __('Replacement Employee') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                    <p class="mb-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        {{ __('Pick a teammate if you already know who can cover this shift. You can search by name and leave this empty if the reviewer should decide later.') }}
                    </p>
                    <x-user.tom-select-user id="swap-replacement" wire:model.live="replacementUserId"
                        :options="$replacementUserOptions" placeholder="{{ __('Search replacement employee') }}"
                        dropdown-parent="self" class="block w-full">
                        <option value="">{{ __('No replacement specified') }}</option>
                        @foreach ($replacementUsers as $replacement)
                            <option value="{{ $replacement->id }}">
                                {{ $replacement->name }}
                                @if ($replacement->jobTitle?->name)
                                    - {{ $replacement->jobTitle->name }}
                                @endif
                            </option>
                        @endforeach
                    </x-user.tom-select-user>
                    <x-forms.input-error for="replacementUserId" class="mt-2" />

                    @if ($selectedReplacement)
                        <div
                            class="mt-4 rounded-2xl border border-sky-100 bg-sky-50/60 p-3 dark:border-sky-900/50 dark:bg-sky-950/10">
                            <div
                                class="text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">
                                {{ __('Replacement') }}</div>
                            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $selectedReplacement->name }}
                            </div>
                            @if ($selectedReplacement->jobTitle?->name)
                                <div class="mt-1 text-xs text-sky-800 dark:text-sky-200">
                                    {{ $selectedReplacement->jobTitle->name }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($selectedScheduleDate && $selectedRequestedShift)
                    <div
                        class="rounded-2xl border border-primary-100 bg-primary-50/60 p-4 dark:border-primary-900/50 dark:bg-primary-950/10">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Request Summary') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-2xl border border-white/70 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/70">
                                <div
                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                                    {{ __('Current') }}</div>
                                <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedSchedule?->shift?->name ?? __('No current schedule') }}
                                </div>
                                @if ($selectedSchedule?->shift)
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($selectedSchedule->shift->start_time)->format('H:i') }}
                                        -
                                        {{ \Carbon\Carbon::parse($selectedSchedule->shift->end_time)->format('H:i') }}
                                    </div>
                                @endif
                            </div>
                            <div
                                class="rounded-2xl border border-white/70 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/70">
                                <div
                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                                    {{ __('Requested Shift') }}</div>
                                <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedRequestedShift->name }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($selectedRequestedShift->start_time)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($selectedRequestedShift->end_time)->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <x-forms.label for="swap-reason" value="{{ __('Reason') }}" class="mb-1.5 block" />
                    <p class="mb-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        {{ __('Explain the reason clearly so your supervisor can review the request quickly.') }}
                    </p>
                    <x-forms.textarea id="swap-reason" wire:model.live="reason" rows="4" class="block w-full"
                        placeholder="{{ __('Explain why this shift needs to be changed or covered.') }}" />
                    <x-forms.input-error for="reason" class="mt-2" />
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <div class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-actions.secondary-button type="button" wire:click="close" class="w-full sm:w-auto">
                    {{ __('Cancel') }}
                </x-actions.secondary-button>
                <x-actions.button type="button" wire:click="store" class="w-full sm:w-auto">
                    {{ __('Submit Request') }}
                </x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
