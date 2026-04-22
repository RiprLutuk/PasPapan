<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="attendance-correction-title" class="user-page-surface">
            <x-user.page-header :back-href="route('home')" :title="__('Attendance Corrections')" title-id="attendance-correction-title"
                class="border-b-0">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-50 via-white to-lime-50 text-emerald-700 ring-1 ring-inset ring-emerald-100 shadow-sm dark:from-emerald-900/30 dark:via-gray-800 dark:to-lime-900/20 dark:text-emerald-300 dark:ring-emerald-800/60">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M9 12h6m-6 4h6M9 8h6m-8 12h10a2 2 0 002-2V6a2 2 0 00-2-2h-1.172a2 2 0 01-1.414-.586l-.828-.828A2 2 0 0012.172 2h-.344a2 2 0 00-1.414.586l-.828.828A2 2 0 018.828 4H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </x-slot>
                <x-slot name="actions">
                    <button type="button" wire:click="create"
                        class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>{{ __('New Request') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="user-filter-grid">
                        <div>
                            <label
                                class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search') }}</label>
                            <x-forms.input id="correction-search" type="search" wire:model.live.debounce.300ms="search"
                                class="block w-full rounded-xl border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-100"
                                placeholder="{{ __('Reason or type') }}" />
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</label>
                            <x-forms.select id="correction-status" wire:model.live="statusFilter"
                                class="block w-full rounded-xl border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-100">
                                <option value="all">{{ __('All statuses') }}</option>
                                <option value="pending">{{ __('Pending Supervisor Review') }}</option>
                                <option value="pending_admin">{{ __('Waiting Admin Review') }}</option>
                                <option value="approved">{{ __('Approved') }}</option>
                                <option value="rejected">{{ __('Rejected') }}</option>
                            </x-forms.select>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr
                                    class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Requested Change') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950/30">
                                @forelse ($corrections as $correction)
                                    <tr class="align-top">
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-semibold">
                                                {{ $correction->attendance_date->translatedFormat('d M Y') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $correction->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $correction->requestTypeLabel() }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="space-y-1">
                                                @if ($correction->requested_time_in)
                                                    <div>{{ __('Check in') }}:
                                                        {{ $correction->requested_time_in->translatedFormat('d M Y H:i') }}
                                                    </div>
                                                @endif
                                                @if ($correction->requested_time_out)
                                                    <div>{{ __('Check out') }}:
                                                        {{ $correction->requested_time_out->translatedFormat('d M Y H:i') }}
                                                    </div>
                                                @endif
                                                @if ($correction->requestedShift)
                                                    <div>{{ __('Shift') }}: {{ $correction->requestedShift->name }}
                                                    </div>
                                                @endif
                                                @if (!$correction->requested_time_in && !$correction->requested_time_out && !$correction->requestedShift)
                                                    <div class="text-gray-500 dark:text-gray-400">
                                                        {{ __('No detailed change recorded.') }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                                {{ $correction->status === 'approved'
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                    : ($correction->status === 'rejected'
                                                        ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                                {{ $correction->statusLabel() }}
                                            </span>
                                            @if ($correction->rejection_note)
                                                <div class="mt-2 text-xs text-rose-600 dark:text-rose-300">
                                                    {{ $correction->rejection_note }}</div>
                                            @endif
                                            @if ($correction->headApprover && $correction->status === 'pending_admin')
                                                <div class="mt-2 text-xs text-sky-600 dark:text-sky-300">
                                                    {{ __('Forwarded by :name', ['name' => $correction->headApprover->name]) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="max-w-md whitespace-pre-line">{{ $correction->reason }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No attendance correction requests found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($corrections->hasPages())
                    <div class="mt-4">
                        {{ $corrections->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>

    <x-overlays.dialog-modal wire:model.live="showCreateModal">
        <x-slot name="title">{{ __('New Attendance Correction') }}</x-slot>

        <x-slot name="content">
            <div class="space-y-5">
                <div>
                    <x-forms.label for="attendance-date" value="{{ __('Attendance Date') }}" class="mb-1.5 block" />
                    <x-forms.input id="attendance-date" type="date" wire:model.live="attendanceDate"
                        max="{{ now()->toDateString() }}" class="mt-1 block w-full" />
                    <x-forms.input-error for="attendanceDate" class="mt-2" />
                </div>

                @if ($existingAttendance)
                    <div
                        class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                        <p class="font-semibold">{{ __('Current Attendance Snapshot') }}</p>
                        <div class="mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                            <div>{{ __('Status') }}: {{ ucfirst($existingAttendance->status) }}</div>
                            <div>{{ __('Shift') }}: {{ $existingAttendance->shift?->name ?? __('Not assigned') }}
                            </div>
                            <div>{{ __('Check in') }}:
                                {{ $existingAttendance->time_in?->translatedFormat('d M Y H:i') ?? __('None') }}</div>
                            <div>{{ __('Check out') }}:
                                {{ $existingAttendance->time_out?->translatedFormat('d M Y H:i') ?? __('None') }}</div>
                        </div>
                    </div>
                @endif

                <div>
                    <x-forms.label for="request-type" value="{{ __('Request Type') }}" class="mb-1.5 block" />
                    <x-forms.select id="request-type" wire:model.live="requestType" class="mt-1 block w-full">
                        @foreach ($requestTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-forms.input-error for="requestType" class="mt-2" />
                </div>

                @if (in_array($requestType, ['missing_check_in', 'wrong_time'], true))
                    <div>
                        <x-forms.label for="requested-time-in" value="{{ __('Requested Check In Time') }}"
                            class="mb-1.5 block" />
                        <x-forms.input id="requested-time-in" type="datetime-local" wire:model.live="requestedTimeIn"
                            class="mt-1 block w-full" />
                        <x-forms.input-error for="requestedTimeIn" class="mt-2" />
                    </div>
                @endif

                @if (in_array($requestType, ['missing_check_out', 'wrong_time'], true))
                    <div>
                        <x-forms.label for="requested-time-out" value="{{ __('Requested Check Out Time') }}"
                            class="mb-1.5 block" />
                        <x-forms.input id="requested-time-out" type="datetime-local"
                            wire:model.live="requestedTimeOut" class="mt-1 block w-full" />
                        <x-forms.input-error for="requestedTimeOut" class="mt-2" />
                    </div>
                @endif

                @if ($requestType === 'wrong_shift')
                    <div>
                        <x-forms.label for="requested-shift" value="{{ __('Correct Shift') }}"
                            class="mb-1.5 block" />
                        <x-forms.select id="requested-shift" wire:model.live="requestedShiftId" class="mt-1 block w-full">
                            <option value="">{{ __('Select shift') }}</option>
                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} -
                                    {{ $shift->end_time }})</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.input-error for="requestedShiftId" class="mt-2" />
                    </div>
                @endif

                <div>
                    <x-forms.label for="correction-reason" value="{{ __('Reason') }}" class="mb-1.5 block" />
                    <x-forms.textarea id="correction-reason" wire:model.live="reason" rows="4"
                        class="mt-1 block w-full"
                        placeholder="{{ __('Explain what happened and what should be corrected.') }}" />
                    <x-forms.input-error for="reason" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-actions.secondary-button type="button" wire:click="closeModal">
                    {{ __('Cancel') }}
                </x-actions.secondary-button>
                <x-actions.button type="button" wire:click="save" class="w-full sm:w-auto">
                    {{ __('Submit Request') }}
                </x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
