<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Apply Leave') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-indigo-100 bg-white shadow-xl shadow-indigo-100/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none relative overflow-hidden transition-all">
                
                {{-- Decorative Blob --}}
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

                <div class="p-6 sm:p-8 relative z-10">

                    <div class="flex items-center justify-between mb-8">
                        <x-secondary-button href="{{ url()->previous() }}" class="!rounded-full !px-3 !py-2 !border-0 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                        </x-secondary-button>
                        
                        <h2 class="text-lg font-bold text-gray-800 dark:text-white uppercase tracking-wider">
                            {{ __('Leave Request Form') }}
                        </h2>
                        
                        <div class="w-10"></div> {{-- Spacer for center alignment --}}
                    </div>
                    
                    {{-- Leave Quota Summary --}}
                    <div class="mb-8 grid grid-cols-2 gap-4 sm:gap-6">
                        <div class="relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-sky-50 to-cyan-50 dark:from-sky-900/30 dark:to-cyan-900/30 border border-sky-100 dark:border-sky-700/30 shadow-sm group hover:shadow-md transition-all">
                            <div class="absolute -right-4 -top-4 w-16 h-16 bg-sky-200/20 rounded-full blur-xl"></div>
                            <div class="text-center relative z-10">
                                <p class="text-[10px] sm:text-xs font-bold text-sky-600 dark:text-sky-400 uppercase tracking-widest mb-2">{{ __('Remaining Annual Leave') }}</p>
                                <div class="flex items-baseline justify-center gap-1">
                                    <span class="text-3xl sm:text-4xl font-black text-sky-700 dark:text-sky-300 group-hover:scale-110 transition-transform">{{ $remainingExcused ?? 0 }}</span>
                                    <span class="text-xs font-semibold text-sky-500/70">/ {{ $annualQuota ?? 12 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-violet-50 to-fuchsia-50 dark:from-violet-900/30 dark:to-fuchsia-900/30 border border-violet-100 dark:border-violet-700/30 shadow-sm group hover:shadow-md transition-all">
                            <div class="absolute -right-4 -top-4 w-16 h-16 bg-violet-200/20 rounded-full blur-xl"></div>
                            <div class="text-center relative z-10">
                                <p class="text-[10px] sm:text-xs font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest mb-2">{{ __('Remaining Sick Leave') }}</p>
                                <div class="flex items-baseline justify-center gap-1">
                                    <span class="text-3xl sm:text-4xl font-black text-violet-700 dark:text-violet-300 group-hover:scale-110 transition-transform">{{ $remainingSick ?? 0 }}</span>
                                    <span class="text-xs font-semibold text-violet-500/70">/ {{ $sickQuota ?? 14 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if ($attendance && ($attendance->time_in || $attendance->time_out))
                        {{-- Warning jika sudah clock in/out hari ini --}}
                        <div
                            class="mb-8 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-xl flex gap-3 shadow-sm">
                            <div class="p-2 bg-amber-100 dark:bg-amber-900/50 rounded-lg shrink-0">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-amber-800 dark:text-amber-300">
                                    {{ __('Attention: Attendance Detected') }}
                                </h3>
                                <div class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                    <p>
                                        {{ __('You have already clocked in/out today:') }}
                                        <span class="font-mono bg-amber-100/50 px-1 rounded">
                                            @if ($attendance->time_in) IN: {{ \App\Helpers::format_time($attendance->time_in) }} @endif
                                            @if ($attendance->time_out) | OUT: {{ \App\Helpers::format_time($attendance->time_out) }} @endif
                                        </span>
                                    </p>
                                    <p class="mt-1 font-medium">{{ __('You cannot request leave for a date with existing attendance.') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Form pengajuan izin --}}
                    <form method="POST" action="{{ route('store-leave-request') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        {{-- Form fields... --}}
                        <div>
                            <x-label for="status" value="{{ __('Leave Type') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                            <div class="relative">
                                <select name="status" id="status" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all py-3 pl-4 pr-10" required>
                                    <option value="" disabled {{ old('status') ? '' : 'selected' }}>{{ __('Select Leave Type') }}</option>
                                    <option value="excused" {{ old('status') == 'excused' ? 'selected' : '' }}>{{ __('Excused (Deduct Annual Leave)') }}</option>
                                    <option value="sick" {{ old('status') == 'sick' ? 'selected' : '' }}>{{ __('Sick (Medical Certificate)') }}</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                            <x-input-error for="status" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-label for="from" value="{{ __('From Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <input type="date" name="from" id="from" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all py-3 px-4"
                                    value="{{ old('from', date('Y-m-d')) }}" required />
                                <x-input-error for="from" class="mt-2" />
                            </div>
                            <div>
                                <x-label for="to" value="{{ __('To Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <div class="relative">
                                    <input type="date" name="to" id="to" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all py-3 px-4"
                                        value="{{ old('to') }}" />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                         <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ __('Optional') }}</span>
                                    </div>
                                </div>
                                <x-input-error for="to" class="mt-2" />
                                <p class="mt-2 text-[10px] text-gray-400">{{ __('Leave blank if only for 1 day.') }}</p>
                            </div>
                        </div>

                        <div>
                            <x-label for="note" value="{{ __('Description / Reason') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                            <textarea name="note" id="note" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all py-3 px-4" rows="4" placeholder="{{ __('Explain the reason for leave request...') }}" required>{{ old('note') }}</textarea>
                            <x-input-error for="note" class="mt-2" />
                        </div>

                        <div class="p-5 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-700/50 border-dashed">
                            <x-label for="attachment" class="mb-2 font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                {{ __('Document Attachment') }}
                                @if($requireAttachment ?? false)
                                    <span class="text-rose-500 text-xs font-bold uppercase tracking-wider bg-rose-50 dark:bg-rose-900/30 px-1.5 py-0.5 rounded ml-2">{{ __('Required') }}</span>
                                @else
                                    <span class="text-gray-400 text-xs font-normal ml-2">({{ __('Optional, but recommended') }})</span>
                                @endif
                            </x-label>
                            
                            <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:bg-white dark:hover:bg-gray-800/50 transition-colors cursor-pointer relative" onclick="document.getElementById('attachment').click()">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                        <label for="attachment-dummy" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>{{ __('Upload a file') }}</span>
                                        </label>
                                        <p class="pl-1">{{ __('or drag and drop') }}</p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        PNG, JPG, PDF up to 3MB
                                    </p>
                                </div>
                                <input type="file" name="attachment" id="attachment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                accept="image/*,application/pdf"
                                {{ ($requireAttachment ?? false) ? 'required' : '' }} />
                            </div>
                            <x-input-error for="attachment" class="mt-2" />
                        </div>

                        <input type="hidden" name="lat" id="lat" />
                        <input type="hidden" name="lng" id="lng" />

                        <div class="flex items-center justify-end pt-6 border-t border-gray-100 dark:border-gray-700/50">
                            <x-secondary-button href="{{ route('home') }}" class="mr-4 !rounded-xl !py-2.5 !px-5 text-gray-500 hover:text-gray-700">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest shadow-lg shadow-indigo-200 dark:shadow-none hover:shadow-xl active:scale-[0.98] transition-all disabled:opacity-25 disabled:cursor-not-allowed">
                                {{ __('Submit Request') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
