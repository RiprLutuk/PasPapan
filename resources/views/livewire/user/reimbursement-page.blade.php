<div class="user-page-shell">
    {{-- App Header Slot --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $isCreating ? __('New Claim') : __('Reimbursement') }}
        </h2>
    </x-slot>

    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="reimbursement-page-title" class="user-page-surface">
            <x-user.page-header
                :back-href="!$isCreating ? route('home') : null"
                :title="$isCreating ? __('New Claim') : __('Reimbursement')"
                title-id="reimbursement-page-title"
                class="border-b-0">
                <x-slot name="icon">
                    <span class="text-lg leading-none">💳</span>
                </x-slot>
                <x-slot name="actions">
                    @if($isCreating)
                        <button wire:click="cancel" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                            <span>{{ __('Back') }}</span>
                        </button>
                    @else
                        <button wire:click="create" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>{{ __('New Request') }}</span>
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">

                @if($isCreating)
                    {{-- CREATE FORM --}}
                    <div class="mx-auto max-w-3xl rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:p-8">
                    <form wire:submit.prevent="save" class="space-y-6">
                        
                        {{-- Date & Type --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Date --}}
                            <div>
                                <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Transaction Date') }}</label>
                                <input type="date" wire:model="date" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" />
                                <x-forms.input-error for="date" class="mt-2" />
                            </div>

                            {{-- Type --}}
                            <div>
                                <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Claim Type') }}</label>
                                <div wire:ignore>
                                    <x-user.tom-select-user id="type" wire:model="type" placeholder="{{ __('Select Type') }}" class="block w-full">
                                        <option value="" disabled>{{ __('Select Type') }}</option>
                                        <option value="medical">{{ __('Medical') }}</option>
                                        <option value="transport">{{ __('Transport') }}</option>
                                        <option value="project">{{ __('Project') }}</option>
                                        <option value="optical">{{ __('Optical') }}</option>
                                        <option value="dental">{{ __('Dental') }}</option>
                                        <option value="other">{{ __('Other') }}</option>
                                    </x-user.tom-select-user>
                                </div>
                                <x-forms.input-error for="type" class="mt-2" />
                            </div>
                        </div>

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
                                    wire:model="amount" 
                                    placeholder="0" 
                                />
                            </div>
                            <x-forms.input-error for="amount" class="mt-2" />
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                            <textarea wire:model="description" rows="3" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all py-3 px-4" placeholder="{{ __('Explain details...') }}"></textarea>
                            <x-forms.input-error for="description" class="mt-2" />
                        </div>

                        {{-- Attachment --}}
                        <div class="p-4 sm:p-5 bg-gray-50 dark:bg-gray-900/30 rounded-2xl border border-gray-200 dark:border-gray-700 border-dashed">
                             <label class="mb-3 font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                {{ __('Attachment (Recall/Bill)') }}
                            </label>
                            
                            <div class="mt-2 flex justify-center px-6 pt-5 pb-6">
                                <div class="space-y-1 text-center">
                                    @if($attachment)
                                        <div class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400 font-bold bg-green-50 dark:bg-green-900/20 py-2 px-4 rounded-full inline-block break-all max-w-full">
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <span class="truncate">{{ $attachment->getClientOriginalName() }}</span>
                                        </div>
                                    @else
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                            <label for="file-upload" class="relative cursor-pointer rounded-md font-bold text-primary-600 hover:text-primary-500 focus-within:outline-none">
                                                <span>{{ __('Upload a file') }}</span>
                                                <input id="file-upload" wire:model="attachment" type="file" class="sr-only">
                                            </label>
                                            <p class="pl-1 hidden sm:inline">{{ __('or drag and drop') }}</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">PNG, JPG, PDF up to 10MB</p>
                                    @endif
                                </div>
                            </div>
                            <x-forms.input-error for="attachment" class="mt-2" />
                        </div>

                        <div class="flex flex-col-reverse items-stretch justify-end gap-3 pt-4 sm:flex-row">
                            <button type="button" wire:click="cancel" class="px-5 py-3 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" class="flex-1 sm:flex-none px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-lg shadow-primary-500/30 transition transform active:scale-95">
                                {{ __('Submit Claim') }}
                            </button>
                        </div>
                    </form>
                    </div>

                @else
                    {{-- LIST VIEW --}}
                    
                    <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="user-filter-grid">
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search') }}</label>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500"
                                placeholder="{{ __('Search reimbursement history') }}">
                        </div>

                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</label>
                            <select wire:model.live="statusFilter" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500">
                                <option value="all">{{ __('All Statuses') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="approved">{{ __('Approved') }}</option>
                                <option value="rejected">{{ __('Rejected') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Type') }}</label>
                            <select wire:model.live="typeFilter" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500">
                                <option value="all">{{ __('All Types') }}</option>
                                <option value="medical">{{ __('Medical') }}</option>
                                <option value="transport">{{ __('Transport') }}</option>
                                <option value="project">{{ __('Project') }}</option>
                                <option value="optical">{{ __('Optical') }}</option>
                                <option value="dental">{{ __('Dental') }}</option>
                                <option value="other">{{ __('Other') }}</option>
                            </select>
                        </div>
                    </div>
                    </div>

                    @if($claims->isEmpty())
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No Claims Found') }}</h3>
                            <p class="user-empty-state__copy">{{ __('You haven\'t submitted any reimbursement claims yet.') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                             @foreach($claims as $claim)
                                <div class="group rounded-2xl border border-gray-100 bg-white p-3 transition-all duration-200 hover:border-primary-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-800 sm:p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-3 overflow-hidden sm:gap-4">
                                            {{-- Icon --}}
                                            <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110
                                                @if($claim->type == 'medical') bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400
                                                @elseif($claim->type == 'transport') bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400
                                                @else bg-gray-50 text-gray-600 dark:bg-gray-700/50 dark:text-gray-400 @endif">
                                                
                                                @if($claim->type == 'medical') 
                                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                                @elseif($claim->type == 'transport') 
                                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                                @else 
                                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                @endif
                                            </div>
                                            
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-0.5">
                                                    <h4 class="font-bold text-gray-900 dark:text-white capitalize truncate text-sm sm:text-base">{{ ucfirst($claim->type) }}</h4>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wide
                                                        @if($claim->status === 'approved') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                                        @elseif($claim->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                                        @else bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 @endif">
                                                        {{ ucfirst($claim->status) }}
                                                    </span>
                                                </div>
                                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 line-clamp-1 break-all">{{ $claim->description }}</p>
                                                <div class="text-[10px] text-gray-400 mt-0.5 sm:mt-1 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    {{ $claim->date->format('d M Y') }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="shrink-0 pl-0 text-left sm:pl-4 sm:text-right">
                                            <p class="text-sm sm:text-lg font-black text-gray-900 dark:text-white tracking-tight">
                                                <span class="text-[10px] sm:text-xs text-gray-400 font-normal mr-0.5">Rp</span>{{ number_format($claim->amount, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                             @endforeach
                        </div>

                        {{-- Load More / Archive Button --}}
                        @if($totalClaims > $limit)
                            <div class="mt-6 text-center">
                                <button wire:click="loadMore" class="px-6 py-2 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-600 transition shadow-sm">
                                    {{ __('View Older History') }} ({{ $totalClaims - $limit }} {{ __('more') }})
                                </button>
                            </div>
                        @endif
                    @endif
                @endif

            </div>
        </section>
    </div>
</div>
