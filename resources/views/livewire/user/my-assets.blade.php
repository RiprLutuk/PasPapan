<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-assets-title" class="user-page-surface relative">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('My Assets')"
                title-id="my-assets-title">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-50 via-white to-emerald-50 text-primary-700 ring-1 ring-inset ring-primary-100 shadow-sm dark:from-primary-900/30 dark:via-gray-800 dark:to-emerald-900/20 dark:text-primary-300 dark:ring-primary-800/60">
                        <x-heroicon-o-archive-box class="h-5 w-5" />
                    </div>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body bg-gray-50/50 dark:bg-gray-900/20">
                <div class="mb-6">
                    @include('components.feedback.alert-messages')
                </div>

                <div class="mb-6 flex justify-center">
                    <div class="inline-flex rounded-2xl border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <button
                            type="button"
                            wire:click="setAssetFilter('active')"
                            @class([
                                'wcag-touch-target inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                                'bg-primary-600 text-white shadow-sm' => $assetFilter === 'active',
                                'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $assetFilter !== 'active',
                            ])>
                            <span>{{ __('Active') }}</span>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-bold',
                                'bg-white/20 text-white' => $assetFilter === 'active',
                                'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $assetFilter !== 'active',
                            ])>{{ $assets->count() }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="setAssetFilter('returned')"
                            @class([
                                'wcag-touch-target inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                                'bg-primary-600 text-white shadow-sm' => $assetFilter === 'returned',
                                'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $assetFilter !== 'returned',
                            ])>
                            <span>{{ __('Returned') }}</span>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-bold',
                                'bg-white/20 text-white' => $assetFilter === 'returned',
                                'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $assetFilter !== 'returned',
                            ])>{{ $returnedHistories->count() }}</span>
                        </button>
                    </div>
                </div>

                @if($assetFilter === 'active' && $assets->isEmpty())
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-computer-desktop class="h-12 w-12 text-gray-300 dark:text-gray-500" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No assets assigned to you') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Contact your administrator if you believe this is an error.') }}</p>
                    </div>
                @elseif($assetFilter === 'active')
                    <div class="space-y-4">
                        @foreach($assets as $asset)
                            @php
                                $isReturnable = $asset->status === 'assigned';
                                $assetName = \Illuminate\Support\Str::lower($asset->name ?? '');
                                $assetTypeMeta = match ($asset->type) {
                                    'vehicle' => [
                                        'icon' => 'heroicon-o-truck',
                                        'classes' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/35 dark:text-sky-300',
                                    ],
                                    'furniture' => [
                                        'icon' => 'heroicon-o-building-office-2',
                                        'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/35 dark:text-amber-300',
                                    ],
                                    'uniform' => [
                                        'icon' => 'heroicon-o-shield-check',
                                        'classes' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/35 dark:text-violet-300',
                                    ],
                                    default => [
                                        'icon' => \Illuminate\Support\Str::contains($assetName, ['iphone', 'phone', 'mobile', 'tablet', 'tab', 'ipad'])
                                            ? 'heroicon-o-device-phone-mobile'
                                            : 'heroicon-o-computer-desktop',
                                        'classes' => 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
                                    ],
                                };
                                $statusClasses = match ($asset->status) {
                                    'assigned' => 'bg-emerald-100 text-emerald-900 ring-emerald-700/20 dark:bg-emerald-900/30 dark:text-emerald-300',
                                    'maintenance' => 'bg-amber-100 text-amber-900 ring-amber-700/20 dark:bg-amber-900/30 dark:text-amber-300',
                                    'available' => 'bg-sky-100 text-sky-900 ring-sky-700/20 dark:bg-sky-900/30 dark:text-sky-300',
                                    default => 'bg-gray-100 text-gray-800 ring-gray-700/10 dark:bg-gray-800 dark:text-gray-200',
                                };
                            @endphp

                            <article class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800/95">
                                <div class="border-b border-gray-100 px-5 py-5 dark:border-gray-700 sm:px-6">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $assetTypeMeta['classes'] }}">
                                                <x-dynamic-component :component="$assetTypeMeta['icon']" class="h-7 w-7" />
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('Assigned asset overview') }}</p>
                                                <h3 class="mt-1 text-lg font-bold leading-tight text-gray-950 dark:text-white">{{ $asset->name }}</h3>
                                                <p class="mt-1 break-all font-mono text-xs text-gray-600 dark:text-gray-300">
                                                    {{ $asset->serial_number ?: __('No serial number') }}
                                                </p>
                                            </div>
                                        </div>

                                        <span class="inline-flex max-w-fit items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] ring-1 ring-inset {{ $statusClasses }}">
                                            {{ $asset->displayStatus() }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-4 px-5 py-5 sm:px-6">
                                    @if($asset->return_date)
                                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 dark:border-sky-900/50 dark:bg-sky-950/30">
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">{{ __('Return Scheduled') }}</p>
                                                <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">{{ $asset->return_date->format('d M Y') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @php
                                        $expiryTone = $asset->expiration_date
                                            ? ($asset->isExpired()
                                                ? 'text-red-700 dark:text-red-300'
                                                : ($asset->isExpiringSoon()
                                                    ? 'text-amber-700 dark:text-amber-300'
                                                    : 'text-emerald-700 dark:text-emerald-300'))
                                            : 'text-gray-500 dark:text-gray-400';
                                        $expiryLabel = $asset->expiration_date
                                            ? ($asset->isExpired()
                                                ? __('Expired')
                                                : ($asset->isExpiringSoon() ? __('Expiring') : __('Valid till')))
                                            : null;
                                    @endphp

                                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/30">
                                        <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Asset Type') }}</dt>
                                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ filled($asset->type) ? __(ucfirst($asset->type)) : '—' }}
                                                </dd>
                                            </div>

                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Date Assigned') }}</dt>
                                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $asset->date_assigned?->format('d M Y') ?? '—' }}
                                                </dd>
                                            </div>

                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Purchase Date') }}</dt>
                                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $asset->purchase_date?->format('d M Y') ?? '—' }}
                                                </dd>
                                            </div>

                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Planned Return') }}</dt>
                                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $asset->return_date?->format('d M Y') ?? '—' }}
                                                </dd>
                                            </div>

                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Expiration Date') }}</dt>
                                                <dd class="flex flex-col gap-1 text-sm font-semibold text-gray-900 dark:text-white sm:flex-row sm:items-center sm:gap-3">
                                                    <span>{{ $asset->expiration_date?->format('d M Y') ?? '—' }}</span>
                                                    @if($expiryLabel)
                                                        <span class="text-xs font-bold uppercase tracking-[0.16em] {{ $expiryTone }}">{{ $expiryLabel }}</span>
                                                    @endif
                                                </dd>
                                            </div>

                                            @if($asset->notes)
                                                <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                    <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Asset Notes') }}</dt>
                                                    <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $asset->notes }}</dd>
                                                </div>
                                            @endif
                                        </dl>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50/80 px-5 py-4 dark:border-gray-700 dark:bg-gray-900/30 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Return request uses OTP verification and is available only for assigned assets.') }}</p>

                                    @if($isReturnable)
                                        <x-actions.button
                                            type="button"
                                            wire:click="openReturnModal('{{ $asset->id }}')"
                                            class="!rounded-2xl !px-4 !py-2.5 !text-sm !font-semibold">
                                            <x-heroicon-m-arrow-path class="h-4 w-4" />
                                            {{ __('Request Return') }}
                                        </x-actions.button>
                                    @else
                                        <button
                                            type="button"
                                            disabled
                                            class="wcag-touch-target inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-400 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                                            <x-heroicon-m-arrow-path class="h-4 w-4" />
                                            {{ __('Request Return') }}
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @elseif($returnedHistories->isEmpty())
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-arrow-uturn-left class="h-12 w-12 text-gray-300 dark:text-gray-500" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No returned asset history yet.') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Assets that you have already returned will appear here as history.') }}</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($returnedHistories as $history)
                            @php
                                $historyAssetName = \Illuminate\Support\Str::lower($history->asset?->name ?? '');
                                $historyTypeMeta = match ($history->asset?->type) {
                                    'vehicle' => [
                                        'icon' => 'heroicon-o-truck',
                                        'classes' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/35 dark:text-sky-300',
                                    ],
                                    'furniture' => [
                                        'icon' => 'heroicon-o-building-office-2',
                                        'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/35 dark:text-amber-300',
                                    ],
                                    'uniform' => [
                                        'icon' => 'heroicon-o-shield-check',
                                        'classes' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/35 dark:text-violet-300',
                                    ],
                                    default => [
                                        'icon' => \Illuminate\Support\Str::contains($historyAssetName, ['iphone', 'phone', 'mobile', 'tablet', 'tab', 'ipad'])
                                            ? 'heroicon-o-device-phone-mobile'
                                            : 'heroicon-o-computer-desktop',
                                        'classes' => 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
                                    ],
                                };
                            @endphp
                            <article class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/95">
                                <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 dark:border-gray-700 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $historyTypeMeta['classes'] }}">
                                            <x-dynamic-component :component="$historyTypeMeta['icon']" class="h-7 w-7" />
                                        </div>

                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('History') }}</p>
                                            <h3 class="mt-1 text-lg font-bold leading-tight text-gray-950 dark:text-white">
                                                {{ $history->asset?->name ?? __('Deleted asset record') }}
                                            </h3>
                                            <p class="mt-1 break-all font-mono text-xs text-gray-600 dark:text-gray-300">
                                                {{ $history->asset?->serial_number ?: __('No serial number') }}
                                            </p>
                                        </div>
                                    </div>

                                    <span class="inline-flex max-w-fit items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-sky-900 ring-1 ring-inset ring-sky-700/20 dark:bg-sky-900/30 dark:text-sky-300">
                                        {{ __('Returned') }}
                                    </span>
                                </div>

                                <div class="px-5 py-5 sm:px-6">
                                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/30">
                                        <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Asset Type') }}</dt>
                                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ filled($history->asset?->type) ? __(ucfirst($history->asset->type)) : '—' }}
                                                </dd>
                                            </div>

                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Returned On') }}</dt>
                                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $history->date?->format('d M Y H:i') ?? $history->created_at?->format('d M Y H:i') ?? '—' }}
                                                </dd>
                                            </div>

                                            <div class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,180px)_1fr] sm:items-start sm:gap-4">
                                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ __('Note') }}</dt>
                                                <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">
                                                    {{ $history->notes ?: '—' }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    <x-overlays.dialog-modal wire:model.live="showReturnModal">
        <x-slot name="title">
            {{ __('Confirm Asset Return') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                @if($selectedAssetName)
                    <div class="rounded-2xl border border-primary-100 bg-primary-50 px-4 py-3 dark:border-primary-900/40 dark:bg-primary-950/25">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-700 dark:text-primary-300">{{ __('Asset Name') }}</p>
                        <p class="mt-1 text-sm font-semibold text-primary-950 dark:text-white">{{ $selectedAssetName }}</p>
                    </div>
                @endif

                @if(!$otpRequested)
                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                        {{ __('To return this asset, an OTP code will be sent to your immediate supervisor or the administrator. You must acquire this 6-digit code from them to confirm the handover.') }}
                    </p>
                @else
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200">
                        <p class="font-semibold">{{ __('OTP expires in 15 minutes.') }}</p>
                        <p class="mt-1">{{ __('An OTP code has been sent. Please contact your manager or administrator, ask for the code, and enter it below to finalize the return.') }}</p>
                    </div>

                    <div>
                        <x-forms.label for="otpCode" value="{{ __('Enter 6-Digit OTP Code') }}" />
                        <input
                            id="otpCode"
                            type="text"
                            wire:model.live="otpCode"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            autocomplete="one-time-code"
                            class="mt-2 block w-full rounded-2xl border-gray-300 bg-white px-4 py-3 text-center font-mono text-xl tracking-[0.35em] text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-primary-400 dark:focus:ring-primary-400"
                            placeholder="------"
                            autofocus>
                        <x-forms.input-error for="otpCode" class="mt-2" />
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="closeReturnModal" wire:loading.attr="disabled">
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
