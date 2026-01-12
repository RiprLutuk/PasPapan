{{-- <nav x-data="{ open: false }" class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800"> --}}
<nav x-data="{ open: false }"
    class="fixed top-0 left-0 z-50 w-full border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800 pt-[env(safe-area-inset-top)]">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ Auth::user()->isAdmin ? route('admin.dashboard') : route('home') }}">
                        <x-application-mark class="block h-9 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-2 sm:-my-px sm:ms-6 sm:flex md:ms-10 md:space-x-5 lg:space-x-8">
                    @if (Auth::user()->isAdmin)
                        {{-- 1. Dashboard --}}
                        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </x-nav-link>

                        {{-- 2. Attendance Group --}}
                        <x-nav-dropdown :active="request()->routeIs('admin.attendances') || request()->routeIs('admin.leaves') || request()->routeIs('admin.reimbursements') || request()->routeIs('admin.analytics') || request()->routeIs('admin.schedules') || request()->routeIs('admin.holidays') || request()->routeIs('admin.announcements')" triggerClasses="text-nowrap">
                            <x-slot name="trigger">
                                {{ __('Attendance') }}
                                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
                            </x-slot>
                            <x-slot name="content">
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Manage Attendance') }}
                                </div>
                                <x-dropdown-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')" wire:navigate>
                                    {{ __('Daily Attendance') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.leaves') }}" :active="request()->routeIs('admin.leaves')" wire:navigate>
                                    {{ __('Approvals') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.reimbursements') }}" :active="request()->routeIs('admin.reimbursements')" wire:navigate>
                                    {{ __('Reimbursements') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')" wire:navigate>
                                    {{ __('Schedules (Roster)') }}
                                </x-dropdown-link>
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <x-dropdown-link href="{{ route('admin.analytics') }}" :active="request()->routeIs('admin.analytics')" wire:navigate>
                                    {{ __('Analytics') }}
                                </x-dropdown-link>
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <x-dropdown-link href="{{ route('admin.holidays') }}" :active="request()->routeIs('admin.holidays')" wire:navigate>
                                    🗓️ {{ __('Holidays') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.announcements') }}" :active="request()->routeIs('admin.announcements')" wire:navigate>
                                    📢 {{ __('Announcements') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-nav-dropdown>

                        {{-- 3. Master Data Group --}}
                        <x-nav-dropdown :active="request()->routeIs('admin.masters.*') || request()->routeIs('admin.employees') || request()->routeIs('admin.barcodes') || request()->routeIs('admin.barcodes.*')" triggerClasses="text-nowrap">
                            <x-slot name="trigger">
                                {{ __('Master Data') }}
                                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
                            </x-slot>
                            <x-slot name="content">
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Organization') }}
                                </div>
                                <x-dropdown-link href="{{ route('admin.employees') }}" :active="request()->routeIs('admin.employees')" wire:navigate>
                                    {{ __('Employees') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.barcodes') }}" :active="request()->routeIs('admin.barcodes')" wire:navigate>
                                    {{ __('Barcode Locations') }}
                                </x-dropdown-link>
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Reference') }}
                                </div>
                                <x-dropdown-link href="{{ route('admin.masters.division') }}" :active="request()->routeIs('admin.masters.division')" wire:navigate>
                                    {{ __('Divisions') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.masters.job-title') }}" :active="request()->routeIs('admin.masters.job-title')" wire:navigate>
                                    {{ __('Job Titles') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.masters.education') }}" :active="request()->routeIs('admin.masters.education')" wire:navigate>
                                    {{ __('Education Levels') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.masters.shift') }}" :active="request()->routeIs('admin.masters.shift')" wire:navigate>
                                    {{ __('Shifts') }}
                                </x-dropdown-link>
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <x-dropdown-link href="{{ route('admin.masters.admin') }}" :active="request()->routeIs('admin.masters.admin')" wire:navigate>
                                    {{ __('Admins') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-nav-dropdown>

                        {{-- 4. System Group --}}
                        <x-nav-dropdown :active="request()->routeIs('admin.settings') || request()->routeIs('admin.system-maintenance') || request()->routeIs('admin.import-export.*')" triggerClasses="text-nowrap">
                            <x-slot name="trigger">
                                {{ __('System') }}
                                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')" wire:navigate>
                                    {{ __('App Settings') }}
                                </x-dropdown-link>
                                @if(Auth::user()->isSuperadmin)
                                    <x-dropdown-link href="{{ route('admin.system-maintenance') }}" :active="request()->routeIs('admin.system-maintenance')" wire:navigate>
                                        {{ __('Maintenance') }}
                                    </x-dropdown-link>
                                @endif
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Data Management') }}
                                </div>
                                <x-dropdown-link href="{{ route('admin.import-export.users') }}" :active="request()->routeIs('admin.import-export.users')" wire:navigate>
                                    {{ __('Import/Export Users') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('admin.import-export.attendances') }}" :active="request()->routeIs('admin.import-export.attendances')" wire:navigate>
                                    {{ __('Import/Export Attendance') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-nav-dropdown>
                    @else
                        <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')" wire:navigate>
                            {{ __('Home') }}
                        </x-nav-link>

                        @if(Auth::user()->subordinates->isNotEmpty())
                            <x-nav-link href="{{ route('approvals') }}" :active="request()->routeIs('approvals')" wire:navigate>
                                {{ __('Team Approvals') }}
                            </x-nav-link>
                        @endif
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">

                    <livewire:notifications-dropdown />






                    <div class="flex items-center mt-1">
                        <form method="POST" action="{{ route('user.language.update') }}">
                            @csrf
                            <input type="hidden" name="language" value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
                            <button type="submit"
                                class="relative inline-flex h-6 w-12 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600">
                                <span class="sr-only">Switch Language</span>
                                <!-- Labels -->
                                <span class="absolute inset-0 flex h-full w-full items-center justify-between px-1.5 text-[8px] font-bold text-gray-500 select-none">
                                    <span>ID</span>
                                    <span>EN</span>
                                </span>
                                <!-- Knob -->
                                <span
                                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ app()->getLocale() == 'en' ? 'translate-x-[24px]' : 'translate-x-0' }}">
                                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100">
                                        <span class="text-[10px] leading-none pt-0.5">
                                            {{ app()->getLocale() == 'id' ? '🇮🇩' : '🇺🇸' }}
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </form>
                    </div>

                    <x-theme-toggle id="theme-switcher-desktop" />

                    <!-- Settings Dropdown -->
                    @if (Auth::user()->isAdmin)
                    <div class="relative">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                    <button
                                        class="flex rounded-full border-2 border-transparent text-sm transition focus:border-gray-300 focus:outline-none">
                                        <img class="h-8 w-8 rounded-full object-cover"
                                            src="{{ Auth::user()->profile_photo_url }}"
                                            alt="{{ Auth::user()->name }}" />
                                    </button>
                                @else
                                    <span class="inline-flex rounded-md">
                                        <button type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:bg-gray-50 focus:outline-none active:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300 dark:focus:bg-gray-700 dark:active:bg-gray-700">
                                            {{ Auth::user()->name }}

                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                    </span>
                                @endif
                            </x-slot>

                            <x-slot name="content">
                                <!-- Account Management -->
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Manage Account') }}
                                </div>

                                <x-dropdown-link href="{{ route('profile.show') }}">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                    <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                        {{ __('API Tokens') }}
                                    </x-dropdown-link>
                                @endif

                                <div class="border-t border-gray-200 dark:border-gray-600"></div>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf

                                    <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endif
                </div>

                <div class="flex items-center sm:hidden">
                    <livewire:notifications-dropdown />
                    
                    <!-- Mobile Language Switcher -->
                    <!-- Mobile Language Switcher -->
                    <!-- Mobile Language Switcher -->
                    <div class="ml-2 flex items-center mt-1">
                        <form method="POST" action="{{ route('user.language.update') }}">
                            @csrf
                            <input type="hidden" name="language" value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
                            <button type="submit"
                                class="relative inline-flex h-6 w-12 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600">
                                <span class="sr-only">Switch Language</span>
                                <!-- Labels -->
                                <span class="absolute inset-0 flex h-full w-full items-center justify-between px-1.5 text-[8px] font-bold text-gray-500 select-none">
                                    <span>ID</span>
                                    <span>EN</span>
                                </span>
                                <!-- Knob -->
                                <span
                                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ app()->getLocale() == 'en' ? 'translate-x-[24px]' : 'translate-x-0' }}">
                                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100">
                                        <span class="text-[10px] leading-none pt-0.5">
                                            {{ app()->getLocale() == 'id' ? '🇮🇩' : '🇺🇸' }}
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <x-theme-toggle id="theme-switcher-mobile" class="sm:hidden" />

                <!-- Hamburger (Removed) -->
                <div class="-me-2 flex items-center sm:hidden hidden">
                </div>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="sm:hidden overflow-y-auto max-h-[calc(100vh-4rem)]">
        <div class="space-y-1 pb-3 pt-2">
            @if (Auth::user()->isAdmin)
                {{-- 1. Dashboard --}}
                <x-responsive-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>

                {{-- 2. Attendance Group --}}
                <div class="block px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    {{ __('Attendance') }}
                </div>
                <x-responsive-nav-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')" wire:navigate>
                    {{ __('Daily Attendance') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.leaves') }}" :active="request()->routeIs('admin.leaves')" wire:navigate>
                    {{ __('Approvals') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.reimbursements') }}" :active="request()->routeIs('admin.reimbursements')" wire:navigate>
                    {{ __('Reimbursements') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')" wire:navigate>
                    {{ __('Schedules (Roster)') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.analytics') }}" :active="request()->routeIs('admin.analytics')" wire:navigate>
                    {{ __('Analytics') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.holidays') }}" :active="request()->routeIs('admin.holidays')" wire:navigate>
                    🗓️ {{ __('Holidays') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.announcements') }}" :active="request()->routeIs('admin.announcements')" wire:navigate>
                    📢 {{ __('Announcements') }}
                </x-responsive-nav-link>

                {{-- 3. Master Data Group --}}
                <div class="block px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    {{ __('Organization & Reference') }}
                </div>
                <x-responsive-nav-link href="{{ route('admin.employees') }}" :active="request()->routeIs('admin.employees')" wire:navigate>
                    {{ __('Employees') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.barcodes') }}" :active="request()->routeIs('admin.barcodes')" wire:navigate>
                    {{ __('Barcode Locations') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.masters.division') }}" :active="request()->routeIs('admin.masters.division')" wire:navigate>
                    {{ __('Divisions') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.masters.job-title') }}" :active="request()->routeIs('admin.masters.job-title')" wire:navigate>
                    {{ __('Job Titles') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.masters.education') }}" :active="request()->routeIs('admin.masters.education')" wire:navigate>
                    {{ __('Education') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.masters.shift') }}" :active="request()->routeIs('admin.masters.shift')" wire:navigate>
                    {{ __('Shifts') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.masters.admin') }}" :active="request()->routeIs('admin.masters.admin')" wire:navigate>
                    {{ __('Admins') }}
                </x-responsive-nav-link>

                {{-- 4. System Group --}}
                <div class="block px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    {{ __('System') }}
                </div>
                <x-responsive-nav-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')" wire:navigate>
                    {{ __('App Settings') }}
                </x-responsive-nav-link>
                @if(Auth::user()->isSuperadmin)
                    <x-responsive-nav-link href="{{ route('admin.system-maintenance') }}" :active="request()->routeIs('admin.system-maintenance')" wire:navigate>
                        {{ __('Maintenance') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link href="{{ route('admin.import-export.users') }}" :active="request()->routeIs('admin.import-export.users')" wire:navigate>
                    {{ __('Import/Export Users') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('admin.import-export.attendances') }}" :active="request()->routeIs('admin.import-export.attendances')" wire:navigate>
                    {{ __('Import/Export Attendance') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')" wire:navigate>
                    {{ __('Home') }}
                </x-responsive-nav-link>

                @if(Auth::user()->subordinates->isNotEmpty())
                    <x-responsive-nav-link href="{{ route('approvals') }}" :active="request()->routeIs('approvals')" wire:navigate>
                        {{ __('Team Approvals') }}
                    </x-responsive-nav-link>
                @endif
            @endif
        </div>

        <!-- Responsive Settings Options -->
        @if (Auth::user()->isAdmin)
        <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
            <div class="flex items-center px-4">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <div class="me-3 shrink-0">
                        <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}"
                            alt="{{ Auth::user()->name }}" />
                    </div>
                @endif

                <div>
                    <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                    <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Account Management -->
                <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">
                        {{ __('API Tokens') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf

                    <x-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endif
    </div>
</nav>
