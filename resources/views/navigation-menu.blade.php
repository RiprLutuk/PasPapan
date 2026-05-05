{{-- <nav x-data="{ open: false }" class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800"> --}}
@php
    $isAdminRoute = request()->routeIs('admin.*');
    $isUserRoute = ! $isAdminRoute;
    $user = Auth::user();
    $isAdminUser = $user?->can('accessAdminPanel') ?? false;
    $homeHref = $user?->preferredHomeUrl() ?? route('home');
    $homeLabel = $isAdminUser ? __('Go to admin home') : __('Go to home');
    $reportingLocked = \App\Helpers\Editions::reportingLocked();
    $payrollLocked = \App\Helpers\Editions::payrollLocked();
    $cashAdvanceLocked = \App\Helpers\Editions::cashAdvanceLocked();
    $analyticsLocked = \App\Helpers\Editions::analyticsLocked();
    $appraisalLocked = \App\Helpers\Editions::appraisalLocked();
    $assetLocked = \App\Helpers\Editions::assetLocked();
    $documentRequestsLocked = false;
    $canReviewSubordinateRequests = $user?->can('reviewSubordinateRequests') ?? false;
    $isRouteActive = fn ($patterns) => request()->routeIs(...(array) $patterns);
    $can = fn (string $ability, mixed $arguments = []) => $user?->can($ability, $arguments) ?? false;
    $allowsAdminPermission = fn (string|array $permissions) => $user?->allowsAdminPermission($permissions) ?? false;

    $adminMenu = [
        [
            'type' => 'link',
            'label' => __('Dashboard'),
            'href' => route('admin.dashboard'),
            'active' => $isRouteActive('admin.dashboard'),
            'visible' => $can('viewAdminDashboard'),
        ],
        [
            'type' => 'group',
            'id' => 'attendance',
            'label' => __('Attendance'),
            'active' => $isRouteActive(['admin.attendances', 'admin.attendance-corrections', 'admin.leaves', 'admin.shift-swaps', 'admin.overtime', 'admin.analytics', 'admin.schedules', 'admin.holidays', 'admin.announcements']),
            'items' => [
                ['type' => 'heading', 'label' => __('Manage Attendance')],
                ['type' => 'link', 'label' => __('Daily Attendance'), 'href' => route('admin.attendances'), 'active' => $isRouteActive('admin.attendances'), 'visible' => $can('viewAdminAny', \App\Models\Attendance::class)],
                ['type' => 'link', 'label' => __('Corrections'), 'href' => route('admin.attendance-corrections'), 'active' => $isRouteActive('admin.attendance-corrections'), 'visible' => $can('viewAdminAny', \App\Models\AttendanceCorrection::class)],
                ['type' => 'link', 'label' => __('Approvals'), 'href' => route('admin.leaves'), 'active' => $isRouteActive('admin.leaves'), 'visible' => $can('manageLeaveApprovals')],
                ['type' => 'link', 'label' => __('Shift Swap Approvals'), 'href' => route('admin.shift-swaps'), 'active' => $isRouteActive('admin.shift-swaps'), 'visible' => $can('manageShiftSwapApprovals')],
                ['type' => 'link', 'label' => __('Overtime'), 'href' => route('admin.overtime'), 'active' => $isRouteActive('admin.overtime'), 'visible' => $can('manageOvertime')],
                ['type' => 'link', 'label' => __('Schedules (Roster)'), 'href' => route('admin.schedules'), 'active' => $isRouteActive('admin.schedules'), 'visible' => $can('manageSchedules')],
                ['type' => 'divider'],
                [
                    'type' => 'feature',
                    'label' => __('Analytics'),
                    'href' => route('admin.analytics'),
                    'active' => $isRouteActive('admin.analytics'),
                    'locked' => $analyticsLocked,
                    'lockTitle' => __('Analytics Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.analytics.view'),
                ],
                ['type' => 'divider'],
                ['type' => 'link', 'label' => __('Holidays'), 'href' => route('admin.holidays'), 'active' => $isRouteActive('admin.holidays'), 'visible' => $can('manageHolidays')],
                ['type' => 'link', 'label' => __('Announcements'), 'href' => route('admin.announcements'), 'active' => $isRouteActive('admin.announcements'), 'visible' => $can('manageAnnouncements')],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'finance',
            'label' => __('Finance'),
            'active' => $isRouteActive(['admin.payrolls', 'admin.payroll.settings', 'admin.reimbursements', 'admin.manage-kasbon']),
            'items' => [
                ['type' => 'heading', 'label' => __('Financial Management')],
                [
                    'type' => 'feature',
                    'label' => __('Payroll'),
                    'href' => route('admin.payrolls'),
                    'active' => $isRouteActive('admin.payrolls'),
                    'locked' => $payrollLocked,
                    'lockTitle' => __('Payroll Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.payroll.view'),
                ],
                ['type' => 'link', 'label' => __('Reimbursements'), 'href' => route('admin.reimbursements'), 'active' => $isRouteActive('admin.reimbursements'), 'visible' => $can('viewAdminAny', \App\Models\Reimbursement::class)],
                [
                    'type' => 'feature',
                    'label' => __('Manage Kasbon'),
                    'href' => route('admin.manage-kasbon'),
                    'active' => $isRouteActive('admin.manage-kasbon'),
                    'locked' => $cashAdvanceLocked,
                    'lockTitle' => __('Kasbon Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.cash_advances.manage'),
                ],
                ['type' => 'divider'],
                [
                    'type' => 'feature',
                    'label' => __('Payroll Settings'),
                    'href' => route('admin.payroll.settings'),
                    'active' => $isRouteActive('admin.payroll.settings'),
                    'locked' => $payrollLocked,
                    'lockTitle' => __('Settings Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.payroll_settings.manage'),
                ],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'master-data',
            'label' => __('Master Data'),
            'active' => $isRouteActive(['admin.masters.*', 'admin.employees', 'admin.hr-checklists', 'admin.document-requests', 'admin.document-templates', 'admin.document-templates.*', 'admin.barcodes', 'admin.barcodes.*', 'admin.appraisals', 'admin.assets']),
            'items' => [
                ['type' => 'heading', 'label' => __('Organization')],
                ['type' => 'link', 'label' => __('Employees'), 'href' => route('admin.employees'), 'active' => $isRouteActive('admin.employees'), 'visible' => $can('viewEmployees')],
                ['type' => 'link', 'label' => __('HR Checklists'), 'href' => route('admin.hr-checklists'), 'active' => $isRouteActive('admin.hr-checklists'), 'visible' => $can('viewHrChecklists')],
                [
                    'type' => 'feature',
                    'label' => __('Document Requests'),
                    'href' => route('admin.document-requests'),
                    'active' => $isRouteActive('admin.document-requests'),
                    'locked' => $documentRequestsLocked,
                    'lockTitle' => __('Documents Locked'),
                    'lockMessage' => __('Document Workflow is an Enterprise Feature. Please Upgrade.'),
                    'visible' => $user?->allowsAdminPermission('admin.document_requests.view') ?? false,
                ],
                [
                    'type' => 'feature',
                    'label' => __('Document Templates'),
                    'href' => route('admin.document-templates'),
                    'active' => $isRouteActive(['admin.document-templates', 'admin.document-templates.*']),
                    'locked' => $documentRequestsLocked,
                    'lockTitle' => __('Documents Locked'),
                    'lockMessage' => __('Document Workflow is an Enterprise Feature. Please Upgrade.'),
                    'visible' => ($user?->allowsAdminPermission('admin.document_requests.templates') ?? false)
                        || ($user?->allowsAdminPermission('admin.document_requests.generate') ?? false)
                        || ($user?->allowsAdminPermission('admin.document_requests.fulfill') ?? false)
                        || ($user?->allowsAdminPermission('admin.settings.manage') ?? false),
                ],
                [
                    'type' => 'feature',
                    'label' => __('Performance Appraisals'),
                    'href' => route('admin.appraisals'),
                    'active' => $isRouteActive('admin.appraisals'),
                    'locked' => $appraisalLocked,
                    'lockTitle' => __('Appraisals Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.appraisals.view'),
                ],
                [
                    'type' => 'feature',
                    'label' => __('Company Assets'),
                    'href' => route('admin.assets'),
                    'active' => $isRouteActive('admin.assets'),
                    'locked' => $assetLocked,
                    'lockTitle' => __('Asset Management Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.assets.view'),
                ],
                ['type' => 'link', 'label' => __('Barcode Locations'), 'href' => route('admin.barcodes'), 'active' => $isRouteActive(['admin.barcodes', 'admin.barcodes.*']), 'visible' => $can('manageBarcodes')],
                ['type' => 'divider'],
                ['type' => 'heading', 'label' => __('Reference')],
                ['type' => 'link', 'label' => __('Divisions'), 'href' => route('admin.masters.division'), 'active' => $isRouteActive('admin.masters.division'), 'visible' => $can('manageDivisions')],
                ['type' => 'link', 'label' => __('Job Titles'), 'href' => route('admin.masters.job-title'), 'active' => $isRouteActive('admin.masters.job-title'), 'visible' => $can('manageJobTitles')],
                ['type' => 'link', 'label' => __('Education Levels'), 'href' => route('admin.masters.education'), 'active' => $isRouteActive('admin.masters.education'), 'visible' => $can('manageEducations')],
                ['type' => 'link', 'label' => __('Shifts'), 'href' => route('admin.masters.shift'), 'active' => $isRouteActive('admin.masters.shift'), 'visible' => $can('manageShifts')],
                ['type' => 'link', 'label' => __('Leave Types'), 'href' => route('admin.masters.leave-types'), 'active' => $isRouteActive('admin.masters.leave-types'), 'visible' => $can('manageLeaveTypes')],
                ['type' => 'link', 'label' => __('Administrators'), 'href' => route('admin.masters.admin'), 'active' => $isRouteActive('admin.masters.admin'), 'visible' => $can('viewAdminAccounts')],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'system',
            'label' => __('System'),
            'active' => $isRouteActive(['admin.settings', 'admin.settings.kpi', 'admin.system-maintenance', 'admin.reports.*', 'admin.import-export.*', 'admin.activity-logs', 'admin.roles.permissions']),
            'items' => array_values(array_filter([
                ['type' => 'link', 'label' => __('App Settings'), 'href' => route('admin.settings'), 'active' => $isRouteActive('admin.settings'), 'visible' => $can('viewAdminSettings')],
                [
                    'type' => 'feature',
                    'label' => __('KPI Settings'),
                    'href' => route('admin.settings.kpi'),
                    'active' => $isRouteActive('admin.settings.kpi'),
                    'locked' => $appraisalLocked,
                    'lockTitle' => __('KPI Settings Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.kpi_settings.manage'),
                ],
                $can('viewAny', \App\Models\SystemBackupRun::class)
                    ? ['type' => 'link', 'label' => __('Maintenance'), 'href' => route('admin.system-maintenance'), 'active' => $isRouteActive('admin.system-maintenance')]
                    : null,
                ['type' => 'divider'],
                ['type' => 'heading', 'label' => __('Data Management')],
                ['type' => 'link', 'label' => __('Reports'), 'href' => route('admin.reports.index'), 'active' => $isRouteActive('admin.reports.*'), 'visible' => $can('viewOperationalReports')],
                ['type' => 'link', 'label' => __('Activity Logs'), 'href' => route('admin.activity-logs'), 'active' => $isRouteActive('admin.activity-logs'), 'visible' => $can('viewActivityLogs')],
                [
                    'type' => 'feature',
                    'label' => __('Import/Export Users'),
                    'href' => route('admin.import-export.users'),
                    'active' => $isRouteActive('admin.import-export.users'),
                    'locked' => $reportingLocked,
                    'lockTitle' => __('Import/Export Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.import_export_users.view'),
                ],
                [
                    'type' => 'feature',
                    'label' => __('Import/Export Attendance'),
                    'href' => route('admin.import-export.attendances'),
                    'active' => $isRouteActive('admin.import-export.attendances'),
                    'locked' => $reportingLocked,
                    'lockTitle' => __('Import/Export Locked'),
                    'lockMessage' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
                    'visible' => $allowsAdminPermission('admin.import_export_attendances.view'),
                ],
                ['type' => 'link', 'label' => __('Roles & Permissions'), 'href' => route('admin.roles.permissions'), 'active' => $isRouteActive('admin.roles.permissions'), 'visible' => $can('manageRbac')],
            ])),
        ],
    ];

    $adminMenu = array_values(array_filter(array_map(function (array $menuItem) {
        if (($menuItem['visible'] ?? true) === false) {
            return null;
        }

        if (($menuItem['type'] ?? 'link') !== 'group') {
            return $menuItem;
        }

        $items = [];

        foreach ($menuItem['items'] as $item) {
            if (($item['visible'] ?? true) === false) {
                continue;
            }

            if (($item['type'] ?? 'link') === 'divider') {
                if (! empty($items) && ($items[array_key_last($items)]['type'] ?? null) !== 'divider') {
                    $items[] = $item;
                }

                continue;
            }

            $items[] = $item;
        }

        while (! empty($items) && in_array($items[array_key_last($items)]['type'] ?? 'link', ['divider', 'heading'], true)) {
            array_pop($items);
        }

        while (! empty($items) && ($items[0]['type'] ?? 'link') === 'divider') {
            array_shift($items);
        }

        $hasInteractiveItems = collect($items)->contains(fn (array $item) => in_array($item['type'] ?? 'link', ['link', 'feature', 'button'], true));

        if (! $hasInteractiveItems) {
            return null;
        }

        $menuItem['items'] = $items;

        return $menuItem;
    }, $adminMenu)));
@endphp

<nav x-data="{ open: false }" @keydown.escape.window="open = false" aria-label="{{ $isAdminRoute ? __('Primary navigation') : __('User navigation') }}"
    data-app-top-nav
    class="fixed top-0 left-0 z-50 w-full border-b border-gray-200/80 bg-white/95 backdrop-blur-sm dark:border-gray-700 dark:bg-gray-800/95 pt-[env(safe-area-inset-top)]">
    <!-- Primary Navigation Menu -->
    <div
        class="{{ $isAdminRoute ? 'w-full px-4 sm:px-6 lg:px-8 2xl:px-10' : 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8' }}">
        <div class="flex {{ $isAdminRoute ? 'h-16' : 'h-14 sm:h-[4.25rem]' }} justify-between gap-3">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ $homeHref }}"
                        class="rounded-xl p-1 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2 dark:hover:bg-gray-800 dark:focus-visible:ring-primary-300 dark:focus-visible:ring-offset-gray-900"
                        aria-label="{{ $homeLabel }}">
                        <x-branding.application-mark
                            class="block {{ $isAdminRoute ? 'h-9 w-auto' : 'h-10 w-10 sm:h-11 sm:w-11' }}" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-2 sm:-my-px sm:ms-6 sm:flex md:ms-10 md:space-x-5 lg:space-x-8">
                    @if ($isAdminUser)
                        @foreach ($adminMenu as $menuItem)
                            @if ($menuItem['type'] === 'link')
                                <x-navigation.nav-link href="{{ $menuItem['href'] }}" :active="$menuItem['active']" wire:navigate>
                                    {{ $menuItem['label'] }}
                                </x-navigation.nav-link>
                            @else
                                <x-navigation.nav-dropdown id="desktop-admin-{{ $menuItem['id'] }}" :active="$menuItem['active']" triggerClasses="text-nowrap">
                                    <x-slot name="trigger">
                                        {{ $menuItem['label'] }}
                                        <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-500 dark:text-gray-300" />
                                    </x-slot>
                                    <x-slot name="content">
                                        @foreach ($menuItem['items'] as $navItem)
                                            @if ($navItem['type'] === 'heading')
                                                <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                                    {{ $navItem['label'] }}
                                                </div>
                                            @elseif ($navItem['type'] === 'divider')
                                                <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                            @elseif (($navItem['type'] ?? 'link') === 'feature' && $navItem['locked'])
                                                <button
                                                    type="button"
                                                    @click.prevent="$dispatch('feature-lock', { title: @js($navItem['lockTitle']), message: @js($navItem['lockMessage']) })"
                                                    class="wcag-touch-target block w-full rounded-md px-4 py-2.5 text-start text-sm leading-5 text-gray-800 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-950 dark:text-gray-100 dark:hover:bg-gray-700 dark:hover:text-white"
                                                    aria-label="{{ $navItem['label'] }}. {{ __('Locked feature') }}">
                                                    <span>{{ $navItem['label'] }}</span>
                                                    <x-heroicon-o-lock-closed class="ms-1 inline h-4 w-4" />
                                                </button>
                                            @else
                                                <x-navigation.dropdown-link href="{{ $navItem['href'] }}" :active="$navItem['active']" wire:navigate>
                                                    {{ $navItem['label'] }}
                                                </x-navigation.dropdown-link>
                                            @endif
                                        @endforeach
                                    </x-slot>
                                </x-navigation.nav-dropdown>
                            @endif
                        @endforeach
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
                    </div>

                    <!-- Settings Dropdown -->
                    @if ($user && $isAdminRoute)
                        <div class="relative">
                            <x-navigation.dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                        <button
                                            type="button"
                                            class="wcag-touch-target flex items-center justify-center rounded-full border-2 border-transparent text-sm transition hover:border-gray-300 focus:outline-none dark:hover:border-gray-600"
                                            aria-label="{{ __('Open account menu') }}">
                                            <img class="h-8 w-8 rounded-full object-cover"
                                                src="{{ $user->profile_photo_url }}"
                                                alt="{{ $user->name }}" />
                                        </button>
                                    @else
                                        <span class="inline-flex rounded-md">
                                            <button type="button"
                                                title="{{ __('Open account menu') }}"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:bg-gray-50 focus:outline-none active:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300 dark:focus:bg-gray-700 dark:active:bg-gray-700">
                                                {{ $user->name }}

                                                <x-heroicon-o-chevron-down class="-me-0.5 ms-2 h-4 w-4" />
                                            </button>
                                        </span>
                                    @endif
                                </x-slot>

                                <x-slot name="content">
                                    <!-- Account Management -->
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        {{ __('Manage Account') }}
                                    </div>

                                    <x-navigation.dropdown-link href="{{ route($isAdminRoute ? 'admin.profile.show' : 'profile.show') }}">
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
                </div>

                <!-- Hamburger -->
                @if ($user && $isAdminRoute)
                    <div class="-me-2 flex items-center sm:hidden">
                        <button type="button" @click="open = ! open"
                            class="wcag-touch-target inline-flex items-center justify-center rounded-md p-2 text-gray-600 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-white"
                            :aria-expanded="open.toString()" aria-controls="mobile-navigation"
                            aria-label="{{ __('Toggle navigation menu') }}">
                            <x-heroicon-o-bars-3 x-show="!open" class="h-6 w-6" />
                            <x-heroicon-o-x-mark x-show="open" class="h-6 w-6" />
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    @if ($isAdminRoute)
    <div id="mobile-navigation" :class="{ 'block': open, 'hidden': !open }"
        class="sm:hidden overflow-y-auto max-h-[calc(100vh-4rem)]">
        <div class="space-y-1 pb-3 pt-2">
            @if ($isAdminUser)
                @foreach ($adminMenu as $menuItem)
                    @if ($menuItem['type'] === 'link')
                        <x-navigation.responsive-nav-link href="{{ $menuItem['href'] }}" :active="$menuItem['active']" wire:navigate>
                            {{ $menuItem['label'] }}
                        </x-navigation.responsive-nav-link>
                    @else
                        <div
                            x-data="{ expanded: {{ $menuItem['active'] ? 'true' : 'false' }} }"
                            class="border-t border-gray-200 dark:border-gray-700">
                            <button
                                type="button"
                                @click="expanded = !expanded"
                                class="wcag-touch-target flex w-full items-center justify-between px-4 py-3 text-left text-sm font-semibold text-gray-800 transition-colors hover:bg-gray-50 hover:text-gray-950 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white"
                                title="{{ __('Toggle menu section') }}"
                                :aria-expanded="expanded.toString()"
                                aria-controls="mobile-admin-group-{{ $menuItem['id'] }}">
                                <span>{{ $menuItem['label'] }}</span>
                                <x-heroicon-o-chevron-down
                                    class="h-4 w-4 transform transition-transform duration-200"
                                    x-bind:class="{ 'rotate-180': expanded }"
                                    />
                            </button>

                            <div
                                id="mobile-admin-group-{{ $menuItem['id'] }}"
                                x-show="expanded"
                                style="display: none;"
                                class="bg-gray-50/80 pb-2 dark:bg-gray-950/30">
                                @foreach ($menuItem['items'] as $navItem)
                                    @if ($navItem['type'] === 'heading')
                                        <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                            {{ $navItem['label'] }}
                                        </div>
                                    @elseif ($navItem['type'] === 'divider')
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                    @elseif (($navItem['type'] ?? 'link') === 'feature' && $navItem['locked'])
                                        <button
                                            type="button"
                                            @click.prevent="$dispatch('feature-lock', { title: @js($navItem['lockTitle']), message: @js($navItem['lockMessage']) })"
                                            class="wcag-touch-target block w-full border-l-4 border-transparent py-2.5 pe-4 ps-3 text-start text-base font-medium text-gray-700 transition duration-150 ease-in-out hover:border-gray-400 hover:bg-gray-100 hover:text-gray-950 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:bg-gray-700 dark:hover:text-white"
                                            aria-label="{{ $navItem['label'] }}. {{ __('Locked feature') }}">
                                            <span>{{ $navItem['label'] }}</span>
                                            <x-heroicon-o-lock-closed class="ms-1 inline h-4 w-4" />
                                        </button>
                                    @else
                                        <x-navigation.responsive-nav-link href="{{ $navItem['href'] }}" :active="$navItem['active']" wire:navigate>
                                            {{ $navItem['label'] }}
                                        </x-navigation.responsive-nav-link>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @else
                <x-navigation.responsive-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')" wire:navigate>
                    {{ __('Home') }}
                </x-navigation.responsive-nav-link>

                @if ($canReviewSubordinateRequests)
                    <x-navigation.responsive-nav-link href="{{ route('approvals') }}" :active="request()->routeIs('approvals')"
                        wire:navigate>
                        {{ __('Team Approvals') }}
                    </x-navigation.responsive-nav-link>
                @endif
            @endif
        </div>

        <!-- Responsive Settings Options -->
        @if ($user)
            <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
                <div class="flex items-center px-4">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <div class="me-3 shrink-0">
                            <img class="h-10 w-10 rounded-full object-cover"
                                src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" />
                        </div>
                    @endif

                    <div>
                        <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ $user->name }}
                        </div>
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $user->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <!-- Account Management -->
                    <x-navigation.responsive-nav-link href="{{ route($isAdminRoute ? 'admin.profile.show' : 'profile.show') }}" :active="request()->routeIs($isAdminRoute ? 'admin.profile.show' : 'profile.show')">
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
    @endif
</nav>
