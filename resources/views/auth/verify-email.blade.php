<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-shell__backdrop" aria-hidden="true"></div>

        <div class="auth-shell__container">
            <section class="auth-card lg:col-span-2" aria-labelledby="verify-email-title">
                <div class="auth-card__header">
                    <p class="auth-card__eyebrow">{{ __('Verify Email') }}</p>
                    <h2 id="verify-email-title" class="auth-card__title">{{ __('Check your inbox') }}</h2>
                    <p class="auth-card__copy">
                        {{ __('Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                    </p>
                </div>

                <div class="auth-form">
                    @if (session('status') == 'verification-link-sent')
                        <div class="auth-status" role="status" aria-live="polite">
                            {{ __('A new verification link has been sent to the email address you provided in your profile settings.') }}
                        </div>
                    @endif

                    <div class="auth-section">
                        <div class="auth-section__header">
                            <h3 class="auth-section__title">{{ __('Next step') }}</h3>
                            <p class="auth-section__copy">
                                {{ __('Use the verification email to activate your account before returning to the app.') }}
                            </p>
                        </div>

                        <div class="auth-actions">
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="auth-button auth-button--full sm:w-auto">
                                    {{ __('Resend Verification Email') }}
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
