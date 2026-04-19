<x-app-layout>
  @php($backRoute = auth()->user()->isAdmin ? route('admin.dashboard') : route('home'))
  @php($profileTabs = [])
  @if (Laravel\Fortify\Features::canUpdateProfileInformation())
      @php($profileTabs[] = 'details')
  @endif
  @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
      @php($profileTabs[] = 'password')
  @endif
  @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
      @php($profileTabs[] = 'security')
  @endif
  @php($profileTabs[] = 'sessions')
  @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
      @php($profileTabs[] = 'danger')
  @endif
  @php($defaultProfileTab = $profileTabs[0] ?? 'details')

  <div
      class="profile-page user-page-shell"
      x-data="{
          tabs: @js($profileTabs),
          activeTab: @js($defaultProfileTab),
          init() {
              const syncFromHash = () => {
                  const requestedTab = window.location.hash.replace('#', '');

                  if (this.tabs.includes(requestedTab)) {
                      this.activeTab = requestedTab;
                      return;
                  }

                  this.writeHash(this.activeTab);
              };

              syncFromHash();
              window.addEventListener('hashchange', () => {
                  const requestedTab = window.location.hash.replace('#', '');

                  if (this.tabs.includes(requestedTab)) {
                      this.activeTab = requestedTab;
                  }
              });
          },
          selectTab(tab) {
              if (!this.tabs.includes(tab)) {
                  return;
              }

              this.activeTab = tab;
              this.writeHash(tab);
          },
          writeHash(tab) {
              const url = new URL(window.location.href);
              url.hash = tab;
              window.history.replaceState({}, '', url);
          },
      }">
    <div class="user-page-container user-page-container--standard">
        <div aria-labelledby="profile-page-title">
            <x-user.page-header
                :back-href="$backRoute"
                :title="__('Profile')"
                :description="auth()->user()->email"
                title-id="profile-page-title"
                plain>
                <x-slot name="icon">
                    <x-heroicon-o-user-circle class="h-6 w-6" />
                </x-slot>
                <x-slot name="actions">
                    @if (auth()->user()->hasVerifiedEmail())
                        <span class="profile-status profile-status--verified">
                            <x-heroicon-s-check-circle class="h-4 w-4" />
                            <span>{{ __('Verified') }}</span>
                        </span>
                    @else
                        <span class="profile-status profile-status--warning">
                            <x-heroicon-s-exclamation-circle class="h-4 w-4" />
                            <span>{{ __('Unverified') }}</span>
                        </span>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="profile-section-nav" role="tablist" aria-label="{{ __('Profile sections') }}">
                @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                    <button
                        type="button"
                        id="profile-details-tab"
                        class="profile-section-nav__link"
                        role="tab"
                        aria-controls="profile-information"
                        x-bind:aria-selected="(activeTab === 'details').toString()"
                        x-bind:tabindex="activeTab === 'details' ? 0 : -1"
                        x-on:click="selectTab('details')">
                        {{ __('Details') }}
                    </button>
                @endif

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                    <button
                        type="button"
                        id="profile-password-tab"
                        class="profile-section-nav__link"
                        role="tab"
                        aria-controls="profile-password"
                        x-bind:aria-selected="(activeTab === 'password').toString()"
                        x-bind:tabindex="activeTab === 'password' ? 0 : -1"
                        x-on:click="selectTab('password')">
                        {{ __('Password') }}
                    </button>
                @endif

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <button
                        type="button"
                        id="profile-security-tab"
                        class="profile-section-nav__link"
                        role="tab"
                        aria-controls="profile-security"
                        x-bind:aria-selected="(activeTab === 'security').toString()"
                        x-bind:tabindex="activeTab === 'security' ? 0 : -1"
                        x-on:click="selectTab('security')">
                        {{ __('Security') }}
                    </button>
                @endif

                <button
                    type="button"
                    id="profile-sessions-tab"
                    class="profile-section-nav__link"
                    role="tab"
                    aria-controls="profile-sessions"
                    x-bind:aria-selected="(activeTab === 'sessions').toString()"
                    x-bind:tabindex="activeTab === 'sessions' ? 0 : -1"
                    x-on:click="selectTab('sessions')">
                    {{ __('Sessions') }}
                </button>

                @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                    <button
                        type="button"
                        id="profile-danger-tab"
                        class="profile-section-nav__link"
                        role="tab"
                        aria-controls="profile-danger-zone"
                        x-bind:aria-selected="(activeTab === 'danger').toString()"
                        x-bind:tabindex="activeTab === 'danger' ? 0 : -1"
                        x-on:click="selectTab('danger')">
                        {{ __('Danger') }}
                    </button>
                @endif
            </div>

            <div class="profile-page-body">
                <main aria-label="{{ __('Profile settings') }}">
                    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                        <section
                            id="profile-information"
                            role="tabpanel"
                            aria-labelledby="profile-details-tab"
                            x-cloak
                            x-show="activeTab === 'details'"
                            x-transition.opacity.duration.150ms>
                            <h2 id="profile-information-heading" class="sr-only">{{ __('Profile Information') }}</h2>
                            @livewire('profile.update-profile-information-form')
                        </section>
                    @endif

                    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                        <section
                            id="profile-password"
                            role="tabpanel"
                            aria-labelledby="profile-password-tab"
                            x-cloak
                            x-show="activeTab === 'password'"
                            x-transition.opacity.duration.150ms>
                            <h2 id="profile-password-heading" class="sr-only">{{ __('Update Password') }}</h2>
                            @livewire('profile.update-password-form')
                        </section>
                    @endif

                    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                        <section
                            id="profile-security"
                            role="tabpanel"
                            aria-labelledby="profile-security-tab"
                            x-cloak
                            x-show="activeTab === 'security'"
                            x-transition.opacity.duration.150ms>
                            <h2 id="profile-security-heading" class="sr-only">{{ __('Two Factor Authentication') }}</h2>
                            @livewire('profile.two-factor-authentication-form')
                        </section>
                    @endif

                    <section
                        id="profile-sessions"
                        role="tabpanel"
                        aria-labelledby="profile-sessions-tab"
                        x-cloak
                        x-show="activeTab === 'sessions'"
                        x-transition.opacity.duration.150ms>
                        <h2 id="profile-sessions-heading" class="sr-only">{{ __('Browser Sessions') }}</h2>
                        @livewire('profile.logout-other-browser-sessions-form')
                    </section>

                    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                        <section
                            id="profile-danger-zone"
                            role="tabpanel"
                            aria-labelledby="profile-danger-tab"
                            x-cloak
                            x-show="activeTab === 'danger'"
                            x-transition.opacity.duration.150ms>
                            <h2 id="profile-danger-zone-heading" class="sr-only">{{ __('Delete Account') }}</h2>
                            @livewire('profile.delete-user-form')
                        </section>
                    @endif
                </main>
            </div>
        </div>
    </div>
  </div>
</x-app-layout>
