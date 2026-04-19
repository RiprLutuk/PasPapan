<x-sections.action-section class="profile-card">
    <x-slot name="icon">
        <x-heroicon-o-lock-closed class="h-6 w-6" />
    </x-slot>

    <x-slot name="title">
        {{ __('Two Factor Authentication') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Add additional security to your account using two factor authentication.') }}
    </x-slot>

    <x-slot name="content">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            @if ($this->enabled)
                @if ($showingConfirmation)
                    {{ __('Finish enabling two factor authentication.') }}
                @else
                    {{ __('You have enabled two factor authentication.') }}
                @endif
            @else
                {{ __('You have not enabled two factor authentication.') }}
            @endif
        </h3>

        <div class="mt-3 max-w-xl text-sm text-gray-600 dark:text-gray-400">
            <p>
                {{ __('When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.') }}
            </p>
        </div>

        @if ($this->enabled)
            @if ($showingQrCode)
                <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-semibold">
                        @if ($showingConfirmation)
                            {{ __('To finish enabling two factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key and provide the generated OTP code.') }}
                        @else
                            {{ __('Two factor authentication is now enabled. Scan the following QR code using your phone\'s authenticator application or enter the setup key.') }}
                        @endif
                    </p>
                </div>

                <div class="mt-4 inline-block max-w-full overflow-x-auto rounded-xl bg-white p-2">
                    {!! $this->user->twoFactorQrCodeSvg() !!}
                </div>

                <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-semibold">
                        {{ __('Setup Key') }}: {{ decrypt($this->user->two_factor_secret) }}
                    </p>
                </div>

                @if ($showingConfirmation)
                    <div class="mt-4">
                        <x-forms.label for="code" value="{{ __('Code') }}" />

                        <x-forms.input id="code" type="text" name="code" class="mt-1 block w-full sm:w-1/2" inputmode="numeric" autofocus autocomplete="one-time-code"
                            wire:model="code"
                            wire:keydown.enter="confirmTwoFactorAuthentication" />

                        <x-forms.input-error for="code" class="mt-2" />
                    </div>
                @endif
            @endif

            @if ($showingRecoveryCodes)
                <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-semibold">
                        {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}
                    </p>
                </div>

                <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 dark:bg-gray-900 dark:text-gray-100 rounded-lg">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        @endif

    </x-slot>

    <x-slot name="actions">
        @if (! $this->enabled)
            <x-overlays.confirms-password wire:then="enableTwoFactorAuthentication">
                <x-actions.button type="button" wire:loading.attr="disabled">
                    {{ __('Enable') }}
                </x-actions.button>
            </x-overlays.confirms-password>
        @else
            @if ($showingRecoveryCodes)
                <x-overlays.confirms-password wire:then="regenerateRecoveryCodes">
                    <x-actions.secondary-button class="me-3">
                        {{ __('Regenerate Recovery Codes') }}
                    </x-actions.secondary-button>
                </x-overlays.confirms-password>
            @elseif ($showingConfirmation)
                <x-overlays.confirms-password wire:then="confirmTwoFactorAuthentication">
                    <x-actions.button type="button" class="me-3" wire:loading.attr="disabled">
                        {{ __('Confirm') }}
                    </x-actions.button>
                </x-overlays.confirms-password>
            @else
                <x-overlays.confirms-password wire:then="showRecoveryCodes">
                    <x-actions.secondary-button class="me-3">
                        {{ __('Show Recovery Codes') }}
                    </x-actions.secondary-button>
                </x-overlays.confirms-password>
            @endif

            @if ($showingConfirmation)
                <x-overlays.confirms-password wire:then="disableTwoFactorAuthentication">
                    <x-actions.secondary-button wire:loading.attr="disabled">
                        {{ __('Cancel') }}
                    </x-actions.secondary-button>
                </x-overlays.confirms-password>
            @else
                <x-overlays.confirms-password wire:then="disableTwoFactorAuthentication">
                    <x-actions.danger-button wire:loading.attr="disabled">
                        {{ __('Disable') }}
                    </x-actions.danger-button>
                </x-overlays.confirms-password>
            @endif

        @endif
    </x-slot>
</x-sections.action-section>
