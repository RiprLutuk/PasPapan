<x-admin.page-shell :title="__('Attendance Corrections')" :description="__('Review and apply employee attendance correction requests.')">
    <div class="space-y-4">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <x-forms.label for="attendance-correction-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                <x-forms.input id="attendance-correction-search" type="search" wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Employee, NIP, or reason') }}" class="w-full min-h-[44px]" />
            </div>
            <div>
                <x-forms.label for="attendance-correction-status" value="{{ __('Status') }}" class="mb-1.5 block" />
                <x-forms.select id="attendance-correction-status" wire:model.live="statusFilter">
                    <option value="all">{{ __('All statuses') }}</option>
                    <option value="pending_admin">{{ __('Waiting Admin Review') }}</option>
                    <option value="pending">{{ __('Pending Supervisor Review') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                </x-forms.select>
            </div>
            <div>
                <x-forms.label for="attendance-correction-type" value="{{ __('Request Type') }}" class="mb-1.5 block" />
                <x-forms.select id="attendance-correction-type" wire:model.live="typeFilter">
                    <option value="all">{{ __('All types') }}</option>
                    @foreach ($requestTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-forms.select>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/40">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">{{ __('Employee') }}</th>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('Type') }}</th>
                            <th class="px-4 py-3">{{ __('Current') }}</th>
                            <th class="px-4 py-3">{{ __('Requested') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($corrections as $correction)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-semibold">{{ $correction->user->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $correction->user->nip }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-semibold">{{ $correction->attendance_date->translatedFormat('d M Y') }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $correction->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-semibold">{{ $correction->requestTypeLabel() }}</div>
                                    <div class="mt-1 max-w-sm whitespace-pre-line text-xs text-slate-500 dark:text-slate-400">{{ $correction->reason }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="space-y-1 text-xs">
                                        <div>{{ __('Shift') }}: {{ data_get($correction->current_snapshot, 'shift_name', __('Not assigned')) }}</div>
                                        <div>{{ __('Check in') }}: {{ data_get($correction->current_snapshot, 'time_in', __('None')) }}</div>
                                        <div>{{ __('Check out') }}: {{ data_get($correction->current_snapshot, 'time_out', __('None')) }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="space-y-1 text-xs">
                                        @if ($correction->requestedShift)
                                            <div>{{ __('Shift') }}: {{ $correction->requestedShift->name }}</div>
                                        @endif
                                        @if ($correction->requested_time_in)
                                            <div>{{ __('Check in') }}: {{ $correction->requested_time_in->translatedFormat('d M Y H:i') }}</div>
                                        @endif
                                        @if ($correction->requested_time_out)
                                            <div>{{ __('Check out') }}: {{ $correction->requested_time_out->translatedFormat('d M Y H:i') }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $correction->status === 'approved'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                            : ($correction->status === 'rejected'
                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                        {{ $correction->statusLabel() }}
                                    </span>
                                    @if ($correction->reviewer)
                                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('By :name', ['name' => $correction->reviewer->name]) }}
                                        </div>
                                    @elseif ($correction->headApprover)
                                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('Supervisor: :name', ['name' => $correction->headApprover->name]) }}
                                        </div>
                                    @endif
                                    @if ($correction->rejection_note)
                                        <div class="mt-2 text-xs text-rose-600 dark:text-rose-300">{{ $correction->rejection_note }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if (in_array($correction->status, ['pending', 'pending_admin'], true))
                                        <div class="flex flex-col gap-2">
                                            <x-actions.button type="button" size="sm" wire:click="approve({{ $correction->id }})">
                                                {{ __('Approve') }}
                                            </x-actions.button>
                                            <x-actions.secondary-button type="button" size="sm" wire:click="confirmReject({{ $correction->id }})">
                                                {{ __('Reject') }}
                                            </x-actions.secondary-button>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Completed') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No attendance correction requests found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($corrections->hasPages())
            <div>
                {{ $corrections->links() }}
            </div>
        @endif
    </div>

    <x-overlays.dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">{{ __('Reject Attendance Correction') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-3">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Add an optional note so the employee understands why this request was rejected.') }}
                </p>
                <x-forms.textarea wire:model.live="rejectionNote" rows="4"
                    placeholder="{{ __('Example: please resubmit with the exact checkout time from your field report.') }}" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full justify-end gap-2">
                <x-actions.secondary-button type="button" wire:click="cancelReject">
                    {{ __('Cancel') }}
                </x-actions.secondary-button>
                <x-actions.button type="button" wire:click="reject">
                    {{ __('Reject Request') }}
                </x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
