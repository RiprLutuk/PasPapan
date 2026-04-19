<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-shell__backdrop" aria-hidden="true"></div>

        <div class="auth-shell__container">
            <section class="auth-card lg:col-span-2" aria-labelledby="forgot-password-title">
                <div class="auth-card__header">
                    <p class="auth-card__eyebrow">{{ __('Password Recovery') }}</p>
                    <h2 id="forgot-password-title" class="auth-card__title">{{ __('Forgot your password?') }}</h2>
                    <p class="auth-card__copy">
                        {{ __('Enter the email address tied to your account and we will send you a secure link to set a new password.') }}
                    </p>
                </div>

                <div class="auth-form">
                    @if (session('status'))
                        <div class="auth-status" role="status" aria-live="polite">
                            {{ session('status') }}
                        </div>
                    @endif

                    <x-forms.validation-errors />

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                        @csrf

                        <div class="auth-section">
                            <div class="auth-section__header">
                                <h3 class="auth-section__title">{{ __('Recovery details') }}</h3>
                                <p class="auth-section__copy">
                                    {{ __('Use the same email you use to sign in so the reset email reaches the correct account.') }}
                                </p>
                            </div>

                            <div class="auth-grid auth-grid--single">
                                <div class="auth-field">
                                    <label for="email" class="auth-label">{{ __('Email Address') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                            </svg>
                                        </div>
                                        <input id="email" name="email" type="email" autocomplete="email" required autofocus
                                            aria-describedby="@error('email') email-error @enderror"
                                            aria-invalid="@error('email') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('email') auth-input--error @enderror"
                                            value="{{ old('email') }}" placeholder="{{ __('email@example.com') }}">
                                    </div>
                                    @error('email')
                                        <p id="email-error" class="auth-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="auth-actions auth-actions--split">
                            <a href="{{ route('login') }}" class="auth-link">
                                {{ __('Back to Login') }}
                            </a>

                            <button type="submit" class="auth-button auth-button--full sm:w-auto">
                                {{ __('Send Password Reset Link') }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>
