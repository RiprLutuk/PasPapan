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

        <!-- Period Lock Banner -->
        <div class="mb-6 rounded-lg p-4 {{ $periodOpen ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if($periodOpen)
                        <x-heroicon-m-lock-open class="h-5 w-5 text-green-600" />
                        <span class="text-sm font-bold text-green-700 dark:text-green-400">{{ __('Appraisal Window: OPEN') }}</span>
                        @if($periodLabel)
                            <span class="text-xs text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-800 px-2 py-0.5 rounded-full">{{ $periodLabel }}</span>
                        @endif
                    @else
                        <x-heroicon-m-lock-closed class="h-5 w-5 text-red-600" />
                        <span class="text-sm font-bold text-red-700 dark:text-red-400">{{ __('Appraisal Window: CLOSED — New evaluations are locked.') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bell Curve Score Distribution -->
        @if(array_sum($bellCurve) > 0)
        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-6">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4">📊 {{ __('Score Distribution (Bell Curve)') }} — {{ __(date('F', mktime(0, 0, 0, $month, 10))) }} {{ $year }}</h3>
            <div class="grid grid-cols-5 gap-3 items-end" style="height: 120px;">
                @php
                    $maxCount = max(1, max($bellCurve));
                    $colors = ['A' => 'bg-green-500', 'B' => 'bg-blue-500', 'C' => 'bg-yellow-500', 'D' => 'bg-orange-500', 'E' => 'bg-red-500'];
                    $labels = ['A' => '≥90 Outstanding', 'B' => '80-89 Exceeds', 'C' => '70-79 Meets', 'D' => '60-69 Needs Imp.', 'E' => '<60 Below'];
                @endphp
                @foreach($bellCurve as $grade => $count)
                    <div class="flex flex-col items-center justify-end h-full">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $count }}</span>
                        <div class="{{ $colors[$grade] }} rounded-t-md w-full transition-all duration-500" style="height: {{ ($count / $maxCount) * 100 }}%; min-height: 4px;"></div>
                        <div class="mt-2 text-center">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $grade }}</span>
                            <div class="text-[10px] text-gray-500 leading-tight">{{ $labels[$grade] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

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
                                    <div class="flex justify-end gap-2 items-center">
                                        <button wire:click="initOrEvaluate('{{ $user->id }}')" type="button" class="text-gray-400 hover:text-primary-600 transition-colors" title="{{ $eval ? __('Update Evaluation') : __('Evaluate') }}">
                                            @if($eval)
                                                <x-heroicon-m-pencil-square class="h-6 w-6" />
                                            @else
                                                <x-heroicon-m-clipboard-document-check class="h-6 w-6" />
                                            @endif
                                        </button>
                                        @if($eval && $eval->status === 'completed')
                                            <a href="{{ route('appraisal.export-pdf', $eval) }}" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Download PDF') }}">
                                                <x-heroicon-m-arrow-down-tray class="h-6 w-6" />
                                            </a>
                                            @if(auth()->user()->isSuperadmin && $eval->calibration_status === 'pending')
                                                <button wire:click="calibrate({{ $eval->id }}, 'approved')" class="text-gray-400 hover:text-green-600 transition-colors" title="{{ __('Approve') }}">
                                                    <x-heroicon-m-check-circle class="h-6 w-6" />
                                                </button>
                                                <button wire:click="calibrate({{ $eval->id }}, 'rejected')" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Reject') }}">
                                                    <x-heroicon-m-x-circle class="h-6 w-6" />
                                                </button>
                                            @elseif($eval->calibration_status === 'approved')
                                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded-full">✓ {{ __('Calibrated') }}</span>
                                            @elseif($eval->calibration_status === 'rejected')
                                                <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded-full">✗ {{ __('Rejected') }}</span>
                                            @endif
                                        @endif
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

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- EVALUATION MODAL — Redesigned for warmth & clarity            -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <x-dialog-modal wire:model.live="showModal" maxWidth="4xl">
            <x-slot name="title">
                <div class="flex items-center gap-3">
                    @if($evaluatingUser)
                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-primary-400 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-md">
                        {{ strtoupper(substr($evaluatingUser->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-base font-bold text-gray-900 dark:text-white leading-tight">{{ $evaluatingUser->name }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $evaluatingUser->nip ?? '' }} · {{ __(date('F', mktime(0, 0, 0, $month, 10))) }} {{ $year }}
                        </div>
                    </div>
                    @else
                    <span class="text-base font-bold text-gray-900 dark:text-white">{{ __('Evaluation Form') }}</span>
                    @endif
                </div>
            </x-slot>

            <x-slot name="content">
                <!-- Focus trap to prevent TomSelect from auto-opening -->
                <button type="button" autofocus class="sr-only"></button>

                @if($evaluatingUser)
                <div class="space-y-8">

                    <!-- ── Section 1: Status & Attendance ─────────────── -->
                    <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-800/60 dark:to-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    <x-heroicon-m-signal class="h-3.5 w-3.5" />
                                    {{ __('Appraisal Status') }}
                                </label>
                                @php
                                $statusOptions = [
                                    ['id' => 'draft', 'name' => __('📝 Draft')],
                                    ['id' => 'self_assessment', 'name' => __('⏳ Pending Self Assessment')],
                                    ['id' => 'manager_review', 'name' => __('👁️ Manager Reviewing')],
                                    ['id' => '1on1_scheduled', 'name' => __('📅 1-on-1 Meeting')],
                                    ['id' => 'completed', 'name' => __('✅ Completed')],
                                ];
                                @endphp
                                <x-tom-select id="appraisalStatus" wire:model="appraisalStatus" :options="$statusOptions" placeholder="{{ __('Select Status') }}" />
                            </div>
                            <div>
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    <x-heroicon-m-clock class="h-3.5 w-3.5" />
                                    {{ __('Attendance Score') }}
                                    <span class="ml-auto font-mono text-[10px] bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-500 normal-case">{{ \App\Models\Setting::getValue('appraisal.attendance_weight', 30) }}% {{ __('Weight') }}</span>
                                </label>
                                <div class="relative">
                                    <x-input type="text" disabled readonly value="{{ number_format((float)$attendanceScore, 2) }}" class="block w-full h-[42px] bg-gray-100 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 font-bold text-lg cursor-not-allowed border-gray-200 dark:border-gray-700 pr-14" />
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 text-sm font-medium">/ 100</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Section 2: KPI Evaluation Matrix (Grouped) ── -->
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
                                @foreach($groupEvals as $eval)
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 transition hover:shadow-md">
                                    <div class="px-4 py-3 flex items-center justify-between bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="flex-shrink-0 h-2 w-2 rounded-full bg-primary-400"></span>
                                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $eval->kpiTemplate->name ?? __('KPI') }}</h4>
                                        </div>
                                        <span class="flex-shrink-0 text-[11px] font-mono font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $eval->kpiTemplate->weight ?? 0 }}%</span>
                                    </div>

                                    @if($eval->kpiTemplate && $eval->kpiTemplate->indicator_description)
                                    <div class="px-4 py-2.5 bg-sky-50/70 dark:bg-sky-900/10 border-b border-sky-100/70 dark:border-sky-900/20">
                                        <div class="text-xs text-sky-700 dark:text-sky-400 leading-relaxed">
                                            @foreach(explode("\n", $eval->kpiTemplate->indicator_description) as $line)
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
                                            <textarea wire:model="evidenceDescriptions.{{ $eval->id }}" rows="2" 
                                                class="block w-full rounded-lg border-gray-200 dark:border-gray-600 text-sm p-3 bg-gray-50 dark:bg-gray-900/40 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 resize-none placeholder-gray-300 dark:placeholder-gray-600" 
                                                placeholder="{{ __('Describe the achievements...') }}"></textarea>
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5 block">{{ __('Score') }}</label>
                                            <select id="ms_{{ $eval->id }}" wire:model="managerScores.{{ $eval->id }}" class="block w-full rounded-lg border-gray-200 dark:border-gray-600 font-semibold text-sm bg-white dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500 h-[42px]">
                                                <option value="">— {{ __('Select Scale') }} —</option>
                                                <option value="1">1 · {{ __('Very Poor') }}</option>
                                                <option value="2">2 · {{ __('Poor') }}</option>
                                                <option value="3">3 · {{ __('Fair') }}</option>
                                                <option value="4">4 · {{ __('Good') }}</option>
                                                <option value="5">5 · {{ __('Outstanding') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- ── Section 3: Meeting Schedule ─────────────── -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <x-heroicon-m-calendar-days class="h-4 w-4 text-gray-400" />
                                {{ __('1-on-1 Meeting Schedule') }}
                            </h3>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 block">{{ __('Date') }}</label>
                                <x-input id="meetingDate" type="date" class="block w-full h-[42px] text-sm rounded-lg" wire:model="meetingDate" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 block">{{ __('Virtual Meeting Link') }}</label>
                                <x-input id="meetingLink" type="url" class="block w-full h-[42px] text-sm rounded-lg" wire:model="meetingLink" placeholder="https://meet.google.com/..." />
                            </div>
                        </div>
                    </div>

                    <!-- ── Section 4: Notes & Recommendations ──────── -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <x-heroicon-m-chat-bubble-bottom-center-text class="h-4 w-4 text-gray-400" />
                                {{ __('Notes & Recommendations') }}
                            </h3>
                        </div>
                        <div class="p-5 space-y-5">
                            <div>
                                <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider mb-1.5">
                                    <span class="h-3 w-1 rounded-full bg-blue-500"></span>
                                    <span class="text-blue-600 dark:text-blue-400">{{ __('Employee Notes') }}</span>
                                </label>
                                <textarea id="employeeNotes" wire:model="employeeNotes" rows="2" 
                                    class="block w-full rounded-lg border-gray-200 dark:border-gray-600 text-sm p-3 bg-gray-50 dark:bg-gray-900/40 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 resize-none placeholder-gray-300 dark:placeholder-gray-600"
                                    placeholder="{{ __('Employee\'s opinion on performance achievements and expectations...') }}"></textarea>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider mb-1.5">
                                    <span class="h-3 w-1 rounded-full bg-emerald-500"></span>
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ __('Evaluator Notes') }}</span>
                                </label>
                                <textarea id="generalNotes" wire:model="generalNotes" rows="2" 
                                    class="block w-full rounded-lg border-gray-200 dark:border-gray-600 text-sm p-3 bg-gray-50 dark:bg-gray-900/40 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 resize-none placeholder-gray-300 dark:placeholder-gray-600"
                                    placeholder="{{ __('Evaluator\'s opinion on employee performance...') }}"></textarea>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider mb-1.5">
                                    <span class="h-3 w-1 rounded-full bg-amber-500"></span>
                                    <span class="text-amber-600 dark:text-amber-400">{{ __('Development Recommendations') }}</span>
                                </label>
                                <textarea id="developmentRecommendations" wire:model="developmentRecommendations" rows="2" 
                                    class="block w-full rounded-lg border-gray-200 dark:border-gray-600 text-sm p-3 bg-gray-50 dark:bg-gray-900/40 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 resize-none placeholder-gray-300 dark:placeholder-gray-600"
                                    placeholder="{{ __('Example: AWS Training, PMP Certification, Leadership Mentoring...') }}"></textarea>
                            </div>
                        </div>
                    </div>

                </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <div class="flex items-center justify-between w-full">
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 hidden sm:block">
                        <x-heroicon-m-information-circle class="h-3.5 w-3.5 inline -mt-0.5" />
                        {{ __('All changes are saved after clicking the Save button.') }}
                    </p>
                    <div class="flex items-center gap-3">
                        <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled" class="h-[40px] px-5">
                            {{ __('Close') }}
                        </x-secondary-button>
                        <x-button class="h-[40px] px-6 !bg-primary-600 hover:!bg-primary-700 shadow-lg shadow-primary-500/20" wire:click="save" wire:loading.attr="disabled">
                            <x-heroicon-m-check class="w-4 h-4 mr-1.5" />
                            <span wire:loading.remove wire:target="save">{{ __('Save Appraisal') }}</span>
                            <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                        </x-button>
                    </div>
                </div>
            </x-slot>
        </x-dialog-modal>

    </div>
</div>

