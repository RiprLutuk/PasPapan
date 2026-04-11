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

        <!-- Evaluation Modal -->
        <x-dialog-modal wire:model.live="showModal" maxWidth="4xl">
            <x-slot name="title">
                {{ __('Appraisal Workflow') }}: {{ $evaluatingUser ? $evaluatingUser->name : '' }}
            </x-slot>

            <x-slot name="content">
                <!-- Focus trap to prevent TomSelect from auto-opening on modal load -->
                <button type="button" autofocus class="sr-only"></button>

                @if($evaluatingUser)
                <div class="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                    <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                        {{ __('Period') }}: <span class="text-gray-900 dark:text-white">{{ __(date('F', mktime(0, 0, 0, $month, 10))) }} {{ $year }}</span>
                    </div>
                </div>

                <div class="space-y-6 mt-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Progress Status -->
                        <div>
                            <x-label for="appraisalStatus" value="{{ __('Appraisal Status') }}" class="mb-1 text-gray-700 dark:text-gray-300 font-bold" />
                            <div class="relative">
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
                        </div>
                        
                        <!-- System Score Block -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <x-label value="{{ __('System Attendance Score') }}" class="text-gray-700 dark:text-gray-300 font-bold" />
                                <span class="bg-gray-100 dark:bg-gray-700 font-mono text-[10px] px-1.5 py-0.5 rounded text-gray-600 dark:text-gray-400">{{ \App\Models\Setting::getValue('appraisal.attendance_weight', 30) }}% {{ __('Weight') }}</span>
                            </div>
                            <x-input type="text" disabled readonly value="{{ number_format((float)$attendanceScore, 2) }} / 100" class="block w-full h-[40px] bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-semibold cursor-not-allowed border-gray-200 dark:border-gray-700" />
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-800 my-2"></div>

                    <h3 class="text-lg font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-m-squares-2x2 class="h-6 w-6 text-primary-500" />
                        {{ __('KPI Matrices') }}
                    </h3>
                    
                    @foreach($evaluations as $eval)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mt-6">
                            <!-- Header -->
                            <div class="px-5 py-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 border-b border-gray-100 dark:border-gray-700">
                                <h4 class="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <x-heroicon-m-chart-bar class="h-5 w-5 text-gray-400" />
                                    {{ $eval->kpiTemplate->name ?? 'Unknown KPI' }}
                                </h4>
                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold px-2.5 py-1 rounded-md">
                                    {{ __('Weight') }}: {{ $eval->kpiTemplate->weight ?? 0 }}%
                                </span>
                            </div>
                            
                            <!-- Body -->
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                                <!-- Employee Side -->
                                <div class="bg-gray-50 dark:bg-gray-800/80 rounded-xl p-4 border border-gray-100 dark:border-gray-700 h-full">
                                    <x-label value="{{ __('Employee Self Assessment') }}" class="mb-3 text-gray-500 dark:text-gray-400 font-bold" />
                                    @if($eval->self_score)
                                        <div class="flex items-baseline gap-2 mb-3">
                                            <span class="text-3xl font-black text-gray-900 dark:text-white">{{ $eval->self_score }}</span>
                                            <span class="text-sm font-medium text-gray-400">/ 100</span>
                                        </div>
                                        @if($eval->comments)
                                        <div class="text-sm text-gray-600 dark:text-gray-400 italic bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-3 rounded-lg shadow-sm relative">
                                            <x-heroicon-m-chat-bubble-left-ellipsis class="h-4 w-4 text-gray-300 dark:text-gray-600 absolute top-3 right-3" />
                                            <span class="pr-6 block">"{{ $eval->comments }}"</span>
                                        </div>
                                        @endif
                                    @else
                                        <div class="h-full min-h-[5rem] flex flex-col items-center justify-center text-sm italic text-gray-400">
                                            <x-heroicon-m-clock class="h-6 w-6 mb-1 opacity-50" />
                                            {{ __('No input provided yet.') }}
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Manager Side -->
                                <div class="h-full flex flex-col">
                                    <div class="flex items-center justify-between mb-3">
                                        <x-label for="ms_{{ $eval->id }}" value="{{ __('Manager Score') }}" class="text-primary-600 dark:text-primary-400 font-bold" />
                                        <span class="text-[10px] uppercase font-bold text-gray-400">{{ __('Max') }} 100</span>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="relative">
                                            <x-input id="ms_{{ $eval->id }}" type="number" step="0.1" min="0" max="100" 
                                                class="block w-full h-[48px] font-bold text-xl pr-16 bg-white dark:bg-gray-800" 
                                                wire:model="managerScores.{{ $eval->id }}" 
                                                oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;" />
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 font-medium">/ 100</div>
                                        </div>
                                        
                                        <textarea id="mc_{{ $eval->id }}" wire:model="evalComments.{{ $eval->id }}" rows="2" 
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm p-3" 
                                            placeholder="{{ __('Constructive feedback...') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="border-t border-gray-100 dark:border-gray-800 my-6"></div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2 mb-4">
                            <x-heroicon-m-calendar-days class="h-5 w-5 text-primary-500" />
                            {{ __('Finalization & 1-on-1 Session') }}
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-label for="meetingDate" value="{{ __('Meeting Date') }}" class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5" />
                                <x-input id="meetingDate" type="date" class="block w-full h-[40px] text-sm rounded-lg" wire:model="meetingDate" />
                            </div>
                            <div>
                                <x-label for="meetingLink" value="{{ __('Meeting Room Link (Virtual)') }}" class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5" />
                                <x-input id="meetingLink" type="url" class="block w-full h-[40px] text-sm rounded-lg" wire:model="meetingLink" placeholder="https://meet.google.com/..." />
                            </div>
                        </div>

                        <!-- Overall Manager Notes -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                            <x-label for="generalNotes" value="{{ __('Overall General Notes') }}" class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2" />
                            <textarea id="generalNotes" wire:model="generalNotes" rows="3" class="block w-full rounded-lg border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="{{ __('Final conclusive remarks regarding this appraisal period...') }}"></textarea>
                        </div>
                    </div>
                </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <div class="flex items-center justify-end w-full gap-3 bg-gray-50 dark:bg-gray-800/50 -m-6 p-6 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                    <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled" class="h-[40px] px-6">
                        {{ __('Close') }}
                    </x-secondary-button>

                    <x-button class="h-[40px] px-6 !bg-primary-600 hover:!bg-primary-700" wire:click="save" wire:loading.attr="disabled">
                        <x-heroicon-m-check class="w-4 h-4 mr-2" />
                        <span wire:loading.remove wire:target="save">{{ __('Save Appraisal') }}</span>
                        <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                    </x-button>
                </div>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>
