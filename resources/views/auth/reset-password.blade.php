<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-shell__backdrop" aria-hidden="true"></div>

        <div class="auth-shell__container">
            <section class="auth-card lg:col-span-2" aria-labelledby="reset-password-title">
                <div class="auth-card__header">
                    <p class="auth-card__eyebrow">{{ __('Password Reset') }}</p>
                    <h2 id="reset-password-title" class="auth-card__title">{{ __('Create a new password') }}</h2>
                    <p class="auth-card__copy">
                        {{ __('Set a new password for your account, then use it the next time you sign in.') }}
                    </p>
                </div>

                <div class="auth-form">
                    <x-forms.validation-errors />

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="auth-section">
                            <div class="auth-section__header">
                                <h3 class="auth-section__title">{{ __('Account verification') }}</h3>
                                <p class="auth-section__copy">
                                    {{ __('Confirm the account email and choose a strong password to complete the reset process.') }}
                                </p>
                            </div>

                            <div class="auth-grid">
                                <div class="auth-field auth-field--full">
                                    <label for="email" class="auth-label">{{ __('Email Address') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                            </svg>
                                        </div>
                                        <input id="email" name="email" type="email" required autofocus autocomplete="username"
                                            aria-describedby="@error('email') email-error @enderror"
                                            aria-invalid="@error('email') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('email') auth-input--error @enderror"
                                            value="{{ old('email', $request->email) }}" placeholder="{{ __('email@example.com') }}">
                                    </div>
                                    @error('email')
                                        <p id="email-error" class="auth-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="auth-field">
                                    <label for="password" class="auth-label">{{ __('New Password') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-4 4h8a2 2 0 002-2v-5a2 2 0 00-2-2H8a2 2 0 00-2 2v5a2 2 0 002 2zm8-9V9a4 4 0 10-8 0v2h8z" />
                                            </svg>
                                        </div>
                                        <input id="password" name="password" type="password" required autocomplete="new-password"
                                            aria-describedby="@error('password') password-error @enderror"
                                            aria-invalid="@error('password') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('password') auth-input--error @enderror"
                                            placeholder="{{ __('********') }}">
                                    </div>
                                    @error('password')
                                        <p id="password-error" class="auth-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="auth-field">
                                    <label for="password_confirmation" class="auth-label">{{ __('Confirm Password') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                            aria-describedby="@error('password_confirmation') password-confirmation-error @enderror"
                                            aria-invalid="@error('password_confirmation') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('password_confirmation') auth-input--error @enderror"
                                            placeholder="{{ __('********') }}">
                                    </div>
                                    @error('password_confirmation')
                                        <p id="password-confirmation-error" class="auth-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="auth-actions auth-actions--split">
                            <a href="{{ route('login') }}" class="auth-link">
                                {{ __('Back to Login') }}
                            </a>

                            <button type="submit" class="auth-button auth-button--full sm:w-auto">
                                {{ __('Reset Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>
