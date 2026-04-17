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
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
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
                            {{ __('Choose the correct leave type, set the date range carefully, and attach supporting files when required. Annual leave requests beyond the remaining quota will be rejected automatically.') }}
                        </p>
                    </div>
                    
                    @if ($attendance && ($attendance->time_in || $attendance->time_out))
                        <div class="mb-6 flex gap-3 rounded-xl border border-orange-200 bg-orange-50 p-3 text-sm dark:border-orange-800/50 dark:bg-orange-900/20" role="status" aria-live="polite">
                            <div class="p-1.5 bg-orange-100 dark:bg-orange-900/50 rounded-lg shrink-0 h-fit">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
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
                            
                            {{-- compact grid: side-by-side on mobile --}}
                            <div class="grid grid-cols-2 gap-3">
                                {{-- Option 1: Excused / Cuti --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="status" value="excused" class="peer sr-only" {{ old('status') == 'excused' ? 'checked' : '' }} required>
                                    
                                    <div class="p-3 rounded-xl border-2 border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-200 dark:hover:border-primary-800 transition-all duration-200 peer-checked:border-primary-500 dark:peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500 h-full flex items-center">
                                        <div class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-2 sm:gap-3 w-full">
                                            <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300 shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                            <div>
                                                <h2 class="text-sm font-bold leading-tight text-gray-900 dark:text-white">{{ __('Excused / Annual Leave') }}</h2>
                                                <p class="mt-0.5 text-sm leading-snug text-gray-700 dark:text-gray-300">{{ __('Annual Leave or Personal') }}</p>
                                            </div>
                                            
                                            {{-- Checkmark --}}
                                            <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity text-primary-600 dark:text-primary-400 transform scale-50 peer-checked:scale-100 duration-200">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                {{-- Option 2: Sick / Sakit --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="status" value="sick" class="peer sr-only" {{ old('status') == 'sick' ? 'checked' : '' }} required>
                                    
                                    <div class="p-3 rounded-xl border-2 border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-rose-200 dark:hover:border-rose-800 transition-all duration-200 peer-checked:border-rose-500 dark:peer-checked:border-rose-500 peer-checked:bg-rose-50 dark:peer-checked:bg-rose-900/20 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-rose-500 h-full flex items-center">
                                        <div class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-2 sm:gap-3 w-full">
                                            <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform duration-300 shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                            </div>
                                            <div>
                                                <h2 class="text-sm font-bold leading-tight text-gray-900 dark:text-white">{{ __('Sick Leave') }}</h2>
                                                <p class="mt-0.5 text-sm leading-snug text-gray-700 dark:text-gray-300">{{ __('Requires Medical Certificate') }}</p>
                                            </div>
                                            
                                            {{-- Checkmark --}}
                                            <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity text-rose-600 dark:text-rose-400 transform scale-50 peer-checked:scale-100 duration-200">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
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
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
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
