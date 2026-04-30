<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Apply Leave') }}
        </h2>
    </x-slot>

    <div class="user-page-shell">
        <div class="user-page-container user-page-container--wide">
            <section aria-labelledby="leave-request-title" class="user-page-surface">
                <x-user.page-header
                    :back-href="route('home')"
                    :title="__('Leave Request')"
                    title-id="leave-request-title">
                    <x-slot name="icon">
                        <x-heroicon-o-calendar-days class="h-5 w-5" />
                    </x-slot>
                </x-user.page-header>

                <div class="user-page-body">
                    
                    {{-- Leave Quota Summary --}}
                    <div class="mb-6 grid grid-cols-1 gap-3">
                        <div class="flex flex-col items-center justify-center rounded-2xl border border-primary-200 bg-primary-50 p-4 text-center transition-colors hover:bg-primary-100/50 dark:border-primary-800/30 dark:bg-primary-900/20">
                            <p class="mb-1 text-sm font-semibold text-primary-800 dark:text-primary-200">{{ __('Annual Leave Quota') }}</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-black text-primary-800 dark:text-primary-200">{{ $remainingExcused ?? 0 }}</span>
                                <span class="text-sm font-semibold text-primary-700/80 dark:text-primary-300/80">/ {{ $annualQuota ?? 12 }}</span>
                            </div>
                            <p class="mt-2 text-sm text-primary-800/80 dark:text-primary-200/80">{{ __('Used') }}: {{ $usedExcused ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="mb-6 rounded-2xl border border-gray-200 bg-gray-50/80 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-300">
                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ __('Before you submit') }}</p>
                        <p class="mt-1 leading-relaxed">
                            {{ __('Choose the correct leave type, set the date range carefully, and attach supporting files when required. Only annual leave types reduce the annual quota; sick leave and special leave types do not use quota.') }}
                        </p>
                    </div>
                    
                    @if ($attendance && ($attendance->time_in || $attendance->time_out))
                        <div class="mb-6 flex gap-3 rounded-xl border border-orange-200 bg-orange-50 p-3 text-sm dark:border-orange-800/50 dark:bg-orange-900/20" role="status" aria-live="polite">
                            <div class="p-1.5 bg-orange-100 dark:bg-orange-900/50 rounded-lg shrink-0 h-fit">
                                <x-heroicon-o-exclamation-triangle class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <h3 class="font-bold text-orange-800 dark:text-orange-300">
                                    {{ __('Attendance Detected') }}
                                </h3>
                                <p class="text-xs text-orange-700 dark:text-orange-400 leading-snug mt-0.5">
                                    {{ __('You have already clocked in/out today.') }}
                                </p>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('store-leave-request') }}" enctype="multipart/form-data" class="space-y-5" aria-describedby="leave-form-help">
                        @csrf
                        <p id="leave-form-help" class="sr-only">{{ __('Complete the leave type, dates, reason, and optional attachment before submitting your request.') }}</p>

                        <fieldset>
                            <legend class="sr-only">{{ __('Leave Type') }}</legend>

                            @if ($leaveTypes->isNotEmpty())
                                <x-forms.label for="leave_type_id" value="{{ __('Leave Type') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <div class="relative z-20">
                                    <x-user.tom-select-user
                                        id="leave_type_id"
                                        name="leave_type_id"
                                        placeholder="{{ __('Select Leave Type') }}"
                                        :selected="old('leave_type_id')"
                                        dropdown-parent="self"
                                        required
                                    >
                                        <option value="" disabled>{{ __('Select Leave Type') }}</option>
                                        @foreach ($leaveTypes as $leaveType)
                                            <option
                                                value="{{ $leaveType->id }}"
                                                data-requires-attachment="{{ ($requireAttachment ?? false) || $leaveType->requires_attachment ? '1' : '0' }}"
                                                @selected((string) old('leave_type_id') === (string) $leaveType->id)
                                            >
                                                {{ $leaveType->name }}
                                            </option>
                                        @endforeach
                                    </x-user.tom-select-user>
                                </div>
                            @else
                                <x-forms.label for="status" value="{{ __('Leave Type') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <x-forms.select id="status" name="status" class="block w-full rounded-xl border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50" required>
                                    <option value="excused" @selected(old('status', 'excused') === 'excused')>{{ __('Annual Leave') }}</option>
                                </x-forms.select>
                            @endif
                            <x-forms.input-error for="leave_type_id" class="mt-2" />
                            <x-forms.input-error for="status" class="mt-2" />
                        </fieldset>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-forms.label for="from" value="{{ __('From Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <x-forms.input type="date" name="from" id="from" class="block w-full rounded-xl border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50"
                                    value="{{ old('from', date('Y-m-d')) }}" required />
                                <x-forms.input-error for="from" class="mt-2" />
                            </div>
                            <div>
                                <x-forms.label for="to" value="{{ __('To Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <div class="relative">
                                    <x-forms.input type="date" name="to" id="to" class="block w-full rounded-xl border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50"
                                        value="{{ old('to') }}" />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-12 pointer-events-none">
                                        <span class="rounded border border-gray-200 bg-white px-2 py-0.5 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ __('Optional') }}</span>
                                    </div>
                                </div>
                                <x-forms.input-error for="to" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-forms.label for="note" value="{{ __('Description / Reason') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                            <x-forms.textarea name="note" id="note" class="block w-full rounded-xl border-gray-200 bg-gray-50 py-3 dark:border-gray-700 dark:bg-gray-900/50" rows="3" placeholder="{{ __('Explain your detailed reason here...') }}" required>{{ old('note') }}</x-forms.textarea>
                            <x-forms.input-error for="note" class="mt-2" />
                        </div>

                        <div
                            x-data="{ fileName: '' }"
                            class="relative rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/30"
                        >
                            <input
                                type="file"
                                name="attachment"
                                id="attachment"
                                accept="image/*,application/pdf"
                                class="sr-only"
                                aria-label="{{ __('Attachment') }}"
                                aria-describedby="attachment-help"
                                x-on:change="fileName = $event.target.files && $event.target.files[0] ? $event.target.files[0].name : ''"
                                {{ ($requireAttachment ?? false) ? 'required' : '' }}
                            />

                            <label
                                for="attachment"
                                class="flex min-h-[4.75rem] w-full cursor-pointer items-center justify-between gap-4 text-left focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-600 focus-within:ring-offset-2 dark:focus-within:ring-offset-gray-900"
                            >
                                <span class="flex min-w-0 items-center gap-3 font-bold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-paper-clip class="h-5 w-5 shrink-0 text-gray-600 dark:text-gray-300" />
                                    <span class="min-w-0">
                                        <span class="block">{{ __('Attachment') }}</span>
                                        <span id="attachment-help" class="mt-1 block truncate text-xs font-medium text-gray-500 dark:text-gray-400" x-text="fileName || @js(__('Choose image or PDF'))"></span>
                                    </span>
                                </span>
                                <span
                                    id="attachment-required-badge"
                                    class="{{ ($requireAttachment ?? false) ? 'shrink-0 rounded bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/20 dark:text-rose-200' : 'shrink-0 rounded bg-white px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}"
                                    data-required-class="shrink-0 rounded bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/20 dark:text-rose-200"
                                    data-optional-class="shrink-0 rounded bg-white px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ ($requireAttachment ?? false) ? __('Required') : __('Optional') }}
                                </span>
                            </label>
                            <x-forms.input-error for="attachment" class="mt-2" />
                        </div>

                        <input type="hidden" name="lat" id="lat" />
                        <input type="hidden" name="lng" id="lng" />

                        <div class="pt-4">
                            <button type="submit" class="flex min-h-[2.75rem] w-full items-center justify-center rounded-xl border border-transparent bg-primary-700 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition-all hover:bg-primary-800">
                                {{ __('Submit Request') }}
                            </button>
                            <div class="mt-4 text-center">
                                <a href="{{ route('home') }}" class="text-sm font-medium text-gray-700 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                                    {{ __('Cancel and Return Home') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
