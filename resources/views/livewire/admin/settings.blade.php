<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Application Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg">
                <div class="p-4 lg:p-6">
                    
                    <div class="mb-4 flex-col items-center gap-5 sm:flex-row md:flex md:justify-between lg:mr-4">
                        <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200 md:mb-0">
                            {{ __('Generic Settings') }}
                        </h3>
                    </div>

                    <div class="space-y-6">
                        @foreach($groups as $group => $settings)
                            <div class="p-4 sm:p-8 bg-gray-50 dark:bg-gray-700/50 shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                                <header class="mb-6 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 uppercase tracking-wider">
                                        {{ $group }}
                                    </h2>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('Manage configuration for') }} {{ $group }}
                                    </p>
                                </header>
                                
                                <div class="grid gap-6">
                                    @foreach($settings as $setting)
                                        <div class="grid gap-2" wire:key="setting-{{ $setting->id }}">
                                            <div class="flex items-center justify-between">
                                                <x-label :for="'setting_' . $this->getId() . '_' . $setting->id" :value="$setting->description ?? $setting->key" />
                                                <span class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $setting->key }}</span>
                                            </div>
                                            
                                            <div class="relative">
                                                @if($setting->type === 'boolean')
                                                    <!-- Boolean Toggle -->
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" 
                                                            id="'setting_' . $this->getId() . '_' . $setting->id" 
                                                            class="sr-only peer"
                                                            @checked($setting->value == '1')
                                                            wire:change="updateValue({{ $setting->id }}, $event.target.checked)"
                                                            {{ !auth()->user()->isSuperadmin ? 'disabled' : '' }}
                                                        >
                                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                            {{ $setting->value == '1' ? __('Enabled') : __('Disabled') }}
                                                        </span>
                                                    </label>
                                                @elseif($setting->type === 'select' && $setting->key === 'app.time_format')
                                                    <!-- Time Format Select -->
                                                    <select 
                                                        id="setting_{{ $this->getId() }}_{{ $setting->id }}"
                                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                                        wire:change="updateValue({{ $setting->id }}, $event.target.value)"
                                                        {{ !auth()->user()->isSuperadmin ? 'disabled' : '' }}
                                                    >
                                                        <option value="24" @selected($setting->value == '24')>{{ __('24 Hour (17:00)') }}</option>
                                                        <option value="12" @selected($setting->value == '12')>{{ __('12 Hour (05:00 PM)') }}</option>
                                                    </select>
                                                @else
                                                    <!-- Text/Number Input -->
                                                    <x-input 
                                                        :id="'setting_' . $this->getId() . '_' . $setting->id" 
                                                        :type="$setting->type === 'number' ? 'number' : 'text'" 
                                                        class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-700 dark:disabled:text-gray-400" 
                                                        :value="$setting->value"
                                                        autocomplete="off"
                                                        wire:change="updateValue({{ $setting->id }}, $event.target.value)"
                                                        :disabled="!auth()->user()->isSuperadmin"
                                                    />
                                                @endif

                                                <div class="mt-1 flex justify-end">
                                                    <span class="text-xs text-blue-500" wire:loading wire:target="updateValue({{ $setting->id }})">
                                                        {{ __('Saving...') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
