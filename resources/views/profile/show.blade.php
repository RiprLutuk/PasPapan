<x-app-layout>
  @php($backRoute = auth()->user()->isAdmin ? route('admin.dashboard') : route('home'))

  <div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="profile-page-title" class="user-page-surface">
            <x-user.page-header
                :back-href="$backRoute"
                :title="__('Profile')"
                title-id="profile-page-title">
                <x-slot name="icon">
                    <span class="text-lg leading-none">👤</span>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body">
                <main class="space-y-6" aria-label="{{ __('Profile settings') }}">
                    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                        @livewire('profile.update-profile-information-form')
                    @endif

                    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                        @livewire('profile.update-password-form')
                    @endif

                    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                        @livewire('profile.two-factor-authentication-form')
                    @endif

                    @livewire('profile.logout-other-browser-sessions-form')

                    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                        @livewire('profile.delete-user-form')
                    @endif
                </main>
            </div>
        </section>
    </div>
  </div>
</x-app-layout>
