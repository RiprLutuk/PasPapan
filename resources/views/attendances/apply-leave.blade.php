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
                            <legend class="mb-3 block text-base font-semibold text-gray-900 dark:text-white">{{ __('Leave Type') }}</legend>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @forelse ($leaveTypes as $leaveType)
                                    @php
                                        $isSick = $leaveType->category === \App\Models\LeaveType::CATEGORY_SICK;
                                        $isAnnual = $leaveType->counts_against_quota;
                                        $checked = old('leave_type_id')
                                            ? (string) old('leave_type_id') === (string) $leaveType->id
                                            : $loop->first;
                                        $toneClass = $isSick
                                            ? 'hover:border-rose-200 dark:hover:border-rose-800 peer-checked:border-rose-500 dark:peer-checked:border-rose-500 peer-checked:bg-rose-50 dark:peer-checked:bg-rose-900/20 peer-focus-visible:ring-rose-500'
                                            : ($isAnnual
                                                ? 'hover:border-primary-200 dark:hover:border-primary-800 peer-checked:border-primary-500 dark:peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 peer-focus-visible:ring-primary-500'
                                                : 'hover:border-sky-200 dark:hover:border-sky-800 peer-checked:border-sky-500 dark:peer-checked:border-sky-500 peer-checked:bg-sky-50 dark:peer-checked:bg-sky-900/20 peer-focus-visible:ring-sky-500');
                                    @endphp
                                    <label class="group relative cursor-pointer">
                                        <input type="radio" name="leave_type_id" value="{{ $leaveType->id }}" class="peer sr-only" data-requires-attachment="{{ ($requireAttachment ?? false) || $leaveType->requires_attachment ? '1' : '0' }}" {{ $checked ? 'checked' : '' }} required>

                                        <div class="flex h-full items-start gap-3 rounded-xl border-2 border-gray-100 bg-white p-3 transition-all duration-200 peer-checked:shadow-sm peer-focus-visible:ring-2 dark:border-gray-700 dark:bg-gray-800 {{ $toneClass }}">
                                            <div class="shrink-0 rounded-lg p-2 {{ $isSick ? 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400' : ($isAnnual ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300') }}">
                                                @if ($isSick)
                                                    <x-heroicon-o-heart class="h-5 w-5" />
                                                @elseif ($isAnnual)
                                                    <x-heroicon-o-calendar-days class="h-5 w-5" />
                                                @else
                                                    <x-heroicon-o-document-text class="h-5 w-5" />
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h2 class="text-sm font-bold leading-tight text-gray-900 dark:text-white">{{ $leaveType->name }}</h2>
                                                @if ($leaveType->description)
                                                    <p class="mt-0.5 text-sm leading-snug text-gray-700 dark:text-gray-300">{{ $leaveType->description }}</p>
                                                @endif
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @if ($isAnnual)
                                                        <span class="rounded bg-primary-100 px-2 py-0.5 text-[11px] font-semibold text-primary-800 dark:bg-primary-900/40 dark:text-primary-200">{{ __('Uses annual quota') }}</span>
                                                    @else
                                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ __('No quota') }}</span>
                                                    @endif
                                                    @if ($leaveType->requires_attachment)
                                                        <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ __('Attachment') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="opacity-0 transition-opacity duration-200 peer-checked:opacity-100">
                                                <x-heroicon-s-check-circle class="h-5 w-5 {{ $isSick ? 'text-rose-600 dark:text-rose-400' : 'text-primary-600 dark:text-primary-400' }}" />
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <label class="relative cursor-pointer group">
                                        <input type="radio" name="status" value="excused" class="peer sr-only" {{ old('status') == 'excused' ? 'checked' : '' }} required>
                                        <div class="p-3 rounded-xl border-2 border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-200 dark:hover:border-primary-800 transition-all duration-200 peer-checked:border-primary-500 dark:peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500 h-full flex items-center">
                                            <div>
                                                <h2 class="text-sm font-bold leading-tight text-gray-900 dark:text-white">{{ __('Annual Leave') }}</h2>
                                                <p class="mt-0.5 text-sm leading-snug text-gray-700 dark:text-gray-300">{{ __('Uses annual quota') }}</p>
                                            </div>
                                        </div>
                                    </label>
                                @endforelse
                            </div>
                            <x-forms.input-error for="leave_type_id" class="mt-2" />
                            <x-forms.input-error for="status" class="mt-2" />
                        </fieldset>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-forms.label for="from" value="{{ __('From Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <input type="date" name="from" id="from" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4"
                                    value="{{ old('from', date('Y-m-d')) }}" required />
                                <x-forms.input-error for="from" class="mt-2" />
                            </div>
                            <div>
                                <x-forms.label for="to" value="{{ __('To Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <div class="relative">
                                    <input type="date" name="to" id="to" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4"
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
                            <textarea name="note" id="note" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" rows="3" placeholder="{{ __('Explain your detailed reason here...') }}" required>{{ old('note') }}</textarea>
                            <x-forms.input-error for="note" class="mt-2" />
                        </div>

                        <div class="p-5 bg-gray-50 dark:bg-gray-900/30 rounded-2xl border border-gray-200 dark:border-gray-700 border-dashed">
                            <x-forms.label for="attachment" class="mb-3 font-bold text-gray-700 dark:text-gray-300 flex items-center justify-between">
                                <span class="flex items-center gap-2">
                                    <x-heroicon-o-paper-clip class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                                    {{ __('Attachment') }}
                                </span>
                                @if($requireAttachment ?? false)
                                    <span class="rounded bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/20 dark:text-rose-200">{{ __('Required') }}</span>
                                @else
                                    <span class="rounded bg-white px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ __('Optional') }}</span>
                                @endif
                            </x-forms.label>
                            
                            <input type="file" name="attachment" id="attachment" 
                                class="block w-full cursor-pointer text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-800 hover:file:bg-primary-200 dark:file:bg-primary-900/30 dark:file:text-primary-200"
                                accept="image/*,application/pdf"
                                {{ ($requireAttachment ?? false) ? 'required' : '' }} />
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
            const leaveTypeInputs = document.querySelectorAll('input[name="leave_type_id"][data-requires-attachment]');
            const syncAttachmentRequirement = () => {
                const selected = document.querySelector('input[name="leave_type_id"][data-requires-attachment]:checked');

                if (attachmentInput && selected) {
                    attachmentInput.required = selected.dataset.requiresAttachment === '1';
                }
            };

            leaveTypeInputs.forEach((input) => input.addEventListener('change', syncAttachmentRequirement));
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
