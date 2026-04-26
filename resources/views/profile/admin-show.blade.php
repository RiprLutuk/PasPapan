<x-app-layout>
    @php
        $user = auth()->user();
        $backRouteName = $user->preferredAdminRouteName() ?? 'home';
        $profileSections = [];

        if (Laravel\Fortify\Features::canUpdateProfileInformation()) {
            $profileSections['details'] = [
                'title' => __('Profile Information'),
                'description' => __('Update your profile photo, contact information, and address.'),
            ];
        }

        if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords())) {
            $profileSections['password'] = [
                'title' => __('Password'),
                'description' => __('Change the password used to access the admin panel.'),
            ];
        }

        if (Laravel\Fortify\Features::canManageTwoFactorAuthentication()) {
            $profileSections['security'] = [
                'title' => __('Two Factor Authentication'),
                'description' => __('Manage the second factor for this administrator account.'),
            ];
        }

        $profileSections['sessions'] = [
            'title' => __('Browser Sessions'),
            'description' => __('Review active sessions and sign out from other devices.'),
        ];
    @endphp

    <x-admin.page-shell :title="__('Admin Profile')" :description="__('Manage your administrator account, security, and preferences.')" x-data="{ activeTab: 'details' }">
        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <img class="h-12 w-12 rounded-full object-cover ring-2 ring-white shadow-sm dark:ring-slate-800" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                        <div class="min-w-0">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <h2 class="truncate text-sm font-semibold text-slate-950 dark:text-white">{{ $user->name }}</h2>
                                <x-admin.status-badge tone="info" pill="true">{{ $user->isSuperadmin ? __('Super Admin') : __('Admin') }}</x-admin.status-badge>
                                <x-admin.status-badge :tone="$user->hasVerifiedEmail() ? 'success' : 'warning'" pill="true">{{ $user->hasVerifiedEmail() ? __('Verified') : __('Unverified') }}</x-admin.status-badge>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-600 dark:text-slate-300">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                        <span class="text-xs font-semibold uppercase text-slate-400 dark:text-slate-500">{{ __('Language') }}</span>
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
                                    <span class="absolute inset-0 flex h-full w-full items-center justify-center">
                                        {{ app()->getLocale() == 'id' ? 'ID' : 'EN' }}
                                    </span>
                                </span>
                            </button>
                        </form>
                    </div>

                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                        <span class="text-xs font-semibold uppercase text-slate-400 dark:text-slate-500">{{ __('Appearance') }}</span>
                        <x-navigation.theme-toggle id="theme-switcher-admin-profile" />
                    </div>
                </div>

            </x-admin.page-tools>
        </x-slot>

        <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="space-y-2">
                @foreach ($profileSections as $sectionKey => $section)
                    <button type="button" class="w-full rounded-xl border px-4 py-3 text-left transition"
                        x-on:click="activeTab = @js($sectionKey)"
                        x-bind:class="activeTab === @js($sectionKey) ?
                            'border-primary-300 bg-primary-50 text-primary-900 shadow-sm dark:border-primary-700 dark:bg-primary-900/20 dark:text-primary-100' :
                            'border-slate-200 bg-white text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-600'">
                        <span class="block text-sm font-semibold">{{ $section['title'] }}</span>
                        <span
                            class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $section['description'] }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="min-w-0 space-y-6">
                @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                    <section x-cloak x-show="activeTab === 'details'" x-transition.opacity.duration.150ms>
                        @livewire('profile.update-profile-information-form')
                    </section>
                @endif

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                    <section x-cloak x-show="activeTab === 'password'" x-transition.opacity.duration.150ms>
                        @livewire('profile.update-password-form')
                    </section>
                @endif

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <section x-cloak x-show="activeTab === 'security'" x-transition.opacity.duration.150ms>
                        @livewire('profile.two-factor-authentication-form')
                    </section>
                @endif

                <section x-cloak x-show="activeTab === 'sessions'" x-transition.opacity.duration.150ms>
                    @livewire('profile.logout-other-browser-sessions-form')
                </section>
            </div>
        </div>
    </x-admin.page-shell>
</x-app-layout>
