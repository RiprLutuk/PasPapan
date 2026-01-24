<div class="py-12" x-data="{ 
    activeTab: 'app', 
    tabs: [
        { id: 'app', label: '{{ __('General') }}', icon: 'home' },
        { id: 'attendance', label: '{{ __('Attendance') }}', icon: 'clock' },
        { id: 'security', label: '{{ __('Security') }}', icon: 'shield-check' },
        { id: 'leave', label: '{{ __('Leave & Time Off') }}', icon: 'calendar' },
        { id: 'notif', label: '{{ __('Notifications') }}', icon: 'bell' },
        { id: 'enterprise', label: '{{ __('Enterprise') }}', icon: 'briefcase' }
    ],
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
}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ __('Application Settings') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Manage your application configuration and preferences.') }}
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Navigation -->
            <aside class="w-full lg:w-64 flex-shrink-0">
                <nav class="space-y-1">
                    <template x-for="tab in tabs" :key="tab.id">
                        <button 
                            @click="activeTab = tab.id"
                            :class="{ 
                                'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400 ring-1 ring-gray-900/5 dark:ring-gray-700': activeTab === tab.id, 
                                'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200': activeTab !== tab.id 
                            }"
                            class="group w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200"
                        >
                            <!-- Icons (Using SVG directly for reliability) -->
                            <span :class="activeTab === tab.id ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300'">
                                <!-- Home/App -->
                                <svg x-show="tab.icon === 'home'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                <!-- Clock/Attendance -->
                                <svg x-show="tab.icon === 'clock'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- Shield/Security -->
                                <svg x-show="tab.icon === 'shield-check'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" /></svg>
                                <!-- Calendar/Leave -->
                                <svg x-show="tab.icon === 'calendar'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                <!-- Bell/Notif -->
                                <svg x-show="tab.icon === 'bell'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                                <!-- Briefcase/Enterprise -->
                                <svg x-show="tab.icon === 'briefcase'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" /></svg>
                            </span>
                            <span x-text="tab.label"></span>
                        </button>
                    </template>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 min-w-0">
                @foreach($groups as $group => $settings)
                    @php
                        // Map database groups to UI tabs
                        $tabMap = [
                            'general'      => 'app',
                            'system'       => 'app',
                            'identity'     => 'app',
                            'features'     => 'app',
                            'attendance'   => 'attendance',
                            'security'     => 'security',
                            'leave'        => 'leave',
                            'notification' => 'notif',
                            'payroll'      => null, // Exclude Payroll/Finance from this view
                            'enterprise'   => 'enterprise',
                        ];
                        $targetTab = $tabMap[$group] ?? null; 
                    @endphp
                    
                    @if($targetTab)
                    <div x-show="activeTab === '{{ $targetTab }}'" 
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 transition-all duration-300 relative mb-6"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    >
                        
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/20 rounded-t-xl">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white capitalize">
                                    {{ $group }} {{ __('Settings') }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">{{ __('Configure compliance and preferences for') }} {{ $group }}</p>
                            </div>
                            
                            @if($group === 'enterprise' && isset($licenseInfo))
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $licenseInfo['client'] ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Expires: <span class="{{ \Carbon\Carbon::parse($licenseInfo['expires_at'])->isPast() ? 'text-red-600' : 'text-green-600' }}">
                                                {{ \Carbon\Carbon::parse($licenseInfo['expires_at'])->format('d M Y') }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                         <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="p-6 space-y-8">
                            @foreach($settings as $setting)
                                <div wire:key="setting-{{ $setting->id }}" class="group">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                        <div class="flex-1">
                                            <x-label :for="'setting_' . $setting->id" :value="$setting->description ?? $setting->key" class="text-base font-medium text-gray-800 dark:text-gray-200" />
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs font-mono text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded select-all">{{ $setting->key }}</span>
                                                <div class="h-4 w-4" wire:loading wire:target="updateValue({{ $setting->id }})">
                                                    <svg class="animate-spin h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex-shrink-0">
                                            @if($setting->type === 'boolean')
                                                <button 
                                                    type="button" 
                                                    wire:click="updateValue({{ $setting->id }}, {{ $setting->value == '1' ? '0' : '1' }})" 
                                                    class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 {{ $setting->value == '1' ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                                    {{ !auth()->user()->isSuperadmin ? 'disabled' : '' }}
                                                >
                                                    <span class="sr-only">{{ $setting->description }}</span>
                                                    <span aria-hidden="true" class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $setting->value == '1' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                                </button>
                                            
                                            @elseif($setting->type === 'select' && $setting->key === 'app.time_format')
                                                <x-select 
                                                    wire:change="updateValue({{ $setting->id }}, $event.target.value)"
                                                    :disabled="!auth()->user()->isSuperadmin"
                                                    class="block w-auto min-w-[11rem]"
                                                >
                                                    <option value="24" @selected($setting->value == '24')>24 Hour (17:00)</option>
                                                    <option value="12" @selected($setting->value == '12')>12 Hour (05:00 PM)</option>
                                                </x-select>

                                            @elseif($setting->type === 'textarea')
                                                 <x-textarea
                                                    wire:change.debounce.500ms="updateValue({{ $setting->id }}, $event.target.value)"
                                                    rows="3"
                                                    :disabled="!auth()->user()->isSuperadmin"
                                                    class="block w-full min-w-[300px]"
                                                >{{ $setting->value }}</x-textarea>

                                            @else
                                                <x-input 
                                                    type="{{ $setting->type === 'number' ? 'number' : 'text' }}" 
                                                    value="{{ $setting->value }}"
                                                    wire:change.debounce.500ms="updateValue({{ $setting->id }}, $event.target.value)"
                                                    :disabled="!auth()->user()->isSuperadmin"
                                                    class="block w-full min-w-[300px]"
                                                />
                                            @endif
                                        </div>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="border-t border-gray-100 dark:border-gray-700 mt-6"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

