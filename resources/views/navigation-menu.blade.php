{{-- <nav x-data="{ open: false }" class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800"> --}}
@php
    $isAdminRoute = request()->routeIs('admin.*');
@endphp

<nav x-data="{ open: false }" aria-label="{{ $isAdminRoute ? __('Primary navigation') : __('User navigation') }}"
    class="fixed top-0 left-0 z-50 w-full border-b border-gray-200/80 bg-white/95 backdrop-blur-sm dark:border-gray-700 dark:bg-gray-800/95 pt-[env(safe-area-inset-top)]">
    <!-- Primary Navigation Menu -->
    <div
        class="{{ $isAdminRoute ? 'w-full px-4 sm:px-6 lg:px-8 2xl:px-10' : 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8' }}">
        <div class="flex {{ $isAdminRoute ? 'h-16' : 'h-14 sm:h-[4.25rem]' }} justify-between gap-3">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ Auth::user()->isAdmin ? route('admin.dashboard') : route('home') }}"
                        class="{{ $isAdminRoute ? 'rounded-xl p-1' : 'rounded-2xl p-1.5 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2 dark:hover:bg-gray-800 dark:focus-visible:ring-primary-300 dark:focus-visible:ring-offset-gray-900' }}">
                        <x-branding.application-mark
                            class="block {{ $isAdminRoute ? 'h-9 w-auto' : 'h-10 w-10 sm:h-11 sm:w-11' }}" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-2 sm:-my-px sm:ms-6 sm:flex md:ms-10 md:space-x-5 lg:space-x-8">
                    @if (Auth::user()->isAdmin || Auth::user()->isSuperadmin)
                        {{-- 1. Dashboard --}}
                        <x-navigation.nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </x-navigation.nav-link>

                        {{-- 2. Attendance Group --}}
                        <x-navigation.nav-dropdown :active="request()->routeIs('admin.attendances') ||
                            request()->routeIs('admin.leaves') ||
                            request()->routeIs('admin.analytics') ||
                            request()->routeIs('admin.schedules') ||
                            request()->routeIs('admin.holidays') ||
                            request()->routeIs('admin.announcements')" triggerClasses="text-nowrap">
                            <x-slot name="trigger">
                                {{ __('Attendance') }}
                                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
                            </x-slot>
                            <x-slot name="content">
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Manage Attendance') }}
                                </div>
                                <x-navigation.dropdown-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')"
                                    wire:navigate>
                                    {{ __('Daily Attendance') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.leaves') }}" :active="request()->routeIs('admin.leaves')"
                                    wire:navigate>
                                    {{ __('Approvals') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.overtime') }}" :active="request()->routeIs('admin.overtime')"
                                    wire:navigate>
                                    {{ __('Overtime') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')"
                                    wire:navigate>
                                    {{ __('Schedules (Roster)') }}
                                </x-navigation.dropdown-link>
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                @if (\App\Helpers\Editions::reportingLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Analytics Locked', message: 'Advanced Analytics is an Enterprise Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Analytics') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.analytics') }}" :active="request()->routeIs('admin.analytics')"
                                        wire:navigate>
                                        {{ __('Analytics') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <x-navigation.dropdown-link href="{{ route('admin.holidays') }}" :active="request()->routeIs('admin.holidays')"
                                    wire:navigate>
                                    🗓️ {{ __('Holidays') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.announcements') }}" :active="request()->routeIs('admin.announcements')"
                                    wire:navigate>
                                    📢 {{ __('Announcements') }}
                                </x-navigation.dropdown-link>
                            </x-slot>
                        </x-navigation.nav-dropdown>

                        {{-- 2.5 Finance Group --}}
                        <x-navigation.nav-dropdown :active="request()->routeIs('admin.payrolls') || request()->routeIs('admin.reimbursements')" triggerClasses="text-nowrap">
                            <x-slot name="trigger">
                                {{ __('Finance') }}
                                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
                            </x-slot>
                            <x-slot name="content">
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Financial Management') }}
                                </div>
                                @if (\App\Helpers\Editions::payrollLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Payroll Locked', message: 'Payroll Management is an Enterprise Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Payroll') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.payrolls') }}" :active="request()->routeIs('admin.payrolls')"
                                        wire:navigate>
                                        {{ __('Payroll') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                <x-navigation.dropdown-link href="{{ route('admin.reimbursements') }}"
                                    :active="request()->routeIs('admin.reimbursements')" wire:navigate>
                                    {{ __('Reimbursements') }}
                                </x-navigation.dropdown-link>
                                @if (\App\Helpers\Editions::payrollLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Kasbon Locked', message: 'Kasbon Feature is an Enterprise Edition Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Manage Kasbon') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.manage-kasbon') }}"
                                        :active="request()->routeIs('admin.manage-kasbon')" wire:navigate>
                                        {{ __('Manage Kasbon') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                @if (\App\Helpers\Editions::payrollLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Settings Locked', message: 'Payroll Settings is an Enterprise Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Payroll Settings') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.payroll.settings') }}"
                                        :active="request()->routeIs('admin.payroll.settings')" wire:navigate>
                                        {{ __('Payroll Settings') }}
                                    </x-navigation.dropdown-link>
                                @endif
                            </x-slot>
                        </x-navigation.nav-dropdown>

                        {{-- 3. Master Data Group --}}
                        <x-navigation.nav-dropdown :active="request()->routeIs('admin.masters.*') ||
                            request()->routeIs('admin.employees') ||
                            request()->routeIs('admin.barcodes') ||
                            request()->routeIs('admin.barcodes.*') ||
                            request()->routeIs('admin.appraisals') ||
                            request()->routeIs('admin.assets')" triggerClasses="text-nowrap">
                            <x-slot name="trigger">
                                {{ __('Master Data') }}
                                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
                            </x-slot>
                            <x-slot name="content">
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Organization') }}
                                </div>
                                <x-navigation.dropdown-link href="{{ route('admin.employees') }}" :active="request()->routeIs('admin.employees')"
                                    wire:navigate>
                                    {{ __('Employees') }}
                                </x-navigation.dropdown-link>
                                @if (\App\Helpers\Editions::appraisalLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Appraisals Locked', message: 'KPI & Performance Appraisal is an Enterprise Edition Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Performance Appraisals') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.appraisals') }}"
                                        :active="request()->routeIs('admin.appraisals')" wire:navigate>
                                        {{ __('Performance Appraisals') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                @if (\App\Helpers\Editions::assetLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Asset Management Locked', message: 'Asset Tracking is an Enterprise Edition Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Company Assets') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.assets') }}" :active="request()->routeIs('admin.assets')"
                                        wire:navigate>
                                        {{ __('Company Assets') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                <x-navigation.dropdown-link href="{{ route('admin.barcodes') }}" :active="request()->routeIs('admin.barcodes')"
                                    wire:navigate>
                                    {{ __('Barcode Locations') }}
                                </x-navigation.dropdown-link>
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Reference') }}
                                </div>
                                <x-navigation.dropdown-link href="{{ route('admin.masters.division') }}"
                                    :active="request()->routeIs('admin.masters.division')" wire:navigate>
                                    {{ __('Divisions') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.masters.job-title') }}"
                                    :active="request()->routeIs('admin.masters.job-title')" wire:navigate>
                                    {{ __('Job Titles') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.masters.education') }}"
                                    :active="request()->routeIs('admin.masters.education')" wire:navigate>
                                    {{ __('Education Levels') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.masters.shift') }}"
                                    :active="request()->routeIs('admin.masters.shift')" wire:navigate>
                                    {{ __('Shifts') }}
                                </x-navigation.dropdown-link>
                                <x-navigation.dropdown-link href="{{ route('admin.masters.admin') }}"
                                    :active="request()->routeIs('admin.masters.admin')" wire:navigate>
                                    {{ __('Administrators') }}
                                </x-navigation.dropdown-link>
                            </x-slot>
                        </x-navigation.nav-dropdown>

                        {{-- 4. System Group --}}
                        <x-navigation.nav-dropdown :active="request()->routeIs('admin.settings') ||
                            request()->routeIs('admin.settings.kpi') ||
                            request()->routeIs('admin.system-maintenance') ||
                            request()->routeIs('admin.import-export.*')" triggerClasses="text-nowrap">
                            <x-slot name="trigger">
                                {{ __('System') }}
                                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
                            </x-slot>
                            <x-slot name="content">
                                <x-navigation.dropdown-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')"
                                    wire:navigate>
                                    {{ __('App Settings') }}
                                </x-navigation.dropdown-link>
                                @if (\App\Helpers\Editions::appraisalLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'KPI Settings Locked', message: 'KPI Settings are an Enterprise Edition Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('KPI Settings') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.settings.kpi') }}"
                                        :active="request()->routeIs('admin.settings.kpi')" wire:navigate>
                                        {{ __('KPI Settings') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                @if (Auth::user()->isSuperadmin)
                                    <x-navigation.dropdown-link href="{{ route('admin.system-maintenance') }}"
                                        :active="request()->routeIs('admin.system-maintenance')" wire:navigate>
                                        {{ __('Maintenance') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Data Management') }}
                                </div>
                                @if (\App\Helpers\Editions::reportingLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Import/Export Locked', message: 'User Import/Export is an Enterprise Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Import/Export Users') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.import-export.users') }}"
                                        :active="request()->routeIs('admin.import-export.users')" wire:navigate>
                                        {{ __('Import/Export Users') }}
                                    </x-navigation.dropdown-link>
                                @endif
                                @if (\App\Helpers\Editions::reportingLocked())
                                    <button type="button"
                                        @click.prevent="$dispatch('feature-lock', { title: 'Import/Export Locked', message: 'Attendance Import/Export is an Enterprise Feature 🔒. Please Upgrade.' })"
                                        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                        {{ __('Import/Export Attendance') }} 🔒
                                    </button>
                                @else
                                    <x-navigation.dropdown-link href="{{ route('admin.import-export.attendances') }}"
                                        :active="request()->routeIs('admin.import-export.attendances')" wire:navigate>
                                        {{ __('Import/Export Attendance') }}
                                    </x-navigation.dropdown-link>
                                @endif
                            </x-slot>
                        </x-navigation.nav-dropdown>
                    @else
                        <x-navigation.nav-link href="{{ route('home') }}" :active="request()->routeIs('home')" wire:navigate>
                            {{ __('Home') }}
                        </x-navigation.nav-link>

                        {{-- @if (Auth::user()->subordinates->isNotEmpty())
                    <x-navigation.nav-link href="{{ route('approvals') }}" :active="request()->routeIs('approvals')" wire:navigate>
                        {{ __('Team Approvals') }}
                    </x-navigation.nav-link>
                    @endif --}}
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">
                    <div class="{{ $isAdminRoute ? 'flex items-center gap-3' : 'topbar-action-cluster' }}">

                        <livewire:shared.notifications-dropdown />

                        <div class="flex items-center">
                            <form method="POST" action="{{ route('user.language.update') }}">
                                @csrf
                                <input type="hidden" name="language"
                                    value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
                                <button type="submit" class="language-toggle"
                                    aria-label="{{ __('Switch language to :language', ['language' => app()->getLocale() == 'id' ? 'English' : 'Bahasa Indonesia']) }}">
                                    <span class="sr-only">Switch Language</span>
                                    <span class="language-toggle__labels" aria-hidden="true">
                                        <span class="language-toggle__label">ID</span>
                                        <span class="language-toggle__label">EN</span>
                                    </span>
                                    <span
                                        class="language-toggle__thumb {{ app()->getLocale() == 'en' ? 'translate-x-[2.35rem]' : 'translate-x-0' }}">
                                        <span
                                            class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100">
                                            <span class="leading-none">
                                                {{ app()->getLocale() == 'id' ? '🇮🇩' : '🇺🇸' }}
                                            </span>
                                        </span>
                                    </span>
                                </button>
                            </form>
                        </div>

                        <x-navigation.theme-toggle id="theme-switcher-desktop" />
                    </div>

                    <!-- Settings Dropdown -->
                    @if (Auth::user()->isAdmin)
                        <div class="relative">
                            <x-navigation.dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                        <button
                                            class="flex rounded-full border-2 border-transparent text-sm transition"
                                            aria-label="{{ __('Open account menu') }}">
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

                                    <x-navigation.dropdown-link href="{{ route('profile.show') }}">
                                        {{ __('Profile') }}
                                    </x-navigation.dropdown-link>

                                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                        <x-navigation.dropdown-link href="{{ route('api-tokens.index') }}">
                                            {{ __('API Tokens') }}
                                        </x-navigation.dropdown-link>
                                    @endif

                                    <div class="border-t border-gray-200 dark:border-gray-600"></div>

                                    <!-- Authentication -->
                                    <form method="POST" action="{{ route('logout') }}" x-data>
                                        @csrf

                                        <x-navigation.dropdown-link href="{{ route('logout') }}"
                                            @click.prevent="$root.submit();">
                                            {{ __('Log Out') }}
                                        </x-navigation.dropdown-link>
                                    </form>
                                </x-slot>
                            </x-navigation.dropdown>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2 sm:hidden">
                    <livewire:shared.notifications-dropdown />

                    <div class="flex items-center">
                        <form method="POST" action="{{ route('user.language.update') }}">
                            @csrf
                            <input type="hidden" name="language"
                                value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
                            <button type="submit" class="language-toggle language-toggle--compact"
                                aria-label="{{ __('Switch language to :language', ['language' => app()->getLocale() == 'id' ? 'English' : 'Bahasa Indonesia']) }}">
                                <span class="sr-only">Switch Language</span>
                                <span class="language-toggle__labels" aria-hidden="true">
                                    <span class="language-toggle__label">ID</span>
                                    <span class="language-toggle__label">EN</span>
                                </span>
                                <span
                                    class="language-toggle__thumb {{ app()->getLocale() == 'en' ? 'translate-x-[2.35rem]' : 'translate-x-0' }}">
                                    <span
                                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100">
                                        <span class="leading-none">
                                            {{ app()->getLocale() == 'id' ? '🇮🇩' : '🇺🇸' }}
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <x-navigation.theme-toggle id="theme-switcher-mobile" class="sm:hidden" />

                <!-- Hamburger -->
                @if (Auth::user()->isAdmin)
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="open = ! open"
                            class="wcag-touch-target inline-flex items-center justify-center rounded-md p-2 text-gray-600 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-white"
                            :aria-expanded="open.toString()" aria-controls="mobile-navigation">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div id="mobile-navigation" :class="{ 'block': open, 'hidden': !open }"
        class="sm:hidden overflow-y-auto max-h-[calc(100vh-4rem)]">
        <div class="space-y-1 pb-3 pt-2">
            @if (Auth::user()->isAdmin)
                {{-- 1. Dashboard --}}
                <x-navigation.responsive-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </x-navigation.responsive-nav-link>

                {{-- 2. Attendance Group --}}
                <div x-data="{ expanded: false }" class="border-t border-gray-100 dark:border-gray-700/50">
                    <button @click="expanded = !expanded"
                        class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <span>{{ __('Attendance') }}</span>
                        <svg class="h-4 w-4 transform transition-transform duration-200"
                            :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="expanded" style="display: none;" class="bg-gray-50/50 dark:bg-black/20 pb-2">
                        <x-navigation.responsive-nav-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')"
                            wire:navigate>
                            {{ __('Daily Attendance') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.leaves') }}" :active="request()->routeIs('admin.leaves')"
                            wire:navigate>
                            {{ __('Approvals') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')"
                            wire:navigate>
                            {{ __('Schedules (Roster)') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.analytics') }}" :active="request()->routeIs('admin.analytics')"
                            wire:navigate>
                            {{ __('Analytics') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.holidays') }}" :active="request()->routeIs('admin.holidays')"
                            wire:navigate>
                            🗓️ {{ __('Holidays') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.announcements') }}"
                            :active="request()->routeIs('admin.announcements')" wire:navigate>
                            📢 {{ __('Announcements') }}
                        </x-navigation.responsive-nav-link>
                    </div>
                </div>

                {{-- 2.5 Finance Group --}}
                <div x-data="{ expanded: false }" class="border-t border-gray-100 dark:border-gray-700/50">
                    <button @click="expanded = !expanded"
                        class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <span>{{ __('Finance') }}</span>
                        <svg class="h-4 w-4 transform transition-transform duration-200"
                            :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="expanded" style="display: none;" class="bg-gray-50/50 dark:bg-black/20 pb-2">
                        <x-navigation.responsive-nav-link href="{{ route('admin.payrolls') }}" :active="request()->routeIs('admin.payrolls')"
                            wire:navigate>
                            {{ __('Payroll') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.reimbursements') }}"
                            :active="request()->routeIs('admin.reimbursements')" wire:navigate>
                            {{ __('Reimbursements') }}
                        </x-navigation.responsive-nav-link>
                        @if (\App\Helpers\Editions::payrollLocked())
                            <button type="button"
                                @click.prevent="$dispatch('feature-lock', { title: 'Kasbon Locked', message: 'Kasbon Feature is an Enterprise Edition Feature 🔒. Please Upgrade.' })"
                                class="block w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out">
                                {{ __('Manage Kasbon') }} 🔒
                            </button>
                        @else
                            <x-navigation.responsive-nav-link href="{{ route('admin.manage-kasbon') }}"
                                :active="request()->routeIs('admin.manage-kasbon')" wire:navigate>
                                {{ __('Manage Kasbon') }}
                            </x-navigation.responsive-nav-link>
                        @endif
                    </div>
                </div>

                {{-- 3. Master Data Group --}}
                <div x-data="{ expanded: false }" class="border-t border-gray-100 dark:border-gray-700/50">
                    <button @click="expanded = !expanded"
                        class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <span>{{ __('Organization & Reference') }}</span>
                        <svg class="h-4 w-4 transform transition-transform duration-200"
                            :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="expanded" style="display: none;" class="bg-gray-50/50 dark:bg-black/20 pb-2">
                        <x-navigation.responsive-nav-link href="{{ route('admin.employees') }}" :active="request()->routeIs('admin.employees')"
                            wire:navigate>
                            {{ __('Employees') }}
                        </x-navigation.responsive-nav-link>
                        @if (\App\Helpers\Editions::appraisalLocked())
                            <button type="button"
                                @click.prevent="$dispatch('feature-lock', { title: 'Appraisals Locked', message: 'KPI & Performance Appraisal is an Enterprise Edition Feature 🔒. Please Upgrade.' })"
                                class="block w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out">
                                {{ __('Performance Appraisals') }} 🔒
                            </button>
                        @else
                            <x-navigation.responsive-nav-link href="{{ route('admin.appraisals') }}"
                                :active="request()->routeIs('admin.appraisals')" wire:navigate>
                                {{ __('Performance Appraisals') }}
                            </x-navigation.responsive-nav-link>
                        @endif
                        @if (\App\Helpers\Editions::assetLocked())
                            <button type="button"
                                @click.prevent="$dispatch('feature-lock', { title: 'Asset Management Locked', message: 'Asset Tracking is an Enterprise Edition Feature 🔒. Please Upgrade.' })"
                                class="block w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out">
                                {{ __('Company Assets') }} 🔒
                            </button>
                        @else
                            <x-navigation.responsive-nav-link href="{{ route('admin.assets') }}" :active="request()->routeIs('admin.assets')"
                                wire:navigate>
                                {{ __('Company Assets') }}
                            </x-navigation.responsive-nav-link>
                        @endif
                        <x-navigation.responsive-nav-link href="{{ route('admin.barcodes') }}" :active="request()->routeIs('admin.barcodes')"
                            wire:navigate>
                            {{ __('Barcode Locations') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.masters.division') }}"
                            :active="request()->routeIs('admin.masters.division')" wire:navigate>
                            {{ __('Divisions') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.masters.job-title') }}"
                            :active="request()->routeIs('admin.masters.job-title')" wire:navigate>
                            {{ __('Job Titles') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.masters.education') }}"
                            :active="request()->routeIs('admin.masters.education')" wire:navigate>
                            {{ __('Education') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.masters.shift') }}"
                            :active="request()->routeIs('admin.masters.shift')" wire:navigate>
                            {{ __('Shifts') }}
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.masters.admin') }}"
                            :active="request()->routeIs('admin.masters.admin')" wire:navigate>
                            {{ __('Admins') }}
                        </x-navigation.responsive-nav-link>
                    </div>
                </div>

                {{-- 4. System Group --}}
                <div x-data="{ expanded: false }" class="border-t border-gray-100 dark:border-gray-700/50">
                    <button @click="expanded = !expanded"
                        class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <span>{{ __('System') }}</span>
                        <svg class="h-4 w-4 transform transition-transform duration-200"
                            :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="expanded" style="display: none;" class="bg-gray-50/50 dark:bg-black/20 pb-2">
                        <x-navigation.responsive-nav-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')"
                            wire:navigate>
                            {{ __('App Settings') }}
                        </x-navigation.responsive-nav-link>
                        @if (Auth::user()->isSuperadmin)
                            <x-navigation.responsive-nav-link href="{{ route('admin.system-maintenance') }}"
                                :active="request()->routeIs('admin.system-maintenance')" wire:navigate>
                                {{ __('Maintenance') }}
                            </x-navigation.responsive-nav-link>
                        @endif
                        <x-navigation.responsive-nav-link href="{{ route('admin.import-export.users') }}"
                            :active="request()->routeIs('admin.import-export.users')" wire:navigate>
                            {{ __('Import/Export Users') }}
                            @if (\App\Helpers\Editions::reportingLocked())
                                🔒
                            @endif
                        </x-navigation.responsive-nav-link>
                        <x-navigation.responsive-nav-link href="{{ route('admin.import-export.attendances') }}"
                            :active="request()->routeIs('admin.import-export.attendances')" wire:navigate>
                            {{ __('Import/Export Attendance') }}
                            @if (\App\Helpers\Editions::reportingLocked())
                                🔒
                            @endif
                        </x-navigation.responsive-nav-link>
                    </div>
                </div>
            @else
                <x-navigation.responsive-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')" wire:navigate>
                    {{ __('Home') }}
                </x-navigation.responsive-nav-link>

                @if (Auth::user()->subordinates->isNotEmpty())
                    <x-navigation.responsive-nav-link href="{{ route('approvals') }}" :active="request()->routeIs('approvals')"
                        wire:navigate>
                        {{ __('Team Approvals') }}
                    </x-navigation.responsive-nav-link>
                @endif
            @endif
        </div>

        <!-- Responsive Settings Options -->
        @if (Auth::user()->isAdmin)
            <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
                <div class="flex items-center px-4">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <div class="me-3 shrink-0">
                            <img class="h-10 w-10 rounded-full object-cover"
                                src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                        </div>
                    @endif

                    <div>
                        <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}
                        </div>
                        <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <!-- Account Management -->
                    <x-navigation.responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                        {{ __('Profile') }}
                    </x-navigation.responsive-nav-link>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <x-navigation.responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">
                            {{ __('API Tokens') }}
                        </x-navigation.responsive-nav-link>
                    @endif

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf

                        <x-navigation.responsive-nav-link href="{{ route('logout') }}"
                            @click.prevent="$root.submit();">
                            {{ __('Log Out') }}
                        </x-navigation.responsive-nav-link>
                    </form>
                </div>
            </div>
        @endif
    </div>
</nav>
