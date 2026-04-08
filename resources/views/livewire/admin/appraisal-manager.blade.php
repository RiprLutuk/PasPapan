<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold font-outfit text-gray-800 dark:text-white">Performance Appraisals</h2>
            <p class="text-sm text-gray-500 mt-1">Evaluate staff KPIs and attendance scores</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Employee</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name or NIP..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white transition-shadow">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Month</label>
                <select wire:model.live="month" class="w-full rounded-lg border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                    @endfor
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                <select wire:model.live="year" class="w-full rounded-lg border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                    @for($i = date('Y') - 2; $i <= date('Y') + 1; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700 border-b border-gray-100 dark:border-slate-600 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        <th class="px-6 py-4">Employee</th>
                        <th class="px-6 py-4">Department</th>
                        <th class="px-6 py-4 text-center">Attendance Score</th>
                        <th class="px-6 py-4 text-center">Subjective Score</th>
                        <th class="px-6 py-4 text-center">Final Score</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($users as $user)
                        @php
                            $eval = $appraisals[$user->id] ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <img class="h-10 w-10 rounded-full object-cover border-2 border-white dark:border-slate-800 shadow-sm"
                                            src="{{ $user->profile_photo_url }}" alt="">
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->nip ?? 'No NIP' }}</div>
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $eval->attendance_score >= 80 ? 'bg-green-100 text-green-800' : ($eval->attendance_score >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $eval->attendance_score }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($eval)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $eval->subjective_score }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($eval)
                                    <div class="font-bold {{ $eval->final_score >= 80 ? 'text-green-600' : ($eval->final_score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $eval->final_score }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">Pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <button wire:click="evaluate({{ $user->id }})" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    {{ $eval ? 'Update' : 'Evaluate' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-clipboard-list text-4xl mb-3 text-gray-300"></i>
                                    <p>No employees found for evaluation.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Evaluation Modal -->
    @if($showModal && $evaluatingUser)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm shadow-xl" aria-hidden="true" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-gray-100 dark:border-slate-700">
                    <div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                                Evaluate: {{ $evaluatingUser->name }}
                            </h3>
                            <div class="mt-2 text-sm text-gray-500 mb-4">
                                Period: {{ date('F', mktime(0, 0, 0, $month, 10)) }} {{ $year }}
                            </div>

                            <form wire:submit.prevent="save">
                                <div class="space-y-4">
                                    <!-- Auto Calculated Attendance Score -->
                                    <div class="bg-gray-50 dark:bg-slate-700 p-4 rounded-lg flex justify-between items-center border border-gray-100 dark:border-slate-600">
                                        <div>
                                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">System Attendance Score</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Weight: 40%</span>
                                        </div>
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ $attendanceScore }}
                                        </div>
                                    </div>

                                    <!-- Subjective Component -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Manager Subjective Score (0-100)</label>
                                        <div class="mt-1 flex items-center justify-between">
                                            <input type="range" wire:model.live="subjectiveScore" min="0" max="100" class="w-full mr-4 accent-primary-600">
                                            <input type="number" wire:model.live="subjectiveScore" class="w-20 rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-slate-700 dark:text-white">
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Weight: 60%. Based on soft skills, teamwork, and task completion.</p>
                                        @error('subjectiveScore') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Evaluation Notes</label>
                                        <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-slate-700 dark:text-white" placeholder="Provide feedback or justification..."></textarea>
                                    </div>
                                </div>

                                <div class="mt-6 sm:flex sm:flex-row-reverse space-y-3 sm:space-y-0 sm:space-x-3 sm:space-x-reverse">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto sm:text-sm transition-colors">
                                        Save Evaluation
                                    </button>
                                    <button type="button" wire:click="$set('showModal', false)" class="w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-slate-600 shadow-sm px-4 py-2 bg-white dark:bg-transparent text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto sm:text-sm transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div> 
