<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight">
                    {{ __('KPI Settings & Weightings') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Manage Key Performance Indicators for employee appraisals. Ensure total active weight equals 100%.') }}
                </p>
            </div>
            <div>
                <x-button wire:click="create" class="flex items-center gap-2">
                    <x-heroicon-m-plus class="h-4 w-4" />
                    {{ __('Add KPI') }}
                </x-button>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-m-check-circle class="h-5 w-5 text-green-400" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-300">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
        <div class="mb-6 rounded-md p-4 {{ $totalWeight === 100 ? 'bg-blue-50 dark:bg-blue-900/30' : 'bg-red-50 dark:bg-red-900/30' }}">
            <div class="flex items-center">
                <x-heroicon-m-scale class="h-5 w-5 {{ $totalWeight === 100 ? 'text-blue-400' : 'text-red-400' }} mr-3" />
                <p class="text-sm font-medium {{ $totalWeight === 100 ? 'text-blue-800 dark:text-blue-300' : 'text-red-800 dark:text-red-300' }}">
                    {{ __('Total Active Weight:') }} {{ $totalWeight }}% 
                    @if($totalWeight !== 100)
                        <span class="ml-2 font-bold">{{ __('⚠️ Warning: Active KPIs must sum exactly to 100% for balanced appraisal scores.') }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('KPI Name') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Weight (%)') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse ($kpis as $kpi)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $kpi->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                {{ $kpi->weight }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleActive({{ $kpi->id }})" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $kpi->is_active ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $kpi->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                                <span class="ml-2 text-xs {{ $kpi->is_active ? 'text-green-600' : 'text-gray-500' }}">{{ $kpi->is_active ? __('Active') : __('Inactive') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $kpi->id }})" class="text-gray-400 hover:text-blue-600 transition-colors" title="{{ __('Edit') }}">
                                        <x-heroicon-m-pencil-square class="h-4 w-4" />
                                    </button>
                                    <button wire:click="delete({{ $kpi->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Delete') }}">
                                        <x-heroicon-m-trash class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No KPIs defined. Please add at least one KPI.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- KPI Modal -->
    <x-dialog-modal wire:model.live="showModal">
        <x-slot name="title">
            {{ $editId ? __('Edit KPI Template') : __('Add KPI Template') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="name" value="{{ __('KPI Name') }}" />
                    <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name" placeholder="{{ __('e.g., Target Achievement') }}" />
                    <x-input-error for="name" class="mt-2" />
                </div>
                
                <div>
                    <x-label for="weight" value="{{ __('Weight Percentage (1-100)') }}" />
                    <x-input id="weight" type="number" class="mt-1 block w-full font-mono" wire:model="weight" min="1" max="100" />
                    <x-input-error for="weight" class="mt-2" />
                    <p class="text-xs text-gray-500 mt-1">{{ __('This indicates how much this category impacts the final appraisal score.') }}</p>
                </div>
                
                <div class="flex items-center mt-4">
                    <input type="checkbox" id="is_active" wire:model="is_active" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded dark:bg-gray-900 dark:border-gray-700">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                        {{ __('Active (Will be used in the next appraisal cycle)') }}
                    </label>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ml-2" wire:click="save">
                {{ __('Save KPI') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Period Lock Card -->
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-10">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">🔒 {{ __('Appraisal Period Lock') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Control when employees and managers can submit appraisals. Close the window to prevent late submissions.') }}</p>
                    </div>
                    <button wire:click="togglePeriodLock" class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $periodOpen ? 'bg-green-500' : 'bg-red-500' }}">
                        <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $periodOpen ? 'translate-x-7' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <div class="rounded-lg p-4 mb-4 {{ $periodOpen ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                    <div class="flex items-center gap-2">
                        @if($periodOpen)
                            <x-heroicon-m-lock-open class="h-5 w-5 text-green-600" />
                            <span class="text-sm font-bold text-green-700 dark:text-green-400">{{ __('Window is OPEN') }} — {{ __('Employees and managers can submit appraisals.') }}</span>
                        @else
                            <x-heroicon-m-lock-closed class="h-5 w-5 text-red-600" />
                            <span class="text-sm font-bold text-red-700 dark:text-red-400">{{ __('Window is CLOSED') }} — {{ __('Submissions are locked. No new assessments can be created.') }}</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="periodLabel" value="{{ __('Period Label') }}" />
                        <x-input id="periodLabel" type="text" class="mt-1 block w-full" wire:model="periodLabel" placeholder="{{ __('e.g., Q1 2026, Semester 1 2026') }}" />
                        <x-input-error for="periodLabel" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="periodDeadline" value="{{ __('Submission Deadline') }}" />
                        <x-input id="periodDeadline" type="date" class="mt-1 block w-full" wire:model="periodDeadline" />
                        <x-input-error for="periodDeadline" class="mt-2" />
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <x-button wire:click="savePeriodLock">
                        {{ __('Save Period Settings') }}
                    </x-button>
                </div>
            </div>
        </div>
    </div>
</div>
