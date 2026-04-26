<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-shell__backdrop" aria-hidden="true"></div>

        <div class="auth-shell__container">
            <section class="auth-card lg:col-span-2" aria-labelledby="login-form-title">
                <div class="auth-card__header">
                    <p class="auth-card__eyebrow">{{ __('Sign in') }}</p>
                    <h2 id="login-form-title" class="auth-card__title">{{ __('Welcome Back!') }}</h2>
                </div>

                <div class="auth-form">
                    @if (session('status'))
                        <div class="auth-status" role="status" aria-live="polite">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div class="auth-section">

                            <div class="auth-grid auth-grid--single">
                                <div class="auth-field">
                                    <label for="email" class="auth-label">{{ __('Email or Phone') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                            </svg>
                                        </div>
                                        <input id="email" name="email" type="text" autocomplete="username"
                                            required autofocus aria-describedby="@error('email') email-error @enderror"
                                            aria-invalid="@error('email') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('email') auth-input--error @enderror"
                                            value="{{ old('email') }}" placeholder="{{ __('Enter your ID') }}">
                                    </div>
                                    @error('email')
                                        <p id="email-error" class="auth-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="auth-field">
                                    <label for="password" class="auth-label">{{ __('Password') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-4 4h8a2 2 0 002-2v-5a2 2 0 00-2-2H8a2 2 0 00-2 2v5a2 2 0 002 2zm8-9V9a4 4 0 10-8 0v2h8z" />
                                            </svg>
                                        </div>
                                        <input id="password" name="password" type="password"
                                            autocomplete="current-password" required
                                            aria-describedby="@error('password') password-error @enderror"
                                            aria-invalid="@error('password') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('password') auth-input--error @enderror"
                                            placeholder="{{ __('********') }}">
                                    </div>
                                    @error('password')
                                        <p id="password-error" class="auth-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="auth-check-row">
                            <label for="remember_me" class="auth-check">
                                <input id="remember_me" name="remember" type="checkbox" class="auth-check__box"
                                    @checked(old('remember'))>
                                <span class="auth-check__label">{{ __('Remember me') }}</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="auth-link">
                                    {{ __('Forgot password?') }}
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="auth-button auth-button--full">
                            {{ __('Log in') }}
                        </button>
                    </form>

                    <div class="auth-footer">
                        {{ __("Don't have an account?") }}
                        <a href="{{ route('register') }}" class="auth-link">{{ __('Register') }}</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>
