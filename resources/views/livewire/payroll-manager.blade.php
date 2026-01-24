<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header & Actions --}}
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-800 to-gray-600 dark:from-white dark:to-gray-300">
                    {{ __('Payroll Management') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Generate and manage employee payments.') }}</p>
            </div>
            
            <div class="flex items-center gap-3">
                <select wire:model.live="month" class="rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
                <select wire:model.live="year" class="rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                    @foreach(range(date('Y')-1, date('Y')+1) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
                
                <button wire:click="openGenerateModal" class="bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-medium py-2 px-4 rounded-xl shadow-lg shadow-indigo-500/30 transform hover:scale-105 transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    {{ __('Generate Payroll') }}
                </button>
            </div>
        </div>

        {{-- Stats / Search (Optional - skipping for now to focus on table) --}}

        {{-- Payroll Table --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Basic Salary') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Overtime') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Deductions') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Net Salary') }}</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-transparent">
                        @forelse ($payrolls as $payroll)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <img class="h-10 w-10 rounded-full object-cover" src="{{ $payroll->user?->profile_photo_url }}" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $payroll->user?->name ?? __('Unknown User') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $payroll->user?->jobTitle->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-300">
                                    Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-300">
                                    Rp {{ number_format($payroll->overtime_pay, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-500 dark:text-red-400">
                                    -Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize
                                        @if($payroll->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @elseif($payroll->status === 'published') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300 @endif">
                                        {{ $payroll->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    @if($payroll->status === 'draft')
                                        <button wire:click="publish('{{ $payroll->id }}')" class="text-gray-400 hover:text-blue-600 transition-colors" title="{{ __('Publish') }}">
                                             <x-heroicon-m-paper-airplane class="h-5 w-5" />
                                        </button>
                                    @endif
                                    
                                    @if($payroll->status === 'published')
                                        <button wire:click="pay('{{ $payroll->id }}')" class="text-gray-400 hover:text-green-600 transition-colors" title="{{ __('Mark Paid') }}">
                                             <x-heroicon-m-banknotes class="h-5 w-5" />
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        <p>{{ __('No payrolls found for this period.') }}</p>
                                        <button wire:click="openGenerateModal" class="mt-2 text-indigo-600 hover:underline">{{ __('Generate Now') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
             <div class="px-6 py-4 border-t border-gray-200/50 dark:border-gray-700/50">
                {{ $payrolls->links() }}
            </div>
        </div>
    </div>

    {{-- Generate Modal --}}
    <x-confirmation-modal wire:model.live="showGenerateModal">
        <x-slot name="title">
            {{ __('Generate Payroll') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to generate payroll for') }} <strong>{{ \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') }} {{ $year }}</strong>?
            <p class="mt-2 text-sm text-gray-500">
                {{ __('This will calculate salary, overtime, and deductions for all eligible employees. Existing drafts for this period will be updated.') }}
            </p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showGenerateModal')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ml-2" wire:click="generate" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">{{ __('Generate') }}</span>
                <span wire:loading wire:target="generate">{{ __('Processing...') }}</span>
            </x-button>
        </x-slot>
    </x-confirmation-modal>
</div>
