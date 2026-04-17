<div x-data="{ showDetail: false, detailPayroll: null }">
    <x-admin.page-shell
        :title="__('Payroll Management')"
        :description="__('Generate and manage employee payments.')"
    >
        <x-slot name="actions">
            <button wire:click="openGenerateModal" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-2.5 font-medium text-white shadow-lg shadow-primary-500/30 transition-all duration-200 hover:scale-[1.02] hover:from-primary-500 hover:to-primary-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                {{ __('Generate Payroll') }}
            </button>
        </x-slot>

        <x-slot name="toolbar">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-md">
                <select wire:model.live="month" class="rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
                <select wire:model.live="year" class="rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    @foreach(range(date('Y')-1, date('Y')+1) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot>

        @if(count($selectedPayrolls) > 0)
        <div class="mb-4 flex items-center gap-3 rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-800 dark:bg-primary-900/20">
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                {{ count($selectedPayrolls) }} {{ __('selected') }}
            </span>
            <div class="ml-auto flex items-center gap-2">
                <button wire:click="bulkPublish" wire:confirm="{{ __('Publish all selected draft payrolls?') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700">
                    <x-heroicon-m-paper-airplane class="h-4 w-4" />
                    {{ __('Publish Selected') }}
                </button>
                <button wire:click="bulkPay" wire:confirm="{{ __('Mark all selected published payrolls as paid?') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-green-700">
                    <x-heroicon-m-banknotes class="h-4 w-4" />
                    {{ __('Pay Selected') }}
                </button>
            </div>
        </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-gray-200/50 bg-white/80 shadow-xl backdrop-blur-xl dark:border-gray-700/50 dark:bg-gray-800/80">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="w-10 px-4 py-3 text-center">
                                <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Basic Salary') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Overtime') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Deductions') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Net Salary') }}</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-transparent dark:divide-gray-700">
                        @forelse ($payrolls as $payroll)
                        <tr class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" wire:model.live="selectedPayrolls" value="{{ $payroll->id }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
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
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-300">
                                Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-300">
                                Rp {{ number_format($payroll->overtime_pay, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-red-500 dark:text-red-400">
                                -Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">
                                Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize
                                        @if($payroll->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @elseif($payroll->status === 'published') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300 @endif">
                                    {{ $payroll->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1">
                                    @if($this->canManage)
                                    <button @click="detailPayroll = {{ json_encode([
                                            'name' => $payroll->user?->name,
                                            'job' => $payroll->user?->jobTitle->name ?? '-',
                                            'basic_salary' => $payroll->basic_salary,
                                            'overtime_pay' => $payroll->overtime_pay,
                                            'allowances' => $payroll->allowances ?? [],
                                            'deductions' => $payroll->deductions ?? [],
                                            'total_allowance' => $payroll->total_allowance,
                                            'total_deduction' => $payroll->total_deduction,
                                            'net_salary' => $payroll->net_salary,
                                            'status' => $payroll->status,
                                        ]) }}; showDetail = true" class="text-gray-400 transition-colors hover:text-primary-600" title="{{ __('View Detail') }}">
                                        <x-heroicon-m-eye class="h-5 w-5" />
                                    </button>
                                    @endif

                                    @if($payroll->status === 'draft')
                                    <button wire:click="publish('{{ $payroll->id }}')" class="text-gray-400 transition-colors hover:text-blue-600" title="{{ __('Publish') }}">
                                        <x-heroicon-m-paper-airplane class="h-5 w-5" />
                                    </button>
                                    @endif

                                    @if($payroll->status === 'published')
                                    <button wire:click="pay('{{ $payroll->id }}')" class="text-gray-400 transition-colors hover:text-green-600" title="{{ __('Mark Paid') }}">
                                        <x-heroicon-m-banknotes class="h-5 w-5" />
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="mb-3 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <p>{{ __('No payrolls found for this period.') }}</p>
                                    <button wire:click="openGenerateModal" class="mt-2 text-primary-600 hover:underline">{{ __('Generate Now') }}</button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200/50 px-6 py-4 dark:border-gray-700/50">
                {{ $payrolls->links() }}
            </div>
        </div>
    </x-admin.page-shell>

    <template x-if="showDetail && detailPayroll">
        <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-900/80" @click="showDetail = false"></div>
                <div class="relative w-full overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:max-w-lg">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="detailPayroll.name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="detailPayroll.job"></p>
                        </div>
                        <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Basic Salary') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="'Rp ' + Number(detailPayroll.basic_salary).toLocaleString('id-ID')"></span>
                        </div>

                        <div>
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Allowances') }}</h4>
                            <template x-for="(amount, name) in detailPayroll.allowances" :key="name">
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400" x-text="name"></span>
                                    <span class="text-green-600 dark:text-green-400" x-text="'Rp ' + Number(amount).toLocaleString('id-ID')"></span>
                                </div>
                            </template>
                            <div class="mt-1 flex justify-between border-t border-gray-100 pt-2 text-sm font-bold dark:border-gray-700">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Total Allowances') }}</span>
                                <span class="text-green-600 dark:text-green-400" x-text="'Rp ' + Number(detailPayroll.total_allowance).toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Overtime Pay') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="'Rp ' + Number(detailPayroll.overtime_pay).toLocaleString('id-ID')"></span>
                        </div>

                        <div>
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-red-500 dark:text-red-400">{{ __('Deductions') }}</h4>
                            <template x-for="(amount, name) in detailPayroll.deductions" :key="name">
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400" x-text="name"></span>
                                    <span class="text-red-500 dark:text-red-400" x-text="'-Rp ' + Number(amount).toLocaleString('id-ID')"></span>
                                </div>
                            </template>
                            <div class="mt-1 flex justify-between border-t border-gray-100 pt-2 text-sm font-bold dark:border-gray-700">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Total Deductions') }}</span>
                                <span class="text-red-500 dark:text-red-400" x-text="'-Rp ' + Number(detailPayroll.total_deduction).toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-green-100 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                            <span class="text-sm font-bold uppercase tracking-wider text-green-800 dark:text-green-300">{{ __('Net Salary') }}</span>
                            <span class="text-xl font-bold text-green-700 dark:text-green-400" x-text="'Rp ' + Number(detailPayroll.net_salary).toLocaleString('id-ID')"></span>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-700/50">
                        <button @click="showDetail = false" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <x-overlays.confirmation-modal wire:model.live="showGenerateModal">
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
            <x-actions.secondary-button wire:click="$toggle('showGenerateModal')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.button class="ml-2" wire:click="generate" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">{{ __('Generate') }}</span>
                <span wire:loading wire:target="generate">{{ __('Processing...') }}</span>
            </x-actions.button>
        </x-slot>
    </x-overlays.confirmation-modal>
</div>
