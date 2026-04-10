<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight">
                {{ __('My Performance Reviews') }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('View your performance assessments, submit self-evaluations, and prepare for 1-on-1 manager meetings.') }}
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Period') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Evaluator') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Score') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Meeting') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse($appraisals as $appraisal)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::createFromDate($appraisal->period_year, $appraisal->period_month, 1)->format('F Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                    {{ in_array($appraisal->status, ['draft', 'self_assessment']) ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                    {{ in_array($appraisal->status, ['manager_review', '1on1_scheduled']) ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $appraisal->status === 'completed' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : '' }}">
                                    {{ __(ucwords(str_replace('_', ' ', $appraisal->status))) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $appraisal->evaluator ? $appraisal->evaluator->name : __('Not assigned yet') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($appraisal->status === 'completed')
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $appraisal->final_score }}</span> / 100
                                @else
                                    <span class="text-xs text-gray-400">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($appraisal->meeting_date)
                                    <div>{{ \Carbon\Carbon::parse($appraisal->meeting_date)->format('d M Y') }}</div>
                                    @if($appraisal->meeting_link)
                                        <a href="{{ $appraisal->meeting_link }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 text-xs">{{ __('Join Link') }}</a>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400">{{ __('Not Scheduled') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($appraisal->status === 'self_assessment')
                                    <button wire:click="openSelfAssessment({{ $appraisal->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        {{ __('Fill Assessment') }}
                                    </button>
                                @elseif($appraisal->status === 'completed' && !$appraisal->employee_acknowledgement)
                                    <button wire:click="acknowledge({{ $appraisal->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        {{ __('Acknowledge') }}
                                    </button>
                                @else
                                    <span class="text-gray-400">{{ __('View Only') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-document-text class="h-10 w-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                                {{ __('No performance reviews found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Self Assessment Form Modal -->
    <x-dialog-modal wire:model.live="showSelfAssessmentModal" maxWidth="3xl">
        <x-slot name="title">
            {{ __('Self Assessment Form') }}
        </x-slot>

        <x-slot name="content">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Please evaluate your performance for each KPI. Use a scale of 1-100. Provide clear, concise comments to justify your score so your manager can review them during the 1-on-1 meeting.') }}
            </p>
            
            <div class="space-y-6">
                @foreach($evaluations as $index => $evaluation)
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ $evaluation->kpiTemplate->name }}</h4>
                            <span class="text-xs font-mono bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 px-2 py-1 rounded">
                                {{ __('Weight') }}: {{ $evaluation->kpiTemplate->weight }}%
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-1">
                                <x-label for="score_{{ $evaluation->id }}" value="{{ __('Your Score (1-100)') }}" />
                                <x-input id="score_{{ $evaluation->id }}" type="number" min="1" max="100" class="mt-1 block w-full text-center" wire:model="selfScores.{{ $evaluation->id }}" />
                                <x-input-error for="selfScores.{{ $evaluation->id }}" class="mt-2" />
                            </div>
                            <div class="md:col-span-3">
                                <x-label for="comment_{{ $evaluation->id }}" value="{{ __('Self Reflection / Proof of Target') }}" />
                                <textarea id="comment_{{ $evaluation->id }}" wire:model="selfComments.{{ $evaluation->id }}" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"></textarea>
                                <x-input-error for="selfComments.{{ $evaluation->id }}" class="mt-2" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showSelfAssessmentModal', false)">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ml-2" wire:click="submitSelfAssessment" wire:confirm="{{ __('Are you sure? Once submitted, you cannot change your self-assessment.') }}">
                {{ __('Submit Review to Manager') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
