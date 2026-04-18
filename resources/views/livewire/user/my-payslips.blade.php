<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-payslips-title" class="user-page-surface relative">
            <x-user.page-header
                :back-href="!($needsSetup && Auth::user()->hasValidPayslipPassword()) ? route('home') : null"
                :title="$needsSetup ? __('Secure Access') : __('My Payslips')"
                title-id="my-payslips-title"
                class="border-b-0">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-50 via-white to-lime-50 text-emerald-700 ring-1 ring-inset ring-emerald-100 shadow-sm dark:from-emerald-900/30 dark:via-gray-800 dark:to-lime-900/20 dark:text-emerald-300 dark:ring-emerald-800/60">
                        <x-heroicon-o-banknotes class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    @if($needsSetup && Auth::user()->hasValidPayslipPassword())
                        <button wire:click="cancelReset" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                            <span>{{ __('Back') }}</span>
                        </button>
                    @elseif(!$needsSetup)
                        <button wire:click="triggerReset" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-o-lock-closed class="h-5 w-5" />
                            <span>{{ __('Reset Password') }}</span>
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if($needsSetup)
                    {{-- Password Setup Form --}}
                    <div class="p-6 lg:p-8">
                        <div class="max-w-md mx-auto">
                            <div class="text-center mb-8">
                                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 mb-4">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Secure Your Payslips') }}</h3>
                                <p class="text-sm text-gray-500 mt-2 px-2">{{ __('Please set a password to access your encrypted payslip files.') }}</p>
                            </div>

                            <form wire:submit.prevent="setupPassword" class="space-y-5">
                                <div class="space-y-1">
                                    <x-forms.label for="new_password" value="{{ __('New Password') }}" class="ml-1 text-xs uppercase tracking-wider text-gray-500" />
                                    <x-forms.input id="new_password" type="password" class="block w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500/20" wire:model="new_password" required placeholder="••••••••" />
                                    <x-forms.input-error for="new_password" />
                                </div>
                                <div class="space-y-1">
                                    <x-forms.label for="new_password_confirmation" value="{{ __('Confirm Password') }}" class="ml-1 text-xs uppercase tracking-wider text-gray-500" />
                                    <x-forms.input id="new_password_confirmation" type="password" class="block w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500/20" wire:model="new_password_confirmation" required placeholder="••••••••" />
                                </div>
                                <div class="flex flex-col-reverse items-stretch justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 sm:flex-row">
                                    @if(Auth::user()->hasValidPayslipPassword())
                                        <x-actions.secondary-button wire:click="cancelReset" wire:loading.attr="disabled">
                                            {{ __('Cancel') }}
                                        </x-actions.secondary-button>
                                    @endif
                                    <x-actions.button wire:loading.attr="disabled">
                                        {{ __('Save Password') }}
                                    </x-actions.button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    {{-- Payslips List --}}
                    @if($payrolls->isEmpty())
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No Payslips Yet') }}</h3>
                            <p class="user-empty-state__copy">{{ __('Salary statements will appear here.') }}</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($payrolls as $payroll)
                                <div class="p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/50 sm:p-6">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 rounded-xl flex items-center justify-center bg-emerald-100 dark:bg-emerald-900/30">
                                            <span class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">{{ \Carbon\Carbon::createFromDate(null, $payroll->month)->translatedFormat('M') }}</span>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white capitalize">
                                                {{ \Carbon\Carbon::createFromDate(null, $payroll->month)->translatedFormat('F') }} {{ $payroll->year }}
                                            </h4>
                                            <div x-data="{ show: false }" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span x-show="!show">Rp *********</span>
                                                <span x-show="show" style="display: none;">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                                                <button @click="show = !show" class="text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none">
                                                    <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <svg x-show="show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                </button>
                                            </div>
                                            <p class="text-[10px] text-gray-400 mt-0.5">{{ __('Generated on') }} {{ $payroll->created_at->format('d/m/Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-medium bg-green-50 text-green-700 border border-green-100 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/30">
                                            {{ __(ucfirst($payroll->status)) }}
                                        </span>
                                        <button wire:click="download('{{ $payroll->id }}')" class="px-3 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-bold text-xs uppercase tracking-widest transition shadow-lg shadow-primary-500/30 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            <span class="hidden sm:inline">{{ __('Download') }}</span>
                                        </button>
                                    </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="rounded-b-2xl border-t border-gray-100 p-4 dark:border-gray-700">
                            {{ $payrolls->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
