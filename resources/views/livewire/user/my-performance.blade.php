<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        
        @if (session()->has('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-900/30 p-4">
                <div class="flex">
                    <x-heroicon-m-x-circle class="h-5 w-5 text-red-500 flex-shrink-0" />
                    <p class="ml-3 text-sm font-medium text-red-800 dark:text-red-300">{{ session('error') }}</p>
                </div>
            </div>
        @endif
        @if (session()->has('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-900/30 p-4">
                <div class="flex">
                    <x-heroicon-m-check-circle class="h-5 w-5 text-green-500 flex-shrink-0" />
                    <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <section aria-labelledby="my-performance-title" class="user-page-surface relative">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('My Performance Reviews')"
                title-id="my-performance-title">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-50 via-white to-indigo-50 text-violet-700 ring-1 ring-inset ring-violet-100 shadow-sm dark:from-violet-900/30 dark:via-gray-800 dark:to-indigo-900/20 dark:text-violet-300 dark:ring-violet-800/60">
                        <x-heroicon-o-chart-bar-square class="h-5 w-5" />
                    </div>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if($appraisals->isEmpty())
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-document-text class="w-12 h-12 text-gray-300 dark:text-gray-500" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No performance reviews found.') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Your managers have not initiated any appraisals yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($appraisals as $appraisal)
                            <div class="p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/50 sm:p-6">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-xl flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-bold text-sm">{{ \Carbon\Carbon::createFromDate($appraisal->period_year, $appraisal->period_month, 1)->format('M') }}</span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white capitalize flex items-center gap-2">
                                            {{ \Carbon\Carbon::createFromDate($appraisal->period_year, $appraisal->period_month, 1)->format('F Y') }}
                                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset
                                                {{ in_array($appraisal->status, ['draft', 'self_assessment']) ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                                {{ in_array($appraisal->status, ['manager_review', '1on1_scheduled']) ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                                {{ $appraisal->status === 'completed' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : '' }}">
                                                {{ __(ucwords(str_replace('_', ' ', $appraisal->status))) }}
                                            </span>
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ __('Evaluator') }}: {{ $appraisal->evaluator ? $appraisal->evaluator->name : __('Not assigned yet') }}
                                        </p>
                                        <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-0.5">
                                            @if($appraisal->meeting_date)
                                                <span>{{ __('Meeting') }}: {{ \Carbon\Carbon::parse($appraisal->meeting_date)->format('d M Y') }}</span>
                                                @if($appraisal->meeting_link)
                                                    <span>•</span>
                                                    <a href="{{ $appraisal->meeting_link }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">{{ __('Join Link') }}</a>
                                                @endif
                                            @else
                                                <span>{{ __('Meeting Not Scheduled') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex w-full items-center justify-between gap-4 border-t border-gray-100 pt-3 dark:border-gray-700 sm:mt-0 sm:w-auto sm:justify-end sm:border-0 sm:pt-0">
                                    <div class="text-left sm:text-right">
                                        <span class="text-[10px] text-gray-500 block uppercase tracking-wider">{{ __('Score') }}</span>
                                        @if($appraisal->status === 'completed')
                                            <span class="font-bold text-gray-900 dark:text-white text-lg">{{ $appraisal->final_score }}</span><span class="text-xs text-gray-400">/100</span>
                                        @else
                                            <span class="text-sm font-medium text-gray-400">-</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2 sm:justify-end">
                                        @if($appraisal->status === 'self_assessment')
                                            <button wire:click="openSelfAssessment({{ $appraisal->id }})" class="px-3 py-2 bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 font-bold text-xs uppercase tracking-widest transition flex items-center gap-1">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                                <span class="hidden sm:inline">{{ __('Assessment') }}</span>
                                            </button>
                                        @elseif($appraisal->status === 'completed' && !$appraisal->employee_acknowledgement)
                                            <button wire:click="acknowledge({{ $appraisal->id }})" class="px-3 py-2 bg-green-500 text-white rounded-xl hover:bg-green-600 shadow-lg shadow-green-500/30 font-bold text-xs uppercase tracking-widest transition flex items-center gap-1">
                                                <x-heroicon-m-check class="w-4 h-4" />
                                                <span class="hidden sm:inline">{{ __('Acknowledge') }}</span>
                                            </button>
                                        @endif
                                        @if($appraisal->status === 'completed')
                                            <a href="{{ route('appraisal.export-pdf', $appraisal) }}" class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-red-600 dark:text-red-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 font-bold text-xs uppercase tracking-widest transition flex items-center gap-1" title="{{ __('Download PDF') }}">
                                                <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                                                <span class="hidden sm:inline">{{ __('PDF') }}</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- SELF ASSESSMENT MODAL — Redesigned for warmth & clarity       -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <x-overlays.dialog-modal wire:model.live="showSelfAssessmentModal" maxWidth="4xl">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white p-2 shadow-md">
                    <x-heroicon-m-clipboard-document-check class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-base font-bold text-gray-900 dark:text-white leading-tight">{{ __('Self Assessment Form') }}</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ __('Please complete your self assessment') }}
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="mb-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border border-indigo-100 dark:border-indigo-800/30 flex items-start gap-3">
                <div class="mt-0.5 text-indigo-500">
                    <x-heroicon-m-information-circle class="h-5 w-5" />
                </div>
                <div class="text-sm text-indigo-800 dark:text-indigo-300">
                    {{ __('Please rate your performance for each KPI. Use a scale of 1-5. Provide clear details of evidence of achievement to make it easier for the Manager to provide the final evaluation.') }}
                </div>
            </div>
            
            <div class="space-y-8">
                @php
                    // Fetch directly to preserve relationships across Livewire component updates
                    $activeAppraisal = \App\Models\Appraisal::with('evaluations.kpiTemplate.kpiGroup')->find($activeAppraisalId);
                    $evalsToList = $activeAppraisal ? $activeAppraisal->evaluations : collect([]);
                    $groupedEvals = collect($evalsToList)->groupBy(fn($e) => $e->kpiTemplate->kpi_group_id ?? 'ungrouped');
                @endphp

                @foreach($groupedEvals as $groupId => $groupEvals)
                    @php
                        $group = ($groupId !== 'ungrouped') ? \App\Models\KpiGroup::find($groupId) : null;
                    @endphp
                    
                    <!-- ── Section: KPI Group ── -->
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-7 w-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                <x-heroicon-m-rectangle-stack class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex-1">{{ $group ? $group->name : __('General') }}</h3>
                            <span class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold px-2.5 py-1 rounded-full border border-indigo-100 dark:border-indigo-800/50">
                                {{ $group ? $group->weight : 100 }}%
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach($groupEvals as $evaluation)
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 transition hover:shadow-md">
                                <div class="px-4 py-3 flex items-center justify-between bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="flex-shrink-0 h-2 w-2 rounded-full bg-primary-400"></span>
                                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $evaluation->kpiTemplate->name ?? __('KPI') }}</h4>
                                    </div>
                                    <span class="flex-shrink-0 text-[11px] font-mono font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $evaluation->kpiTemplate->weight ?? 0 }}%</span>
                                </div>

                                @if($evaluation->kpiTemplate && $evaluation->kpiTemplate->indicator_description)
                                <div class="px-4 py-2.5 bg-sky-50/70 dark:bg-sky-900/10 border-b border-sky-100/70 dark:border-sky-900/20">
                                    <div class="text-xs text-sky-700 dark:text-sky-400 leading-relaxed">
                                        @foreach(explode("\n", $evaluation->kpiTemplate->indicator_description) as $line)
                                            @php $line = trim($line); @endphp
                                            @if(str_starts_with($line, '- '))
                                                <div class="flex items-start gap-1.5 mt-1 first:mt-0">
                                                    <span class="text-sky-400/70 mt-px">•</span>
                                                    <span>{{ ltrim($line, '- ') }}</span>
                                                </div>
                                            @elseif($line !== '')
                                                <p class="mt-1 first:mt-0">{{ $line }}</p>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <div class="p-4 grid grid-cols-1 lg:grid-cols-4 gap-4">
                                    <div class="lg:col-span-3">
                                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5 block">{{ __('Evidence of Achievement') }}</label>
                                        <x-forms.textarea wire:model="evidenceDescriptions.{{ $evaluation->id }}" rows="2"
                                            class="block w-full resize-none rounded-lg border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-300"
                                            placeholder="{{ __('Description of the results you have achieved...') }}" />
                                        <x-forms.input-error for="evidenceDescriptions.{{ $evaluation->id }}" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5 block">{{ __('Your Score') }}</label>
                                        <x-forms.select id="score_{{ $evaluation->id }}" wire:model="selfScores.{{ $evaluation->id }}" class="block w-full rounded-lg border-gray-200 bg-white text-sm font-semibold dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                            <option value="">— {{ __('Select Scale') }} —</option>
                                            <option value="1">1 · {{ __('Very Poor') }}</option>
                                            <option value="2">2 · {{ __('Poor') }}</option>
                                            <option value="3">3 · {{ __('Fair') }}</option>
                                            <option value="4">4 · {{ __('Good') }}</option>
                                            <option value="5">5 · {{ __('Outstanding') }}</option>
                                        </x-forms.select>
                                        <x-forms.input-error for="selfScores.{{ $evaluation->id }}" class="mt-1" />
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                
                <!-- ── Section: Notes ── -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden mt-6">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                            <x-heroicon-m-chat-bubble-bottom-center-text class="h-4 w-4 text-gray-400" />
                            {{ __('General Notes') }}
                        </h3>
                    </div>
                    <div class="p-5">
                        <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider mb-1.5">
                            <span class="h-3 w-1 rounded-full bg-blue-500"></span>
                            <span class="text-blue-600 dark:text-blue-400">{{ __('Employee Notes') }}</span>
                        </label>
                        <x-forms.textarea id="employeeNotes" wire:model="employeeNotes" rows="3"
                            class="block w-full resize-none rounded-lg border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-300"
                            placeholder="{{ __('Your opinion on overall performance achievements, challenges, and your expectations going forward...') }}" />
                        <x-forms.input-error for="employeeNotes" class="mt-1" />
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <p class="text-[11px] text-gray-400 dark:text-gray-500 hidden sm:block">
                    <x-heroicon-m-information-circle class="h-3.5 w-3.5 inline -mt-0.5" />
                    {{ __('Scores cannot be changed after being submitted to the Manager.') }}
                </p>
                <div class="flex items-center gap-3">
                    <x-actions.secondary-button wire:click="$set('showSelfAssessmentModal', false)" class="h-[40px] px-5">
                        {{ __('Close') }}
                    </x-actions.secondary-button>
        
                    <x-actions.button class="h-[40px] px-6 !bg-primary-600 hover:!bg-primary-700 shadow-lg shadow-primary-500/20" wire:click="submitSelfAssessment" wire:confirm="{{ __('Are you sure? Once submitted, you cannot change this assessment.') }}">
                        <x-heroicon-m-paper-airplane class="w-4 h-4 mr-1.5 -rotate-45" />
                        {{ __('Submit') }}
                    </x-actions.button>
                </div>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
