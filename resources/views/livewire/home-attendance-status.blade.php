<div>
    @if($approvedAbsence)
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                        {{ __('Your Status') }}
                    </p>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                        {{ $approvedAbsence->date->translatedFormat('l, d F Y') }}
                    </h2>
                </div>
                
                <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/30 px-3 py-1.5 rounded-full border border-green-100 dark:border-green-800">
                    <div class="relative flex h-2.5 w-2.5 items-center justify-center rounded-full bg-green-500">
                         <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    </div>
                    <span class="text-xs font-bold text-green-600 dark:text-green-400 leading-none uppercase">
                        {{ __(ucfirst($approvedAbsence->status)) }}
                    </span>
                </div>
            </div>

            <div class="bg-gray-50/50 dark:bg-gray-700/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 flex items-start gap-4">
                <div class="p-2.5 bg-white dark:bg-gray-700 rounded-xl shadow-sm text-gray-400 dark:text-gray-400 flex-shrink-0">
                    <x-heroicon-m-document-text class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ __('Note') }}</h4>
                    <p class="text-sm font-medium text-gray-900 dark:text-white italic leading-relaxed">
                        "{{ $approvedAbsence->note }}"
                    </p>
                </div>
            </div>
        </div>
    @elseif($requiresFaceEnrollment)
         <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 text-center relative overflow-hidden group hover:shadow-lg transition-all">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50"></div>
            
            <div class="relative z-10">
                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4 text-primary-600 dark:text-primary-400 shadow-sm">
                    <x-heroicon-m-face-smile class="w-8 h-8" />
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('Face ID Registration Required') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 leading-relaxed max-w-xs mx-auto">
                    {{ __('To ensure security, you must register your face data before you can clock in/out.') }}
                </p>

                @if(\App\Helpers\Editions::attendanceLocked())
                    <button type="button" @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })" class="inline-flex items-center justify-center gap-2 px-6 py-3 w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-primary-500/30 transition-all active:scale-95">
                        <x-heroicon-m-camera class="w-5 h-5" />
                        {{ __('Register Face ID Now') }} 🔒
                    </button>
                @else
                    <a href="{{ route('face.enrollment') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-primary-500/30 transition-all active:scale-95">
                        <x-heroicon-m-camera class="w-5 h-5" />
                        {{ __('Register Face ID Now') }}
                    </a>
                @endif
            </div>
        </div>
    @elseif($hasCheckedIn && $hasCheckedOut)
        <x-attendance-hero-card :attendance="$attendance" />
    @else
        <x-home-actions-card 
            :hasCheckedIn="$hasCheckedIn" 
            :hasCheckedOut="$hasCheckedOut" 
            :attendance="$attendance" 
            :hasApprovedOvertime="$hasApprovedOvertime"
        />
    @endif
</div>
