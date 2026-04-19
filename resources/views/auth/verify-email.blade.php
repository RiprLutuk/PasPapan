<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-shell__backdrop" aria-hidden="true"></div>

        <div class="auth-shell__container">
            <section class="auth-card lg:col-span-2" aria-labelledby="verify-email-title">
                <div class="auth-card__header">
                    <p class="auth-card__eyebrow">{{ __('Verify Email') }}</p>
                    <h2 id="verify-email-title" class="auth-card__title">{{ __('Check your inbox') }}</h2>
                    <p class="auth-card__copy">
                        {{ __('Enter the verification code we sent to your email address. If you did not receive it, you can request a new code.') }}
                    </p>
                </div>

                <div class="auth-form">
                    @if (session('status') == 'verification-link-sent')
                        <div class="auth-status" role="status" aria-live="polite">
                            {{ __('A new verification code has been sent to your email address.') }}
                        </div>
                    @endif

                    <div class="auth-section">
                        <div class="auth-section__header">
                            <h3 class="auth-section__title">{{ __('Verification code') }}</h3>
                            <p class="auth-section__copy">
                                {{ __('Type the 6 digit code from your email to activate your account.') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('verification.code.verify') }}" class="space-y-4" novalidate>
                            @csrf

                            <div class="auth-field">
                                <label for="code" class="auth-label">{{ __('Verification Code') }}</label>
                                <input id="code" name="code" type="text" value="{{ old('code') }}" required autofocus
                                    inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                                    aria-describedby="@error('code') code-error @enderror"
                                    aria-invalid="@error('code') true @else false @enderror"
                                    class="auth-input @error('code') auth-input--error @enderror"
                                    placeholder="{{ __('6 digit code') }}">
                                @error('code')
                                    <p id="code-error" class="auth-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="auth-button auth-button--full">
                                {{ __('Verify and Continue') }}
                            </button>
                        </form>

                        <div class="auth-actions">
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="auth-button auth-button--secondary auth-button--full sm:w-auto">
                                    {{ __('Resend Verification Code') }}
                                </button>
                            </form>

                            <a href="{{ route('profile.show') }}" class="auth-link">
                                {{ __('Edit Profile') }}
                            </a>
                        </div>
                    </div>

                    <div class="auth-footer">
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="auth-link">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>
