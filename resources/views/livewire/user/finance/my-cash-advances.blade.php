<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-kasbon-title" class="user-page-surface relative">
            <x-user.page-header
                :back-href="!$showCreateModal ? route('home') : null"
                :title="$showCreateModal ? __('Request Kasbon') : __('My Kasbon')"
                title-id="my-kasbon-title"
                class="border-b-0">
                <x-slot name="icon">
                    <span class="text-lg leading-none">💸</span>
                </x-slot>
                <x-slot name="actions">
                    @if($showCreateModal)
                        <button wire:click="$set('showCreateModal', false)" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                            <span>{{ __('Back') }}</span>
                        </button>
                    @else
                        <button wire:click="openCreateModal" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700">
                            <x-heroicon-m-plus class="h-5 w-5" />
                            <span>{{ __('Request Kasbon') }}</span>
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">

                @if($showCreateModal)
                {{-- CREATE FORM --}}
                <div class="mx-auto max-w-3xl rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:p-8">
                <form wire:submit.prevent="submit" class="space-y-6">

                    {{-- Amount --}}
                    <div>
                        <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <span class="text-gray-500 dark:text-gray-400 font-bold">Rp</span>
                            </div>
                            <input
                                type="text"
                                class="block w-full pl-12 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4 font-bold text-lg"
                                x-data
                                x-mask:dynamic="$money($input, '.', ',')"
                                wire:model.defer="amount"
                                placeholder="0" />
                        </div>
                        <x-forms.input-error for="amount" class="mt-2" />
                    </div>

                    {{-- Purpose --}}
                    <div>
                        <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Purpose') }}</label>
                        <textarea wire:model.defer="purpose" rows="3" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" placeholder="{{ __('Purpose of Kasbon') }}"></textarea>
                        <x-forms.input-error for="purpose" class="mt-2" />
                    </div>

                    {{-- Deduction Target --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Payment Month --}}
                        <div>
                            <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Payment Month') }}</label>
                            <select wire:model.defer="payment_month" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                                    @endfor
                            </select>
                            <x-forms.input-error for="payment_month" class="mt-2" />
                        </div>

                        {{-- Payment Year --}}
                        <div>
                            <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Payment Year') }}</label>
                            <input type="number" wire:model.defer="payment_year" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" />
                            <x-forms.input-error for="payment_year" class="mt-2" />
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                        <p class="text-sm text-orange-800 dark:text-orange-300 flex items-start gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span><strong class="font-bold">{{ __('IMPORTANT') }}:</strong> {{ __('If approved, this amount will be automatically deducted from your payroll for the month and year you selected above.') }}</span>
                        </p>
                    </div>

                    <div class="flex flex-col-reverse items-stretch justify-end gap-3 pt-4 sm:flex-row">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-5 py-3 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 sm:flex-none px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-lg shadow-primary-500/30 transition transform active:scale-95 disabled:opacity-50">
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </form>
                </div>

                @else
                {{-- LIST VIEW --}}

                {{-- Summary Cards (Compact Mode) --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                    {{-- Unpaid --}}
                    <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10 p-3 flex items-center gap-3">
                        <div class="h-10 w-10 shrink-0 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <x-heroicon-m-clock class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">{{ __('Unpaid') }}</p>
                            <p class="text-sm font-black text-amber-900 dark:text-amber-200 mt-0.5">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Paid --}}
                    <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10 p-3 flex items-center gap-3">
                        <div class="h-10 w-10 shrink-0 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <x-heroicon-m-check-badge class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-green-700 dark:text-green-400 uppercase tracking-wider">{{ __('Paid') }}</p>
                            <p class="text-sm font-black text-green-900 dark:text-green-200 mt-0.5">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Limit --}}
                    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/10 p-3 flex items-center gap-3">
                        <div class="h-10 w-10 shrink-0 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <x-heroicon-m-shield-check class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">{{ __('Kasbon Limit') }}</p>
                            <p class="text-sm font-black text-blue-900 dark:text-blue-200 mt-0.5">Rp {{ number_format($basicSalary, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                @if($advances->isEmpty())
                <div class="user-empty-state">
                    <div class="user-empty-state__icon">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="user-empty-state__title">{{ __('No cash advance data found.') }}</h3>
                    <p class="user-empty-state__copy">{{ __('No cash advance requests yet.') }}</p>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($advances as $advance)
                    <div class="group rounded-2xl border border-gray-100 bg-white p-3 transition-all duration-200 hover:border-primary-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-800 sm:p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3 overflow-hidden sm:gap-4">
                                {{-- Icon --}}
                                <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110 bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-0.5">
                                        <h4 class="font-bold text-gray-900 dark:text-white capitalize truncate text-sm sm:text-base">{{ __('Deduction Target') }}: {{ \Carbon\Carbon::create()->month((int)$advance->payment_month)->translatedFormat('F') }} {{ $advance->payment_year }}</h4>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wide
                                                        @if($advance->status === 'approved') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                                        @elseif($advance->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                                        @elseif($advance->status === 'paid') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                                        @else bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 @endif">
                                            {{ __($advance->status === 'pending' ? 'Pending' : ($advance->status === 'approved' ? 'Approved' : ($advance->status === 'paid' ? 'Paid' : 'Rejected'))) }}
                                        </span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 line-clamp-1 break-all">{{ $advance->purpose }}</p>
                                    <div class="text-[10px] text-gray-400 mt-0.5 sm:mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ $advance->created_at->format('d M Y') }}
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0 flex flex-col items-start gap-1 pl-0 text-left sm:items-end sm:pl-4 sm:text-right">
                                <p class="text-sm sm:text-lg font-black text-gray-900 dark:text-white tracking-tight">
                                    <span class="text-[10px] sm:text-xs text-gray-400 font-normal mr-0.5">Rp</span>{{ number_format($advance->amount, 0, ',', '.') }}
                                </p>
                                @if($advance->status === 'pending')
                                <button wire:click="delete({{ $advance->id }})" wire:confirm="{{ __('Are you sure you want to cancel this request?') }}" class="text-[10px] font-medium text-red-500 hover:text-red-700 transition">
                                    {{ __('Cancel') }}
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    {{ $advances->links() }}
                </div>
                @endif
                @endif

            </div>
        </section>
    </div>
</div>
