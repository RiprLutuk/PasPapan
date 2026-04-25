<x-app-layout>
    @php($backRoute = auth()->user()->preferredHomeUrl())
    @php($profilePanels = [])
    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
        @php($profilePanels['details'] = ['title' => __('Details'), 'copy' => __('Update your personal profile information')])
    @endif
    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
        @php($profilePanels['password'] = ['title' => __('Password'), 'copy' => __('Change and strengthen your password')])
    @endif
    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
        @php($profilePanels['security'] = ['title' => __('Security'), 'copy' => __('Manage verification and account protection')])
    @endif
    @php($profilePanels['sessions'] = ['title' => __('Sessions'), 'copy' => __('Review and sign out active devices')])
    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
        @php($profilePanels['danger'] = ['title' => __('Danger'), 'copy' => __('Request account deletion with admin approval')])
    @endif

    <div class="profile-page user-page-shell" x-data="{
        panels: @js($profilePanels),
        activePanel: null,
        openPanel(panel) {
            if (!this.panels[panel]) {
                return;
            }

            this.activePanel = panel;
        },
        closePanel() {
            this.activePanel = null;
        },
    }">
        <div class="user-page-container user-page-container--standard">
            <div aria-labelledby="profile-page-title">
                <x-user.page-header :back-href="$backRoute" :title="__('Profile')" :description="auth()->user()->email" title-id="profile-page-title"
                    plain>
                    <x-slot name="icon">
                        <x-heroicon-o-user-circle class="h-6 w-6" />
                    </x-slot>

                    <x-slot name="meta">
                        @if (auth()->user()->hasVerifiedEmail())
                            <span class="profile-verified-mark" aria-label="{{ __('Verified account') }}" title="{{ __('Verified') }}">
                                <x-heroicon-s-check-badge class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                            </span>
                        @else
                            <span class="profile-verified-mark profile-verified-mark--warning" aria-label="{{ __('Unverified account') }}" title="{{ __('Unverified') }}">
                                <x-heroicon-s-exclamation-circle class="h-4 w-4 text-amber-600 dark:text-amber-300" />
                            </span>
                        @endif
                    </x-slot>
                </x-user.page-header>

                <section class="profile-control-grid" aria-label="{{ __('Profile controls') }}">
                    <div class="profile-preferences__item">
                        <div>
                            <h2 class="profile-preferences__title">{{ __('Language') }}</h2>
                            <p class="profile-preferences__copy">{{ __('Switch between Indonesian and English') }}</p>
                        </div>

                        <form method="POST" action="{{ route('user.language.update') }}">
                            @csrf
                            <input type="hidden" name="language" value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
                            <button type="submit" class="language-toggle language-toggle--compact"
                                aria-label="{{ __('Switch language to :language', ['language' => app()->getLocale() == 'id' ? 'English' : 'Bahasa Indonesia']) }}">
                                <span class="sr-only">{{ __('Switch Language') }}</span>
                                <span class="language-toggle__labels">
                                    <span class="language-toggle__label">ID</span>
                                    <span class="language-toggle__label">EN</span>
                                </span>
                                <span class="language-toggle__thumb {{ app()->getLocale() == 'en' ? 'language-toggle__thumb--end' : 'translate-x-0' }}">
                                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100">
                                        <span class="leading-none">
                                            {{ app()->getLocale() == 'id' ? '🇮🇩' : '🇺🇸' }}
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </form>
                    </div>

                    <div class="profile-preferences__item">
                        <div>
                            <h2 class="profile-preferences__title">{{ __('Appearance') }}</h2>
                            <p class="profile-preferences__copy">{{ __('Toggle light or dark mode') }}</p>
                        </div>

                        <x-navigation.theme-toggle id="theme-switcher-profile" class="shrink-0" />
                    </div>

                    @foreach ($profilePanels as $panelKey => $panel)
                        <button type="button" class="profile-section-nav__link"
                            x-on:click="openPanel(@js($panelKey))"
                            x-bind:aria-haspopup="'dialog'"
                            x-bind:aria-expanded="(activePanel === @js($panelKey)).toString()">
                            <span class="profile-section-nav__title">{{ $panel['title'] }}</span>
                            <span class="profile-section-nav__copy">{{ $panel['copy'] }}</span>
                        </button>
                    @endforeach
                </section>

                <div x-cloak x-show="activePanel" x-trap.inert.noscroll="activePanel !== null"
                    x-on:keydown.escape.window="closePanel()" class="profile-modal">
                    <div class="profile-modal__backdrop" x-on:click="closePanel()"></div>

                    <div x-show="activePanel" class="profile-modal__panel" role="dialog" aria-modal="true"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <div class="profile-modal__header">
                            <div>
                                <h2 class="profile-modal__title" x-text="activePanel ? panels[activePanel].title : ''"></h2>
                                <p class="profile-modal__copy" x-text="activePanel ? panels[activePanel].copy : ''"></p>
                            </div>

                            <button type="button" class="profile-modal__close" x-on:click="closePanel()"
                                aria-label="{{ __('Close panel') }}">
                                <x-heroicon-o-x-mark class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="profile-modal__body">
                            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                                <section x-cloak x-show="activePanel === 'details'" x-transition.opacity.duration.150ms>
                                    <h3 class="sr-only">{{ __('Profile Information') }}</h3>
                                    @livewire('profile.update-profile-information-form')
                                </section>
                            @endif

                            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                                <section x-cloak x-show="activePanel === 'password'" x-transition.opacity.duration.150ms>
                                    <h3 class="sr-only">{{ __('Update Password') }}</h3>
                                    @livewire('profile.update-password-form')
                                </section>
                            @endif

                            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                                <section x-cloak x-show="activePanel === 'security'" x-transition.opacity.duration.150ms>
                                    <h3 class="sr-only">{{ __('Two Factor Authentication') }}</h3>
                                    @livewire('profile.two-factor-authentication-form')
                                </section>
                            @endif

                            <section x-cloak x-show="activePanel === 'sessions'" x-transition.opacity.duration.150ms>
                                <h3 class="sr-only">{{ __('Browser Sessions') }}</h3>
                                @livewire('profile.logout-other-browser-sessions-form')
                            </section>

                            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                                <section x-cloak x-show="activePanel === 'danger'" x-transition.opacity.duration.150ms>
                                    <h3 class="sr-only">{{ __('Delete Account') }}</h3>
                                    @livewire('profile.request-account-deletion-form')
                                </section>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
