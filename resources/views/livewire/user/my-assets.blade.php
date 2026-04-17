<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-assets-title" class="user-page-surface relative">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('My Assets')"
                title-id="my-assets-title">
                <x-slot name="icon">
                    <span class="text-lg leading-none">💻</span>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body bg-gray-50/50 dark:bg-gray-900/20">
                @if($assets->isEmpty())
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-computer-desktop class="w-12 h-12 text-gray-300 dark:text-gray-500" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No assets assigned to you') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Contact your administrator if you believe this is an error.') }}</p>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($assets as $asset)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 hover:shadow-md transition-shadow">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $asset->name }}</h3>
                                @if($asset->serial_number)
                                    <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $asset->serial_number }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest
                                {{ $asset->status === 'assigned' ? 'bg-blue-600 text-white shadow-sm' : '' }}
                                {{ $asset->status === 'maintenance' ? 'bg-amber-500 text-white shadow-sm' : '' }}
                                {{ $asset->status === 'available' ? 'bg-emerald-500 text-white shadow-sm' : '' }}">
                                {{ __(ucfirst($asset->status)) }}
                            </span>
                        </div>

                        <!-- Details -->
                        <div class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-tag class="w-4 h-4 text-gray-400" />
                                <span>{{ __(ucfirst($asset->type)) }}</span>
                            </div>
                            @if($asset->date_assigned)
                                <div class="flex items-center gap-2">
                                    <x-heroicon-m-calendar class="w-4 h-4 text-gray-400" />
                                    <span>{{ __('Assigned') }}: {{ \Carbon\Carbon::parse($asset->date_assigned)->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if($asset->return_date)
                                <div class="flex items-center gap-2">
                                    <x-heroicon-m-arrow-uturn-left class="w-4 h-4 text-gray-400" />
                                    <span>{{ __('Return') }}: {{ \Carbon\Carbon::parse($asset->return_date)->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if($asset->notes)
                                <div class="flex items-start gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <x-heroicon-m-document-text class="w-4 h-4 text-gray-400 mt-0.5" />
                                    <span class="text-xs">{{ $asset->notes }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                            <x-actions.button type="button" wire:click="openReturnModal('{{ $asset->id }}')" class="!py-1.5 !px-3 !text-xs !bg-indigo-50 !text-indigo-700 hover:!bg-indigo-100 dark:!bg-indigo-900/30 dark:!text-indigo-400 dark:hover:!bg-indigo-900/50 shadow-none border-transparent uppercase font-bold tracking-widest transition">
                                <x-heroicon-m-arrow-path class="w-3.5 h-3.5 mr-1.5" />
                                {{ __('Request Return') }}
                            </x-actions.button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        </section>
    </div>
</div>

    <!-- OTP Return Modal -->
    <x-overlays.dialog-modal wire:model.live="showReturnModal">
        <x-slot name="title">
            {{ __('Confirm Asset Return') }}
        </x-slot>

        <x-slot name="content">
            @if(!$otpRequested)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('To return this asset, an OTP code will be sent to your immediate supervisor or the administrator. You must acquire this 6-digit code from them to confirm the handover.') }}
                </p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('An OTP code has been sent. Please contact your manager or administrator, ask for the code, and enter it below to finalize the return.') }}
                </p>
                <div class="mt-4 flex flex-col justify-center items-center gap-2">
                    <x-forms.label for="otpCode" value="{{ __('Enter 6-Digit OTP Code') }}" />
                    <input type="text" wire:model="otpCode" maxlength="6" class="text-center tracking-[0.5em] text-xl font-mono border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm w-48 py-3" placeholder="------" autofocus>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showReturnModal', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            @if(!$otpRequested)
                <x-actions.button class="ms-3" wire:click="requestOtp" wire:loading.attr="disabled">
                    {{ __('Request OTP') }}
                </x-actions.button>
            @else
                <x-actions.button class="ms-3" wire:click="verifyOtp" wire:loading.attr="disabled">
                    {{ __('Confirm Return') }}
                </x-actions.button>
            @endif
        </x-slot>
    </x-overlays.dialog-modal>
</div>
