<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Apply Leave') }}
        </h2>
    </x-slot>

    <div class="py-6 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                
                <div class="p-5 lg:p-10">

                    <div class="flex items-center justify-between mb-6">
                        <x-secondary-button href="{{ route('home') }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                            <x-heroicon-o-arrow-left class="h-5 w-5 text-gray-500 dark:text-gray-300" />
                        </x-secondary-button>
                        
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ __('Leave Request') }}
                        </h2>
                        
                        <div class="w-10"></div>
                    </div>
                    
                    {{-- Leave Quota Summary --}}
                    <div class="mb-6 grid grid-cols-1 gap-3">
                        <div class="p-4 rounded-2xl bg-primary-50 dark:bg-primary-900/20 border border-primary-100 dark:border-primary-800/30 flex flex-col items-center justify-center text-center group transition-colors hover:bg-primary-100/50">
                            <p class="text-[10px] font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-1">{{ __('Annual Leave Quota') }}</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-black text-primary-700 dark:text-primary-300">{{ $remainingExcused ?? 0 }}</span>
                                <span class="text-[10px] font-semibold text-primary-400 dark:text-primary-500">/ {{ $annualQuota ?? 12 }}</span>
                            </div>
                            <p class="mt-2 text-[11px] text-primary-700/80 dark:text-primary-300/80">{{ __('Used') }}: {{ $usedExcused ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="mb-6 rounded-2xl border border-gray-200 bg-gray-50/80 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-300">
                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ __('Before you submit') }}</p>
                        <p class="mt-1 leading-relaxed">
                            {{ __('Choose the correct leave type, set the date range carefully, and attach supporting files when required. Annual leave requests beyond the remaining quota will be rejected automatically.') }}
                        </p>
                    </div>
                    
                    @if ($attendance && ($attendance->time_in || $attendance->time_out))
                        <div class="mb-6 p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800/50 rounded-xl flex gap-3 text-sm">
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

                    <form method="POST" action="{{ route('store-leave-request') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <div>
                            <label class="mb-3 block font-bold text-gray-700 dark:text-gray-300">{{ __('Leave Type') }}</label>
                            
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
                                                <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ __('Excused / Annual Leave') }}</h3>
                                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">{{ __('Annual Leave or Personal') }}</p>
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
                                                <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ __('Sick Leave') }}</h3>
                                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">{{ __('Requires Medical Certificate') }}</p>
                                            </div>
                                            
                                            {{-- Checkmark --}}
                                            <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity text-rose-600 dark:text-rose-400 transform scale-50 peer-checked:scale-100 duration-200">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <x-input-error for="status" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-label for="from" value="{{ __('From Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <input type="date" name="from" id="from" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4"
                                    value="{{ old('from', date('Y-m-d')) }}" required />
                                <x-input-error for="from" class="mt-2" />
                            </div>
                            <div>
                                <x-label for="to" value="{{ __('To Date') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                                <div class="relative">
                                    <input type="date" name="to" id="to" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4"
                                        value="{{ old('to') }}" />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-12 pointer-events-none">
                                        <span class="text-[10px] text-gray-400 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-100 dark:border-gray-700">{{ __('Optional') }}</span>
                                    </div>
                                </div>
                                <x-input-error for="to" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-label for="note" value="{{ __('Description / Reason') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                            <textarea name="note" id="note" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" rows="3" placeholder="{{ __('Explain your detailed reason here...') }}" required>{{ old('note') }}</textarea>
                            <x-input-error for="note" class="mt-2" />
                        </div>

                        <div class="p-5 bg-gray-50 dark:bg-gray-900/30 rounded-2xl border border-gray-200 dark:border-gray-700 border-dashed">
                            <x-label for="attachment" class="mb-3 font-bold text-gray-700 dark:text-gray-300 flex items-center justify-between">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    {{ __('Attachment') }}
                                </span>
                                @if($requireAttachment ?? false)
                                    <span class="text-[10px] font-bold text-rose-500 bg-rose-50 dark:bg-rose-900/20 px-2 py-1 rounded">{{ __('REQUIRED') }}</span>
                                @else
                                    <span class="text-[10px] font-medium text-gray-400 bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ __('OPTIONAL') }}</span>
                                @endif
                            </x-label>
                            
                            <input type="file" name="attachment" id="attachment" 
                                class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-400 transition-all cursor-pointer"
                                accept="image/*,application/pdf"
                                {{ ($requireAttachment ?? false) ? 'required' : '' }} />
                            <x-input-error for="attachment" class="mt-2" />
                        </div>

                        <input type="hidden" name="lat" id="lat" />
                        <input type="hidden" name="lng" id="lng" />

                        <div class="pt-4">
                            <button type="submit" class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-primary-500/30 text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all active:scale-[0.98]">
                                {{ __('Submit Request') }}
                            </button>
                            <div class="mt-4 text-center">
                                <a href="{{ route('home') }}" class="text-xs font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                                    {{ __('Cancel and Return Home') }}
                                </a>
                            </div>
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
