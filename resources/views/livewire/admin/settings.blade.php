@php
    $tabMap = [
        'general' => 'app',
        'system' => 'app',
        'identity' => 'app',
        'features' => 'app',
        'attendance' => 'attendance',
        'security' => 'security',
        'leave' => 'leave',
        'notification' => 'notif',
        'payroll' => null,
        'enterprise' => 'enterprise',
    ];

    $tabCounts = [
        'app' => 0,
        'attendance' => 0,
        'security' => 0,
        'leave' => 0,
        'notif' => 0,
        'enterprise' => 0,
    ];

    foreach ($groups as $group => $settings) {
        $targetTab = $tabMap[$group] ?? null;

        if ($targetTab) {
            $tabCounts[$targetTab] += $settings->count();
        }
    }

    $isSuperadmin = auth()->user()->isSuperadmin;
    $licenseStatus = $licenseValidation ?? [
        'valid' => false,
        'code' => 'missing_key',
        'message' => __('No enterprise license key saved yet.'),
    ];
    $licenseStatusValid = $licenseStatus['valid'] ?? false;
    $licenseStatusCode = $licenseStatus['code'] ?? 'missing_key';
@endphp

<x-admin.page-shell :title="__('Application Settings')" :description="__('Manage your application configuration and preferences.')" x-data="{
    activeTab: 'app',
    search: '',
    tabs: [
        { id: 'app', label: '{{ __('General') }}', icon: 'home', count: {{ $tabCounts['app'] }} },
        { id: 'attendance', label: '{{ __('Attendance') }}', icon: 'clock', count: {{ $tabCounts['attendance'] }} },
        { id: 'security', label: '{{ __('Security') }}', icon: 'shield-check', count: {{ $tabCounts['security'] }} },
        { id: 'leave', label: '{{ __('Leave & Time Off') }}', icon: 'calendar', count: {{ $tabCounts['leave'] }} },
        { id: 'notif', label: '{{ __('Notifications') }}', icon: 'bell', count: {{ $tabCounts['notif'] }} },
        { id: 'enterprise', label: '{{ __('Enterprise') }}', icon: 'briefcase', count: {{ $tabCounts['enterprise'] }} }
    ],
    normalize(value) {
        return (value || '').toString().toLowerCase();
    },
    matchesSearch(value) {
        const query = this.normalize(this.search).trim();
        return query === '' || this.normalize(value).includes(query);
    },
    clearSearch() {
        this.search = '';
    },
    init() {
        // Initialize from URL hash
        const hash = window.location.hash.replace('#', '');
        if (hash && this.tabs.some(t => t.id === hash)) {
            this.activeTab = hash;
        }

        // Watch for changes to update URL hash
        this.$watch('activeTab', value => {
            window.location.hash = value;
        });

        // Handle browser back/forward buttons
        window.addEventListener('hashchange', () => {
            const newHash = window.location.hash.replace('#', '');
            if (newHash && this.tabs.some(t => t.id === newHash)) {
                this.activeTab = newHash;
            }
        });
    }
}"
    x-on:enterprise-license-applied.window="if ($event.detail.reload) window.location.reload()">
    <x-slot name="toolbar">
        <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 lg:grid-cols-4">

            <x-slot name="actions">
                <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $isSuperadmin ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300' }}">
                    {{ $isSuperadmin ? __('Auto-save enabled') : __('Read-only mode') }}
                </span>
            </x-slot>

            <div class="lg:col-span-4">
                <label for="settings-search" class="mb-1.5 block text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('Search settings') }}
                </label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <x-forms.input id="settings-search" x-model.debounce.200ms="search" type="text"
                        placeholder="{{ __('Search by setting name or key') }}"
                        class="w-full border-gray-200 bg-gray-50 py-2.5 pl-9 pr-10 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500" />
                    <button x-cloak x-show="search" type="button" @click="clearSearch()"
                        aria-label="{{ __('Clear settings search') }}"
                        class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:ring-offset-gray-900">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-64 flex-shrink-0 lg:sticky lg:top-24 lg:self-start">
            <nav class="space-y-1" role="tablist" aria-label="{{ __('Settings categories') }}">
                <template x-for="tab in tabs" :key="tab.id">
                    <button type="button" role="tab" @click="activeTab = tab.id" :id="'settings-tab-' + tab.id"
                        :aria-controls="'settings-panel-' + tab.id" :aria-selected="(activeTab === tab.id).toString()"
                        :tabindex="activeTab === tab.id ? 0 : -1"
                        :class="{
                            'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400 ring-1 ring-gray-900/5 dark:ring-gray-700': activeTab ===
                                tab.id,
                            'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200': activeTab !==
                                tab.id
                        }"
                        class="wcag-touch-target group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <!-- Icons (Using SVG directly for reliability) -->
                        <span
                            :class="activeTab === tab.id ? 'text-primary-600 dark:text-primary-400' :
                                'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300'">
                            <!-- Home/App -->
                            <svg x-show="tab.icon === 'home'" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <!-- Clock/Attendance -->
                            <svg x-show="tab.icon === 'clock'" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Shield/Security -->
                            <svg x-show="tab.icon === 'shield-check'" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                            </svg>
                            <!-- Calendar/Leave -->
                            <svg x-show="tab.icon === 'calendar'" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            <!-- Bell/Notif -->
                            <svg x-show="tab.icon === 'bell'" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <!-- Briefcase/Enterprise -->
                            <svg x-show="tab.icon === 'briefcase'" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                            </svg>
                        </span>
                        <span class="flex-1 text-left" x-text="tab.label"></span>
                        <span
                            class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                            x-text="tab.count"></span>
                    </button>
                </template>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 min-w-0">
            @foreach (array_keys($tabCounts) as $panelTab)
                <div x-show="activeTab === '{{ $panelTab }}'" id="settings-panel-{{ $panelTab }}"
                    role="tabpanel" aria-labelledby="settings-tab-{{ $panelTab }}" tabindex="0"
                    class="space-y-6 focus:outline-none" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    @foreach ($groups as $group => $settings)
                        @php
                            $targetTab = $tabMap[$group] ?? null;
                            $groupSearchIndex = $settings
                                ->map(
                                    fn($setting) => implode(
                                        ' ',
                                        array_filter([$group, $setting->description, $setting->key, $setting->value]),
                                    ),
                                )
                                ->implode(' ');
                        @endphp

                        @if ($targetTab === $panelTab)
                            <div data-search-index="{{ $groupSearchIndex }}"
                                x-show="matchesSearch($el.dataset.searchIndex)"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0">
                            <x-admin.panel class="relative mb-6 rounded-xl transition-all duration-300">

                                <div
                                    class="flex flex-col gap-4 border-b border-gray-100 bg-gray-50/50 px-6 py-5 dark:border-gray-700 dark:bg-gray-700/20 sm:flex-row sm:items-center sm:justify-between rounded-t-xl">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white capitalize">
                                            {{ $group }} {{ __('Settings') }}
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ __('Configure compliance and preferences for') }} {{ $group }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                        @unless ($isSuperadmin)
                                            <span
                                                class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                {{ __('Read-only mode') }}
                                            </span>
                                        @endunless
                                        @if ($group === 'enterprise')
                                            @if ($licenseStatusValid && isset($licenseInfo['expires_at']))
                                                <div
                                                    class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-white px-3 py-2 dark:border-emerald-900/40 dark:bg-gray-800">
                                                    <div class="text-right">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ __('License active') }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ __('Until') }}:
                                                            <span class="text-emerald-600 dark:text-emerald-300">
                                                                {{ \Carbon\Carbon::parse($licenseInfo['expires_at'])->format('d M Y') }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                                                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-300"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="@class([
                                                    'inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold',
                                                    'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' =>
                                                        $licenseStatusCode === 'missing_key',
                                                    'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-300' =>
                                                        $licenseStatusCode !== 'missing_key',
                                                ])">
                                                    {{ __('License status') }}
                                                </span>
                                            @endif
                                        @endif

                                    </div>
                                </div>

                                @if ($group === 'enterprise')
                                    <x-admin.alert tone="warning"
                                        class="rounded-none rounded-b-none border-x-0 border-t-0 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Server
                                                Hardware ID (HWID)</h4>
                                            <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-1">
                                                {{ __('Please give this code to Developer if you want to request Enterprise License for this server.') }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 w-full sm:w-auto">
                                            <code
                                                class="px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 text-sm rounded border border-yellow-200 dark:border-yellow-700 font-mono select-all w-full sm:w-auto text-center">{{ $hwid }}</code>
                                        </div>
                                    </x-admin.alert>
                                @endif

                                <div class="p-6 space-y-8">
                                    @foreach ($settings as $setting)
                                        @php
                                            $settingSearchIndex = implode(
                                                ' ',
                                                array_filter([
                                                    $group,
                                                    $setting->description,
                                                    $setting->key,
                                                    $setting->value,
                                                ]),
                                            );
                                        @endphp

                                        <div wire:key="setting-{{ $setting->id }}" class="group"
                                            data-search-index="{{ $settingSearchIndex }}"
                                            x-show="matchesSearch($el.dataset.searchIndex)">
                                            <div
                                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                                <div class="flex-1">
                                                    <x-forms.label :for="'setting_' . $setting->id" :value="$setting->description ?? $setting->key"
                                                        class="text-base font-medium text-gray-800 dark:text-gray-200" />
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span
                                                            class="text-xs font-mono text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded select-all">{{ $setting->key }}</span>
                                                        <div class="h-4 w-4" wire:loading
                                                            wire:target="updateValue({{ $setting->id }})">
                                                            <svg class="animate-spin h-4 w-4 text-primary-600"
                                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12"
                                                                    cy="12" r="10" stroke="currentColor"
                                                                    stroke-width="4">
                                                                </circle>
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex-shrink-0">
                                                    @if ($setting->key === 'enterprise_license_key')
                                                        <div class="w-full min-w-[300px] max-w-3xl space-y-4">

                                                            <x-forms.textarea wire:model.defer="enterpriseLicenseDraft"
                                                                rows="3" :disabled="!$isSuperadmin"
                                                                class="block w-full font-mono text-xs sm:text-sm"></x-forms.textarea>

                                                            <div class="flex items-center justify-end gap-3">
                                                                <div class="h-4 w-4" wire:loading
                                                                    wire:target="applyEnterpriseLicense">
                                                                    <svg class="h-4 w-4 animate-spin text-primary-600"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        fill="none" viewBox="0 0 24 24">
                                                                        <circle class="opacity-25" cx="12"
                                                                            cy="12" r="10"
                                                                            stroke="currentColor" stroke-width="4">
                                                                        </circle>
                                                                        <path class="opacity-75" fill="currentColor"
                                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                        </path>
                                                                    </svg>
                                                                </div>

                                                                @if ($isSuperadmin)
                                                                    <x-actions.button
                                                                        wire:click="applyEnterpriseLicense"
                                                                        wire:loading.attr="disabled"
                                                                        class="justify-center"
                                                                        wire:target="applyEnterpriseLicense">
                                                                        {{ __('Apply') }}
                                                                    </x-actions.button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @elseif ($setting->type === 'boolean')
                                                        <x-forms.switch
                                                            wire:click="updateValue({{ $setting->id }}, {{ $setting->value == '1' ? '0' : '1' }})"
                                                            :checked="$setting->value == '1'" :label="$setting->description ?? $setting->key" :disabled="!auth()->user()->isSuperadmin" />
                                                    @elseif($setting->type === 'select' && $setting->key === 'app.time_format')
                                                        <x-forms.select
                                                            wire:change="updateValue({{ $setting->id }}, $event.target.value)"
                                                            :disabled="!auth()->user()->isSuperadmin" class="block w-auto min-w-[11rem]">
                                                            <option value="24" @selected($setting->value == '24')>24 Hour
                                                                (17:00)
                                                            </option>
                                                            <option value="12" @selected($setting->value == '12')>12 Hour
                                                                (05:00 PM)</option>
                                                        </x-forms.select>
                                                    @elseif($setting->type === 'textarea')
                                                        <x-forms.textarea
                                                            wire:change.debounce.500ms="updateValue({{ $setting->id }}, $event.target.value)"
                                                            rows="3" :disabled="!auth()->user()->isSuperadmin"
                                                            class="block w-full min-w-[300px]">{{ $setting->value }}</x-forms.textarea>
                                                    @else
                                                        <x-forms.input
                                                            type="{{ $setting->type === 'number' ? 'number' : 'text' }}"
                                                            value="{{ $setting->value }}"
                                                            wire:change.debounce.500ms="updateValue({{ $setting->id }}, $event.target.value)"
                                                            :disabled="!auth()->user()->isSuperadmin" class="block w-full min-w-[300px]" />
                                                    @endif
                                                </div>
                                            </div>
                                            @if (!$loop->last)
                                                <div class="border-t border-gray-100 dark:border-gray-700 mt-6"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </x-admin.panel>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-admin.page-shell>
