<div>
    @if ($approvedAbsence)
        <section aria-labelledby="attendance-status-date" class="wcag-card rounded-3xl p-6">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('Your Status') }}
                    </p>
                    <h2 id="attendance-status-date" class="text-xl font-bold leading-tight text-gray-950 dark:text-white">
                        {{ $approvedAbsence->date->translatedFormat('l, d F Y') }}
                    </h2>
                </div>

                <div class="flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 dark:border-green-800 dark:bg-green-900/30"
                    role="status" aria-live="polite">
                    <div
                        class="relative flex h-2.5 w-2.5 items-center justify-center rounded-full bg-green-700 dark:bg-green-400">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    </div>
                    <span class="text-sm font-semibold leading-none text-green-800 dark:text-green-200">
                        {{ __(ucfirst($approvedAbsence->status)) }}
                    </span>
                </div>
            </div>

            <div
                class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700/50 dark:bg-gray-700/30">
                <div
                    class="flex-shrink-0 rounded-xl bg-white p-2.5 text-gray-700 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                    <x-heroicon-m-document-text class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Note') }}</h3>
                    <p class="text-sm font-medium italic leading-relaxed text-gray-900 dark:text-white">
                        "{{ $approvedAbsence->note }}"
                    </p>
                </div>
            </div>
        </section>
    @elseif($requiresFaceEnrollment)
        <section aria-labelledby="face-enrollment-heading"
            class="wcag-card group relative overflow-hidden rounded-3xl p-6 text-center transition-all hover:shadow-lg">
            <div
                class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50">
            </div>

            <div class="relative z-10">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-100 text-primary-700 shadow-sm dark:bg-primary-900/40 dark:text-primary-300">
                    <x-heroicon-m-face-smile class="w-8 h-8" />
                </div>

                <h2 id="face-enrollment-heading" class="mb-2 text-lg font-bold text-gray-950 dark:text-white">
                    {{ __('Face ID Registration Required') }}</h2>
                <p class="mx-auto mb-6 max-w-xs text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                    {{ __('To ensure security, you must register your face data before you can clock in/out.') }}
                </p>

                @if (\App\Helpers\Editions::attendanceLocked())
                    <button type="button"
                        @click.prevent="$dispatch('feature-lock', { title: @js(__('Face ID Locked')), message: @js(__('Face ID Biometrics is an Enterprise Feature. Please Upgrade.')) })"
                        class="inline-flex min-h-[2.75rem] w-full items-center justify-center gap-2 rounded-xl bg-primary-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition-all sm:w-auto">
                        <x-heroicon-m-camera class="w-5 h-5" />
                        {{ __('Register Face ID Now') }}
                        <x-heroicon-o-lock-closed class="h-4 w-4" />
                    </button>
                @else
                    <a href="{{ route('face.enrollment') }}"
                        class="inline-flex min-h-[2.75rem] w-full items-center justify-center gap-2 rounded-xl bg-primary-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition-all hover:bg-primary-800 sm:w-auto">
                        <x-heroicon-m-camera class="w-5 h-5" />
                        {{ __('Register Face ID Now') }}
                    </a>
                @endif
            </div>
        </section>
    @elseif($hasCheckedIn && $hasCheckedOut)
        <x-user.attendance-hero-card :attendance="$attendance" />
    @else
        <x-user.home-actions-card :hasCheckedIn="$hasCheckedIn" :hasCheckedOut="$hasCheckedOut" :attendance="$attendance" :hasApprovedOvertime="$hasApprovedOvertime" />
    @endif
</div>
