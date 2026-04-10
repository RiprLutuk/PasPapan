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
                                        <button wire:click="initOrEvaluate('{{ $user->id }}')" type="button" class="text-gray-400 hover:text-primary-600 transition-colors" title="{{ $eval ? __('Update Evaluation') : __('Evaluate') }}">
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
                            <button wire:click="initOrEvaluate('{{ $user->id }}')" type="button" class="p-2 text-gray-400 hover:text-primary-600 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
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
        <x-dialog-modal wire:model.live="showModal" maxWidth="4xl">
            <x-slot name="title">
                {{ __('Appraisal Workflow') }}: {{ $evaluatingUser ? $evaluatingUser->name : '' }}
            </x-slot>

            <x-slot name="content">
                @if($evaluatingUser)
                <div class="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Period') }}: {{ __(date('F', mktime(0, 0, 0, $month, 10))) }} {{ $year }}
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Progress Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Appraisal Status') }}</label>
                            <select wire:model="appraisalStatus" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-white">
                                <option value="draft">{{ __('Draft') }}</option>
                                <option value="self_assessment">{{ __('Pending Self Assessment') }}</option>
                                <option value="manager_review">{{ __('Manager Reviewing') }}</option>
                                <option value="1on1_scheduled">{{ __('1-on-1 Meeting Scheduled') }}</option>
                                <option value="completed">{{ __('Completed') }}</option>
                            </select>
                        </div>
                        
                        <!-- System Score Block -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('System Attendance Score') }} (30% {{ __('Weight') }})</label>
                            <div class="p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700/50">
                                <span class="text-2xl font-bold {{ $attendanceScore >= 80 ? 'text-green-600' : ($attendanceScore >= 60 ? 'text-yellow-600' : 'text-red-600') }}">{{ $attendanceScore }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">{{ __('KPI Performance Matrices') }}</h3>
                    
                    @foreach($evaluations as $eval)
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ $eval->kpiTemplate->name ?? 'Unknown KPI' }}</h4>
                                <span class="text-xs font-mono bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 px-2 py-1 rounded">
                                    {{ __('Weight') }}: {{ $eval->kpiTemplate->weight ?? 0 }}%
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Self Assessment Half -->
                                <div class="border-r border-gray-200 dark:border-gray-600 pr-4">
                                    <h5 class="text-xs uppercase text-gray-500 font-bold mb-2">{{ __('Employee Self Score') }}</h5>
                                    @if($eval->self_score)
                                        <div class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $eval->self_score }}</div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 italic">"{{ $eval->comments }}"</p>
                                    @else
                                        <p class="text-sm text-gray-400 italic">{{ __('No self-assessment received yet.') }}</p>
                                    @endif
                                </div>
                                
                                <!-- Manager Evaluation Half -->
                                <div>
                                    <h5 class="text-xs uppercase text-gray-500 font-bold mb-2">{{ __('Manager Input') }}</h5>
                                    <div>
                                        <x-label for="ms_{{ $eval->id }}" value="{{ __('Manager Score (1-100)') }}" />
                                        <x-input id="ms_{{ $eval->id }}" type="number" min="0" max="100" class="mt-1 w-24 block" wire:model="managerScores.{{ $eval->id }}" />
                                    </div>
                                    <div class="mt-3">
                                        <x-label for="mc_{{ $eval->id }}" value="{{ __('Manager Note (Optional)') }}" />
                                        <textarea id="mc_{{ $eval->id }}" wire:model="evalComments.{{ $eval->id }}" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                    <!-- 1 on 1 scheduling -->
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">{{ __('1-on-1 Session') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-label for="meetingDate" value="{{ __('Meeting Date') }}" />
                            <x-input id="meetingDate" type="date" class="mt-1 block w-full" wire:model="meetingDate" />
                        </div>
                        <div>
                            <x-label for="meetingLink" value="{{ __('Meeting Room Link (Virtual)') }}" />
                            <x-input id="meetingLink" type="url" class="mt-1 block w-full" wire:model="meetingLink" placeholder="https://meet.google.com/..." />
                        </div>
                    </div>

                    <!-- Overall Manager Notes -->
                    <div>
                        <x-label for="generalNotes" value="{{ __('Overall General Notes') }}" />
                        <textarea id="generalNotes" wire:model="generalNotes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white" placeholder="{{ __('Final conclusive remarks...') }}"></textarea>
                    </div>
                </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-button class="ml-3" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ __('Save Workflow') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </x-button>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>