// Validate date range
            const fromInput = document.getElementById('from');
            if (fromInput) {
                fromInput.addEventListener('change', function() {
                    const fromDate = new Date(this.value);
                    const toInput = document.getElementById('to');
                    if (toInput) {
                        toInput.min = this.value;
                        if (toInput.value && new Date(toInput.value) < fromDate) {
                            toInput.value = this.value;
                        }
                    }
                });
            }

            const attachmentInput = document.getElementById('attachment');
            const attachmentBadge = document.getElementById('attachment-required-badge');
            const leaveTypeSelect = document.getElementById('leave_type_id');
            const attachmentRequiredByPolicy = @json((bool) ($requireAttachment ?? false));
            const syncAttachmentRequirement = () => {
                const selected = leaveTypeSelect?.selectedOptions?.[0];
                const requiresAttachment = selected?.dataset.requiresAttachment === '1' || attachmentRequiredByPolicy;

                if (attachmentInput) {
                    attachmentInput.required = requiresAttachment;
                }

                if (attachmentBadge) {
                    attachmentBadge.className = requiresAttachment
                        ? attachmentBadge.dataset.requiredClass
                        : attachmentBadge.dataset.optionalClass;
                    attachmentBadge.textContent = requiresAttachment ? @json(__('Required')) : @json(__('Optional'));
                }
            };

            leaveTypeSelect?.addEventListener('change', syncAttachmentRequirement);
            syncAttachmentRequirement();

            /*
            // Get user location (Disabled to prevent focus shift on mobile)
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const latEl = document.getElementById('lat');
                    const lngEl = document.getElementById('lng');
                    if(latEl) latEl.value = position.coords.latitude;
                    if(lngEl) lngEl.value = position.coords.longitude;
                });
            }
            */
        </script>
    @endpush
</x-app-layout>
