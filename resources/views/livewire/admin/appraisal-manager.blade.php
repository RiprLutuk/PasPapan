<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    {{ __('Performance Appraisals') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Evaluate staff KPIs and attendance scores.') }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Search -->
            <div class="relative col-span-1 sm:col-span-2 lg:col-span-2">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" 
                    placeholder="{{ __('Search name, NIP...') }}" 
                    class="block w-full rounded-lg border-0 py-2 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
            </div>
            
            <!-- Month Filter -->
            <div class="col-span-1">
                <x-tom-select id="filter_month" wire:model.live="month" placeholder="{{ __('Select Month') }}" :options="$months" />
            </div>
            
            <!-- Year Filter -->
            <div class="col-span-1">
                <x-tom-select id="filter_year" wire:model.live="year" placeholder="{{ __('Select Year') }}" :options="$years" />
            </div>
        </div>

        <!-- Content -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Department') }}</th>
                            <th scope="col" class="px-6 py-4 text-center font-medium">{{ __('Attendance Score') }}</th>
                            <th scope="col" class="px-6 py-4 text-center font-medium">{{ __('Subjective Score') }}</th>
                            <th scope="col" class="px-6 py-4 text-center font-medium">{{ __('Final Score') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($users as $user)
                            @php
                                $eval = $appraisals[$user->id] ?? null;
                            @endphp
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700 ring-2 ring-white dark:ring-gray-800">
                                            <img class="h-full w-full object-cover" src="{{ $user->profile_photo_url }}" alt="">
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $user->nip ?? __('No NIP') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $user->division->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($eval)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $eval->attendance_score >= 80 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($eval->attendance_score >= 60 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                            {{ $eval->attendance_score }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($eval)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            {{ $eval->subjective_score }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($eval)
                                        <div class="font-bold {{ $eval->final_score >= 80 ? 'text-green-600 dark:text-green-400' : ($eval->final_score >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                            {{ $eval->final_score }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">{{ __('Pending') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="evaluate('{{ $user->id }}')" type="button" class="text-gray-400 hover:text-primary-600 transition-colors" title="{{ $eval ? __('Update Evaluation') : __('Evaluate') }}">
                                            @if($eval)
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            @else
                                                <x-heroicon-m-clipboard-document-check class="h-5 w-5" />
                                            @endif
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <x-heroicon-o-clipboard-document-list class="h-12 w-12 mb-3 text-gray-300 dark:text-gray-600" />
                                        <p>{{ __('No employees found for evaluation.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($users as $user)
                    @php $eval = $appraisals[$user->id] ?? null; @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700 ring-2 ring-white dark:ring-gray-800">
                                    <img class="h-full w-full object-cover" src="{{ $user->profile_photo_url }}" alt="">
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->division->name ?? '-' }}</div>
                                </div>
                            </div>
                            <button wire:click="evaluate('{{ $user->id }}')" type="button" class="p-2 text-gray-400 hover:text-primary-600 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                @if($eval)
                                    <x-heroicon-m-pencil-square class="h-5 w-5" />
                                @else
                                    <x-heroicon-m-clipboard-document-check class="h-5 w-5" />
                                @endif
                            </button>
                        </div>
                        @if($eval)
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Attend.') }}</div>
                                    <div class="font-bold text-sm {{ $eval->attendance_score >= 80 ? 'text-green-600' : ($eval->attendance_score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">{{ $eval->attendance_score }}</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Subj.') }}</div>
                                    <div class="font-bold text-sm text-blue-600">{{ $eval->subjective_score }}</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Final') }}</div>
                                    <div class="font-bold text-sm {{ $eval->final_score >= 80 ? 'text-green-600' : ($eval->final_score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">{{ $eval->final_score }}</div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-1">
                                <span class="text-xs text-gray-400 italic">{{ __('Not yet evaluated') }}</span>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-clipboard-document-list class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                        <p>{{ __('No employees found for evaluation.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $users->links() }}
            </div>
        </div>

        <!-- Evaluation Modal -->
        <x-dialog-modal wire:model.live="showModal">
            <x-slot name="title">
                {{ __('Evaluate') }}: {{ $evaluatingUser ? $evaluatingUser->name : '' }}
            </x-slot>

            <x-slot name="content">
                @if($evaluatingUser)
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('Period') }}: {{ __(date('F', mktime(0, 0, 0, $month, 10))) }} {{ $year }}
                </div>

                <div class="space-y-4">
                    <!-- Auto Calculated Attendance Score -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg flex justify-between items-center border border-gray-100 dark:border-gray-600">
                        <div>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('System Attendance Score') }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Weight') }}: 40%</span>
                        </div>
                        <div class="text-2xl font-bold {{ $attendanceScore >= 80 ? 'text-green-600' : ($attendanceScore >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $attendanceScore }}
                        </div>
                    </div>

                    <!-- Subjective Component -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Manager Subjective Score (0-100)') }}</label>
                        <div class="mt-1 flex items-center justify-between gap-4">
                            <input type="range" wire:model.live="subjectiveScore" min="0" max="100" class="w-full accent-primary-600">
                            <input type="number" wire:model.live="subjectiveScore" min="0" max="100" class="w-20 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white text-center">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Weight: 60%. Based on soft skills, teamwork, and task completion.') }}</p>
                        @error('subjectiveScore') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Preview Final Score -->
                    <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg border border-primary-100 dark:border-primary-800">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ __('Estimated Final Score') }}</span>
                            <span class="text-xl font-bold text-primary-700 dark:text-primary-300">
                                {{ round(($attendanceScore * 0.4) + ($subjectiveScore * 0.6), 1) }}
                            </span>
                        </div>
                        <p class="text-xs text-primary-500 dark:text-primary-400 mt-1">
                            ({{ $attendanceScore }} × 40%) + ({{ $subjectiveScore }} × 60%)
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Evaluation Notes') }}</label>
                        <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white" placeholder="{{ __('Provide feedback or justification...') }}"></textarea>
                    </div>
                </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-button class="ml-3" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ __('Save Evaluation') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </x-button>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>
