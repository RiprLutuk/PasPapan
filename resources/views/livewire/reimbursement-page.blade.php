<div class="py-6 lg:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden relative">
            
            {{-- Header --}}
            <div class="px-5 py-4 lg:px-8 lg:py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 relative z-10">
                <div class="flex items-center gap-3">
                    @if($isCreating)
                         <button wire:click="cancel" class="p-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                            <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                        </button>
                    @else
                        <x-secondary-button href="{{ route('home') }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                            <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                        </x-secondary-button>
                    @endif
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        {{ $isCreating ? __('New Claim') : __('Reimbursement') }}
                    </h3>
                </div>
                
                @if(!$isCreating)
                    <button wire:click="create" class="px-4 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-bold text-xs uppercase tracking-widest transition shadow-lg shadow-primary-500/30 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span class="hidden sm:inline">{{ __('New Request') }}</span>
                    </button>
                @endif
            </div>

            <div class="p-0">
                @if($isCreating)
                    {{-- Create Form --}}
                    <div class="p-6 lg:p-8">
                        <form wire:submit.prevent="save" class="space-y-6">
                            
                            {{-- Date & Type --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Date --}}
                                <div>
                                    <x-label for="date" value="{{ __('Transaction Date') }}" />
                                    <x-input id="date" type="date" class="mt-1 block w-full" wire:model="date" />
                                    <x-input-error for="date" class="mt-2" />
                                </div>

                                {{-- Type --}}
                                <div>
                                    <x-label for="type" value="{{ __('Claim Type') }}" />
                                    <x-tom-select id="type" wire:model="type" placeholder="{{ __('Select Claim Type') }}">
                                        <option value="medical">{{ __('Medical') }}</option>
                                        <option value="transport">{{ __('Transport') }}</option>
                                        <option value="optical">{{ __('Optical') }}</option>
                                        <option value="dental">{{ __('Dental') }}</option>
                                        <option value="other">{{ __('Other') }}</option>
                                    </x-tom-select>
                                    <x-input-error for="type" class="mt-2" />
                                </div>
                            </div>

                            {{-- Amount --}}
                            <div>
                                <x-label for="amount" value="{{ __('Amount') }}" />
                                <div class="relative mt-1 rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <x-input id="amount" type="number" step="0.01" class="block w-full pl-10" wire:model="amount" placeholder="0.00" />
                                </div>
                                <x-input-error for="amount" class="mt-2" />
                            </div>

                            {{-- Description --}}
                            <div>
                                <x-label for="description" value="{{ __('Description') }}" />
                                <textarea id="description" wire:model="description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm"></textarea>
                                <x-input-error for="description" class="mt-2" />
                            </div>

                            {{-- Attachment --}}
                            <div>
                                <x-label for="attachment" value="{{ __('Attachment (Recall/Bill)') }}" />
                                <div class="mt-1 flex justify-center rounded-md border-2 border-dashed border-gray-300 dark:border-gray-700 px-6 pt-5 pb-6">
                                    <div class="space-y-1 text-center">
                                        @if($attachment)
                                            <p class="text-sm text-green-600 dark:text-green-400 font-medium">
                                                {{ $attachment->getClientOriginalName() }}
                                            </p>
                                        @else
                                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                                <label for="file-upload" class="relative cursor-pointer rounded-md bg-white dark:bg-gray-800 font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                                    <span>{{ __('Upload a file') }}</span>
                                                    <input id="file-upload" wire:model="attachment" type="file" class="sr-only">
                                                </label>
                                                <p class="pl-1">{{ __('or drag and drop') }}</p>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, PDF up to 10MB</p>
                                        @endif
                                    </div>
                                </div>
                                <x-input-error for="attachment" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <x-secondary-button wire:click="cancel" wire:loading.attr="disabled">
                                    {{ __('Cancel') }}
                                </x-secondary-button>

                                <x-button wire:loading.attr="disabled">
                                    {{ __('Submit Claim') }}
                                </x-button>
                            </div>
                        </form>
                    </div>

                @else
                    {{-- History List --}}
                    @if($claims->isEmpty())
                        <div class="p-8 text-center flex flex-col items-center justify-center min-h-[400px]">
                            <div class="w-24 h-24 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('No Claims Found') }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-sm mb-6">{{ __('You haven\'t submitted any reimbursement claims yet. Click the button above to start specific claim.') }}</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                             @foreach($claims as $claim)
                                <div class="p-4 sm:p-6 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 rounded-xl flex items-center justify-center
                                            @if($claim->type == 'medical') bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400
                                            @elseif($claim->type == 'transport') bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400
                                            @else bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 @endif">
                                            
                                            @if($claim->type == 'medical') 🩺
                                            @elseif($claim->type == 'transport') 🚕
                                            @elseif($claim->type == 'optical') 👓
                                            @elseif($claim->type == 'dental') 🦷
                                            @else 📄 @endif
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white capitalize">{{ $claim->type }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5 line-clamp-1 break-all">{{ $claim->description }}</p>
                                             <span class="text-[10px] text-gray-400">{{ $claim->date->format('d M Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">Rp {{ number_format($claim->amount, 0, ',', '.') }}</p>
                                         <span class="inline-flex items-center px-2 py-0.5 rounded textxs font-medium
                                            @if($claim->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400
                                            @elseif($claim->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400
                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400 @endif">
                                            {{ ucfirst($claim->status) }}
                                        </span>
                                    </div>
                                </div>
                             @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
