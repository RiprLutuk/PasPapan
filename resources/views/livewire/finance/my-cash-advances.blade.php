<div class="py-6 lg:py-12">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $showCreateModal ? __('Request Kasbon') : __('Kasbon Saya') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">

            <div class="p-4 sm:p-5 lg:p-10">

                @if($showCreateModal)
                {{-- HEADER: Back Button --}}
                <div class="flex items-center justify-between mb-6 sm:mb-8">
                    <button wire:click="$set('showCreateModal', false)" class="p-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 transition shadow-sm">
                        <x-heroicon-o-arrow-left class="h-5 w-5 text-gray-500 dark:text-gray-300" />
                    </button>
                    <div class="w-10"></div> {{-- Spacer --}}
                </div>

                {{-- CREATE FORM --}}
                <form wire:submit.prevent="submit" class="space-y-6 max-w-3xl mx-auto">

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
                        <x-input-error for="amount" class="mt-2" />
                    </div>

                    {{-- Purpose --}}
                    <div>
                        <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Purpose') }}</label>
                        <textarea wire:model.defer="purpose" rows="3" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" placeholder="{{ __('Purpose of Kasbon') }}"></textarea>
                        <x-input-error for="purpose" class="mt-2" />
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
                            <x-input-error for="payment_month" class="mt-2" />
                        </div>

                        {{-- Payment Year --}}
                        <div>
                            <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Payment Year') }}</label>
                            <input type="number" wire:model.defer="payment_year" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" />
                            <x-input-error for="payment_year" class="mt-2" />
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                        <p class="text-sm text-orange-800 dark:text-orange-300 flex items-start gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span><strong class="font-bold">PENTING:</strong> Jika disetujui, nominal ini akan otomatis dipotong pada Payroll bulan dan tahun yang Anda pilih di atas.</span>
                        </p>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-5 py-3 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 sm:flex-none px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-lg shadow-primary-500/30 transition transform active:scale-95 disabled:opacity-50">
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </form>

                @else
                {{-- LIST VIEW --}}

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    {{-- Unpaid --}}
                    <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="h-9 w-9 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                                <x-heroicon-m-clock class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <span class="text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider">{{ __('Belum Terbayar') }}</span>
                        </div>
                        <p class="text-xl font-black text-amber-800 dark:text-amber-200">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</p>
                        <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-1">{{ __('Pending + Approved') }}</p>
                    </div>

                    {{-- Paid --}}
                    <div class="rounded-2xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="h-9 w-9 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                                <x-heroicon-m-check-badge class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <span class="text-xs font-bold text-green-700 dark:text-green-300 uppercase tracking-wider">{{ __('Sudah Dibayar') }}</span>
                        </div>
                        <p class="text-xl font-black text-green-800 dark:text-green-200">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                        <p class="text-[10px] text-green-600 dark:text-green-400 mt-1">{{ __('Sudah masuk potongan gaji') }}</p>
                    </div>

                    {{-- Limit --}}
                    <div class="rounded-2xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="h-9 w-9 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                                <x-heroicon-m-shield-check class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider">{{ __('Limit Kasbon') }}</span>
                        </div>
                        <p class="text-xl font-black text-blue-800 dark:text-blue-200">Rp {{ number_format($basicSalary, 0, ',', '.') }}</p>
                        <p class="text-[10px] text-blue-600 dark:text-blue-400 mt-1">{{ __('Maks. per pengajuan = Gaji Pokok') }}</p>
                    </div>
                </div>

                {{-- List Header --}}
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Request History') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Your recent Kasbon requests') }}</p>
                    </div>
                    <button wire:click="openCreateModal" class="px-4 py-2.5 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-bold text-sm shadow-lg shadow-primary-500/30 flex items-center gap-2 transition transform active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden sm:inline">{{ __('Request Kasbon') }}</span>
                    </button>
                </div>

                @if($advances->isEmpty())
                <div class="p-12 text-center rounded-2xl bg-gray-50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-800 border-dashed">
                    <div class="w-16 h-16 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('No cash advance data found.') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs mx-auto">{{ __('No cash advance requests yet.') }}</p>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($advances as $advance)
                    <div class="group p-3 sm:p-4 rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-200 dark:hover:border-primary-800 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 sm:gap-4 overflow-hidden">
                                {{-- Icon --}}
                                <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110 bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-0.5">
                                        <h4 class="font-bold text-gray-900 dark:text-white capitalize truncate text-sm sm:text-base">{{ __('Deduction Target') }}: {{ \Carbon\Carbon::create()->month($advance->payment_month)->translatedFormat('F') }} {{ $advance->payment_year }}</h4>
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

                            <div class="text-right pl-2 sm:pl-4 shrink-0 flex flex-col items-end gap-1">
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

                <div class="mt-4">
                    {{ $advances->links() }}
                </div>
                @endif
                @endif

            </div>
        </div>
    </div>
</div>