<div class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10 2xl:px-10">
    <div class="w-full">
        <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
            <div>
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight">
                    {{ __('Weighting & KPI System') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Manage KPI Categories and Components for employee appraisals. Ensure total active category weight = 100%.') }}
                </p>
            </div>
            <div>
                <x-actions.button wire:click="createGroup" title="{{ __('Add Category') }}" aria-label="{{ __('Add Category') }}" class="h-10 w-10 justify-center !px-0 !py-0">
                    <x-heroicon-m-plus class="h-4 w-4" />
                </x-actions.button>
            </div>
        </div>

        {{-- Global Group Weight Indicator --}}
        <div class="mb-6 rounded-xl p-4 {{ $totalGroupWeight === 100 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
            <div class="flex items-center gap-3">
                <x-heroicon-m-scale class="h-5 w-5 {{ $totalGroupWeight === 100 ? 'text-green-500' : 'text-red-500' }}" />
                <p class="text-sm font-medium {{ $totalGroupWeight === 100 ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                    {{ __('Total Active Category Weight:') }} <span class="font-bold text-lg">{{ $totalGroupWeight }}%</span>
                    @if($totalGroupWeight !== 100)
                        <span class="ml-2">⚠️ {{ __('Must total exactly 100% for balanced calculation.') }}</span>
                    @else
                        <span class="ml-2">✅ {{ __('Balanced') }}</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Groups with nested KPI Templates --}}
        @forelse($groups as $group)
            <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-xl mb-6 overflow-hidden border border-gray-100 dark:border-gray-700">
                {{-- Group Header --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg flex items-center justify-center {{ $group->is_active ? 'bg-primary-100 dark:bg-primary-900/40' : 'bg-gray-200 dark:bg-gray-600' }}">
                            <x-heroicon-m-folder class="h-5 w-5 {{ $group->is_active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ $group->name }}</h3>
                            <span class="text-xs font-mono {{ $group->is_active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}">
                                {{ __('Category Weight:') }} {{ $group->weight }}%
                                @if(!$group->is_active) · <span class="text-red-500">{{ __('Inactive') }}</span> @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $childWeight = $group->kpiTemplates->where('is_active', true)->sum('weight');
                        @endphp
                        <span class="text-xs px-2 py-1 rounded-md font-bold {{ $childWeight === 100 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                            {{ __('Child Weight:') }} {{ $childWeight }}%
                            {{ $childWeight === 100 ? '✅' : '⚠️' }}
                        </span>
                        <button wire:click="createTemplate({{ $group->id }})" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 transition" title="{{ __('Add KPI Component') }}">
                            <x-heroicon-m-plus-circle class="h-5 w-5" />
                        </button>
                        <button wire:click="editGroup({{ $group->id }})" class="text-gray-400 hover:text-blue-600 transition" title="{{ __('Edit Category') }}">
                            <x-heroicon-m-pencil-square class="h-4 w-4" />
                        </button>
                        <button wire:click="deleteGroup({{ $group->id }})" wire:confirm="{{ __('Are you sure you want to delete this category?') }}" class="text-gray-400 hover:text-red-600 transition" title="{{ __('Delete Category') }}">
                            <x-heroicon-m-trash class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                {{-- Child KPI Templates --}}
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-white dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="pl-8 pr-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Performance Objective') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">{{ __('Weight') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700/50">
                        @forelse ($group->kpiTemplates as $kpi)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="pl-8 pr-4 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="font-semibold">{{ $kpi->name }}</div>
                                    @if($kpi->indicator_description)
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 font-normal max-w-lg leading-relaxed">
                                            @foreach(explode("\n", $kpi->indicator_description) as $line)
                                                @php $line = trim($line); @endphp
                                                @if(str_starts_with($line, '- '))
                                                    <div class="flex items-start gap-1 mt-0.5 first:mt-0">
                                                        <span class="text-gray-400 mt-px">•</span>
                                                        <span>{{ ltrim($line, '- ') }}</span>
                                                    </div>
                                                @elseif($line !== '')
                                                    <p class="mt-0.5 first:mt-0">{{ $line }}</p>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-mono font-bold text-gray-700 dark:text-gray-300">
                                    {{ $kpi->weight }}%
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <button wire:click="toggleActive({{ $kpi->id }})" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $kpi->is_active ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $kpi->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                    </button>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $kpi->id }})" class="text-gray-400 hover:text-blue-600 transition" title="{{ __('Edit') }}">
                                            <x-heroicon-m-pencil-square class="h-4 w-4" />
                                        </button>
                                        <button wire:click="delete({{ $kpi->id }})" wire:confirm="{{ __('Are you sure to delete?') }}" class="text-gray-400 hover:text-red-600 transition" title="{{ __('Delete') }}">
                                            <x-heroicon-m-trash class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-sm text-gray-400 italic">
                                    {{ __('No KPI components yet. Click the (+) icon above to add.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-xl p-12 text-center">
                <x-heroicon-o-folder-plus class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('No KPI Categories Yet') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('Start by creating a parent category, then add KPI components inside it.') }}</p>
                <x-actions.button wire:click="createGroup">{{ __('Create First Category') }}</x-actions.button>
            </div>
        @endforelse
    </div>

    {{-- ═══════ GROUP MODAL ═══════ --}}
    <x-overlays.dialog-modal wire:model.live="showGroupModal">
        <x-slot name="title">
            {{ $editGroupId ? __('Edit KPI Category') : __('Add KPI Category') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-forms.label for="groupName" value="{{ __('Category Name') }}" />
                    <x-forms.input id="groupName" type="text" class="mt-1 block w-full" wire:model="groupName" placeholder="{{ __('Example: Key Performance Indicator (KPI)') }}" />
                    <x-forms.input-error for="groupName" class="mt-2" />
                </div>
                <div>
                    <x-forms.label for="groupWeight" value="{{ __('Category Weight (%)') }}" />
                    <x-forms.input id="groupWeight" type="number" class="mt-1 block w-full font-mono" wire:model="groupWeight" min="0" max="100" />
                    <x-forms.input-error for="groupWeight" class="mt-2" />
                    <p class="text-xs text-gray-500 mt-1">{{ __('Total weight of all active categories must be exactly 100%.') }}</p>
                </div>
                <div class="flex items-center mt-4">
                    <input type="checkbox" id="groupIsActive" wire:model="groupIsActive" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded dark:bg-gray-900 dark:border-gray-700">
                    <label for="groupIsActive" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                        {{ __('Active (Will be used in appraisal cycle)') }}
                    </label>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showGroupModal', false)">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="saveGroup">
                {{ $editGroupId ? __('Update') : __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    {{-- ═══════ TEMPLATE (CHILD) MODAL ═══════ --}}
    <x-overlays.dialog-modal wire:model.live="showModal">
        <x-slot name="title">
            {{ $editId ? __('Edit KPI Component') : __('Add KPI Component') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-forms.label for="name" value="{{ __('Performance Objective') }}" />
                    <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model="name" placeholder="{{ __('Example: FDC Dashboard Development') }}" />
                    <x-forms.input-error for="name" class="mt-2" />
                </div>
                
                <div>
                    <x-forms.label for="indicator_description" value="{{ __('Performance Indicator (Target)') }}" />
                    <textarea id="indicator_description" wire:model="indicator_description" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm" placeholder="{{ __("Write each point starting with a dash (-):\n- Achieve 100% monthly SLA\n- 0% downtime per quarter\n- Timely reports") }}"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1.5">💡 {{ __('Tip: Start each item with "- " (dash space) to display as a list in the appraisal form.') }}</p>
                    <x-forms.input-error for="indicator_description" class="mt-2" />
                </div>
                <div>
                    <x-forms.label for="weight" value="{{ __('Component Weight (%)') }}" />
                    <x-forms.input id="weight" type="number" class="mt-1 block w-full font-mono" wire:model="weight" min="1" max="100" />
                    <x-forms.input-error for="weight" class="mt-2" />
                    <p class="text-xs text-gray-500 mt-1">{{ __('Total active component weight in a category must be 100%.') }}</p>
                </div>
                
                <div class="flex items-center mt-4">
                    <input type="checkbox" id="is_active" wire:model="is_active" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded dark:bg-gray-900 dark:border-gray-700">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                        {{ __('Active') }}
                    </label>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showModal', false)">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.button class="ml-2" wire:click="save">
                {{ $editId ? __('Update') : __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Period Lock Card -->
    <div class="mt-10 w-full">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Appraisal Period Lock') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Set when employees and managers can submit appraisals. Close the window to prevent late submissions.') }}</p>
                    </div>
                    <button wire:click="togglePeriodLock" class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $periodOpen ? 'bg-green-500' : 'bg-red-500' }}">
                        <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $periodOpen ? 'translate-x-7' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <div class="rounded-lg p-4 mb-4 {{ $periodOpen ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                    <div class="flex items-center gap-2">
                        @if($periodOpen)
                            <x-heroicon-m-lock-open class="h-5 w-5 text-green-600" />
                            <span class="text-sm font-bold text-green-700 dark:text-green-400">{{ __('Window OPEN') }} — {{ __('Employees and managers can submit appraisals.') }}</span>
                        @else
                            <x-heroicon-m-lock-closed class="h-5 w-5 text-red-600" />
                            <span class="text-sm font-bold text-red-700 dark:text-red-400">{{ __('Window CLOSED') }} — {{ __('Submissions are locked. No new appraisals can be created.') }}</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-forms.label for="periodLabel" value="{{ __('Period Label') }}" />
                        <x-forms.input id="periodLabel" type="text" class="mt-1 block w-full" wire:model="periodLabel" placeholder="{{ __('Example: Q1 2026, Semester 1 2026') }}" />
                        <x-forms.input-error for="periodLabel" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="periodDeadline" value="{{ __('Submission Deadline') }}" />
                        <x-forms.input id="periodDeadline" type="date" class="mt-1 block w-full" wire:model="periodDeadline" />
                        <x-forms.input-error for="periodDeadline" class="mt-2" />
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <x-actions.button wire:click="savePeriodLock">
                        {{ __('Save Period Settings') }}
                    </x-actions.button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Evaluation Settings -->
    <div class="mt-10 w-full">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="mb-2">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Advanced Evaluation Metrics') }}</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Set the balance between objective system factors (Attendance) and the manager\'s subjective assessment (KPI).') }}</p>

                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-5 border border-gray-100 dark:border-gray-600">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div>
                            <x-forms.label for="attendanceWeight" value="{{ __('System Attendance Weight (%)') }}" class="mb-2 font-bold text-gray-700 dark:text-gray-300" />
                            <div class="flex items-center gap-3">
                                <div class="relative w-32">
                                    <x-forms.input id="attendanceWeight" type="number" wire:model.live.debounce.500ms="attendanceWeight" min="0" max="100" class="block w-full text-lg pr-8 font-bold" />
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">%</div>
                                </div>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">+</span>
                                <div class="flex-1 bg-white dark:bg-gray-800 px-3 py-2.5 rounded-md border border-gray-200 dark:border-gray-700 text-center">
                                    <span class="text-sm text-gray-400">{{ __('Subjective KPI Weight:') }} </span>
                                    <span class="text-lg font-bold text-primary-600 ml-1">{{ 100 - (int)$attendanceWeight }}%</span>
                                </div>
                            </div>
                            <x-forms.input-error for="attendanceWeight" class="mt-2" />
                        </div>
                        
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <strong>{{ __('How it works:') }}</strong> {{ __('The final score consists of two factors. System Attendance is calculated automatically. The remaining percentage is allocated to the Manager\'s subjective assessment of the KPIs above.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
