<x-app-layout>
  <div class="py-6 lg:py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {{-- Custom Header --}}
        <div class="flex items-center gap-3 mb-6 lg:mb-8">
            <x-secondary-button href="{{ url()->previous() }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700">
                <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
            </x-secondary-button>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">
                    👤
                </span>
                {{ __('Profile') }}
            </h2>
        </div>

        <div class="space-y-6">
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
        </div>
    </div>
  </div>
</x-app-layout>
