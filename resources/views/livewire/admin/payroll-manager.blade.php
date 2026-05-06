<div x-data="{ showDetail: false, detailPayroll: null }">
    <x-admin.page-shell :title="__('Payroll Management')" :description="__('Generate and manage employee payments.')">
        <x-slot name="actions">
            <x-actions.button type="button" wire:click="openGenerateModal">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                    </path>
                </svg>
                {{ __('Generate Payroll') }}
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools>

                <div class="md:col-span-2 xl:col-span-5">
                    <x-forms.label for="payroll-search" value="{{ __('Search payroll records') }}"
                        class="mb-1.5 block" />
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        <x-forms.input id="payroll-search" type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search employee, NIP, or job title...') }}" class="w-full pl-11" />
                    </div>
                </div>

                <div class="xl:col-span-2">
                    <x-forms.label for="payroll-month" value="{{ __('Month') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-month" wire:model.live="month" class="w-full">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}</option>
                        @endforeach
                    </x-forms.select>
                </div>

                <div class="xl:col-span-2">
                    <x-forms.label for="payroll-year" value="{{ __('Year') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-year" wire:model.live="year" class="w-full">
                        @foreach (range(date('Y') - 1, date('Y') + 1) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </x-forms.select>
                </div>

                <div class="xl:col-span-3">
                    <x-forms.label for="payroll-status-filter" value="{{ __('Status') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-status-filter" wire:model.live="statusFilter" class="w-full">
                        <option value="all">{{ __('All statuses') }}</option>
                        <option value="draft">{{ __('Draft') }}</option>
                        <option value="published">{{ __('Published') }}</option>
                        <option value="paid">{{ __('Paid') }}</option>
                    </x-forms.select>
                </div>
            </x-admin.page-tools>
        </x-slot>

        @php
            $selectedPayrollActionState = $this->selectedPayrollActionState;
        @endphp

        @if (count($selectedPayrolls) > 0 && $selectedPayrollActionState['has_actions'])
            <x-admin.alert tone="primary" class="mb-4 flex items-center gap-3">
                <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                    {{ count($selectedPayrolls) }} {{ __('selected') }}
                </span>
                <div class="ml-auto flex items-center gap-2">
                    @if ($selectedPayrollActionState['can_publish'])
                        <x-actions.button type="button" wire:click="bulkPublish"
                            wire:confirm="{{ __('Publish all selected draft payrolls?') }}" size="sm">
                            <x-heroicon-m-paper-airplane class="h-4 w-4" />
                            {{ __('Publish Selected') }}
                        </x-actions.button>
                    @endif

                    @if ($selectedPayrollActionState['can_pay'])
                        <x-actions.button type="button" wire:click="bulkPay"
                            wire:confirm="{{ __('Mark all selected published payrolls as paid?') }}" variant="success"
                            size="sm">
                            <x-heroicon-m-banknotes class="h-4 w-4" />
                            {{ __('Pay Selected') }}
                        </x-actions.button>
                    @endif
                </div>
            </x-admin.alert>
        @endif

        @php
            $allPayrolls = $payrolls->getCollection();
            $totalGross = $allPayrolls->sum('basic_salary') + $allPayrolls->sum('overtime_pay') + $allPayrolls->sum('total_allowance');
            $totalNet = $allPayrolls->sum('net_salary');
            $totalDeductions = $allPayrolls->sum('total_deduction');
            $draftCount = $allPayrolls->where('status', 'draft')->count();
            $publishedCount = $allPayrolls->where('status', 'published')->count();
            $paidCount = $allPayrolls->where('status', 'paid')->count();
        @endphp

        <dl class="flex flex-wrap gap-2 mb-4" role="region" aria-label="{{ __('Payroll Summary') }}">
            <div class="rounded-xl border border-slate-200/70 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ __('Gross') }}</dt>
                <dd class="text-sm font-bold text-slate-900 dark:text-white">Rp {{ number_format($totalGross, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-xl border border-emerald-300/70 bg-emerald-50/60 px-3 py-2 dark:border-emerald-800 dark:bg-emerald-900/15">
                <dt class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Net') }}</dt>
                <dd class="text-sm font-bold text-emerald-800 dark:text-emerald-200">Rp {{ number_format($totalNet, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-xl border border-rose-300/70 bg-rose-50/60 px-3 py-2 dark:border-rose-800 dark:bg-rose-900/15">
                <dt class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">{{ __('Deductions') }}</dt>
                <dd class="text-sm font-bold text-rose-800 dark:text-rose-200">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-xl border border-slate-200/70 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80 flex items-center gap-1.5">
                <dt class="sr-only">{{ __('Status') }}</dt>
                <dd class="flex items-center gap-1.5">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ $draftCount }} {{ __('Draft') }}</span>
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">{{ $publishedCount }} {{ __('Pub') }}</span>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">{{ $paidCount }} {{ __('Paid') }}</span>
                </dd>
            </div>
            <div class="rounded-xl border border-slate-200/70 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ __('Employees') }}</dt>
                <dd class="text-sm font-bold text-slate-900 dark:text-white">{{ $payrolls->total() }}</dd>
            </div>
        </dl>

        <x-admin.panel>
            <div class="space-y-3 p-4 lg:hidden">
                @forelse ($payrolls as $payroll)
                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start gap-3">
                            <x-forms.checkbox wire:model.live="selectedPayrolls" value="{{ $payroll->id }}" />
                            <img class="h-10 w-10 rounded-full object-cover" src="{{ $payroll->user?->profile_photo_url }}" alt="{{ $payroll->user?->name ?? __('Unknown User') }}">
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $payroll->user?->name ?? __('Unknown User') }}</h3>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $payroll->user?->jobTitle->name ?? '-' }}</p>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize
                                @if ($payroll->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                @elseif($payroll->status === 'published') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300 @endif">
                                {{ $payroll->status }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Basic Salary') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Overtime') }}</dt>
                                <dd class="mt-1 text-gray-700 dark:text-gray-300">Rp {{ number_format($payroll->overtime_pay, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Deductions') }}</dt>
                                <dd class="mt-1 text-red-500 dark:text-red-400">-Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Net Salary') }}</dt>
                                <dd class="mt-1 font-bold text-gray-900 dark:text-white">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex items-center justify-end gap-1">
                            @if ($this->canManage)
                                <x-actions.icon-button
                                    @click="detailPayroll = {{ json_encode([
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
                                    ]) }}; showDetail = true"
                                    variant="primary"
                                    label="{{ __('View payroll detail') }}: {{ $payroll->user?->name }}">
                                    <x-heroicon-m-eye class="h-5 w-5" />
                                </x-actions.icon-button>
                            @endif

                            @if ($this->canManage && $payroll->status === 'draft')
                                <x-actions.icon-button wire:click="publish('{{ $payroll->id }}')" variant="primary" label="{{ __('Publish payroll') }}: {{ $payroll->user?->name }}">
                                    <x-heroicon-m-paper-airplane class="h-5 w-5" />
                                </x-actions.icon-button>
                            @endif

                            @if ($this->canManage && $payroll->status === 'published')
                                <x-actions.icon-button wire:click="pay('{{ $payroll->id }}')" variant="success" label="{{ __('Mark payroll paid') }}: {{ $payroll->user?->name }}">
                                    <x-heroicon-m-banknotes class="h-5 w-5" />
                                </x-actions.icon-button>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-admin.empty-state :title="__('No payrolls found for this period.')" class="border border-dashed border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                        <x-slot name="icon">
                            <x-heroicon-o-calculator class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                        </x-slot>
                        <x-actions.button type="button" wire:click="openGenerateModal" variant="ghost" size="sm" class="mt-2">{{ __('Generate Now') }}</x-actions.button>
                    </x-admin.empty-state>
                @endforelse
            </div>

            <div class="hidden lg:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="w-10 px-4 py-3 text-center">
                                <x-forms.checkbox wire:model.live="selectAll" />
                            </th>
                            <th scope="col"
                                class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Employee') }}</th>
                            <th scope="col"
                                class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Basic Salary') }}</th>
                            <th scope="col"
                                class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Overtime') }}</th>
                            <th scope="col"
                                class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Deductions') }}</th>
                            <th scope="col"
                                class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Net Salary') }}</th>
                            <th scope="col"
                                class="px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Status') }}</th>
                            <th scope="col"
                                class="px-4 py-2.5 text-end text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-transparent dark:divide-gray-700">
                        @forelse ($payrolls as $payroll)
                            <tr class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-4 text-center">
                                    <x-forms.checkbox wire:model.live="selectedPayrolls" value="{{ $payroll->id }}" />
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                src="{{ $payroll->user?->profile_photo_url }}"
                                                alt="{{ $payroll->user?->name ?? __('Unknown User') }}">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $payroll->user?->name ?? __('Unknown User') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $payroll->user?->jobTitle->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-300">
                                    Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-300">
                                    Rp {{ number_format($payroll->overtime_pay, 0, ',', '.') }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right text-sm text-red-500 dark:text-red-400">
                                    -Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize
                                        @if ($payroll->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @elseif($payroll->status === 'published') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300 @endif">
                                        {{ $payroll->status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($this->canManage)
                                            <x-actions.icon-button
                                                @click="detailPayroll = {{ json_encode([
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
                                                ]) }}; showDetail = true"
                                                variant="primary"
                                                label="{{ __('View payroll detail') }}: {{ $payroll->user?->name }}">
                                                <x-heroicon-m-eye class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endif

                                        @if ($this->canManage && $payroll->status === 'draft')
                                            <x-actions.icon-button wire:click="publish('{{ $payroll->id }}')"
                                                variant="primary"
                                                label="{{ __('Publish payroll') }}: {{ $payroll->user?->name }}">
                                                <x-heroicon-m-paper-airplane class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endif

                                        @if ($this->canManage && $payroll->status === 'published')
                                            <x-actions.icon-button wire:click="pay('{{ $payroll->id }}')"
                                                variant="success"
                                                label="{{ __('Mark payroll paid') }}: {{ $payroll->user?->name }}">
                                                <x-heroicon-m-banknotes class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="mb-3 h-12 w-12 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <p>{{ __('No payrolls found for this period.') }}</p>
                                        <x-actions.button type="button" wire:click="openGenerateModal"
                                            variant="ghost" size="sm"
                                            class="mt-2">{{ __('Generate Now') }}</x-actions.button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200/50 px-4 py-3 dark:border-gray-700/50">
                {{ $payrolls->links() }}
            </div>
        </x-admin.panel>
    </x-admin.page-shell>

    <template x-teleport="body">
        <template x-if="showDetail && detailPayroll">
            <div class="fixed inset-0 z-[90] overflow-y-auto" x-transition>
                <div class="flex min-h-[100dvh] items-start justify-center px-4 py-[calc(1rem+env(safe-area-inset-top))] text-center sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]">
                    <div class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-900/80"
                        @click="showDetail = false"></div>
                    <div class="relative w-full overflow-hidden rounded-xl bg-white text-left shadow-md transition-all dark:bg-gray-800 sm:my-8 sm:max-w-lg"
                        style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));"
                        role="dialog" aria-modal="true" aria-labelledby="payroll-detail-title">
                        <div
                            class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <div>
                                <h3 id="payroll-detail-title" class="text-lg font-bold text-gray-900 dark:text-white"
                                    x-text="detailPayroll.name"></h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="detailPayroll.job"></p>
                            </div>
                            <x-actions.icon-button type="button" @click="showDetail = false" variant="neutral"
                                label="{{ __('Close payroll detail') }}">
                                <x-heroicon-o-x-mark class="h-5 w-5" />
                            </x-actions.icon-button>
                        </div>
                        <div class="space-y-4 overflow-y-auto px-4 py-3" style="max-height: calc(100dvh - 12rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('Basic Salary') }}</span>
                                <span class="font-medium text-gray-900 dark:text-white"
                                    x-text="'Rp ' + Number(detailPayroll.basic_salary).toLocaleString('id-ID')"></span>
                            </div>

                        <div>
                            <h4
                                class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Allowances') }}</h4>
                            <template x-for="(amount, name) in detailPayroll.allowances" :key="name">
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400" x-text="name"></span>
                                    <span class="text-green-600 dark:text-green-400"
                                        x-text="'Rp ' + Number(amount).toLocaleString('id-ID')"></span>
                                </div>
                            </template>
                            <div
                                class="mt-1 flex justify-between border-t border-gray-100 pt-2 text-sm font-bold dark:border-gray-700">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Total Allowances') }}</span>
                                <span class="text-green-600 dark:text-green-400"
                                    x-text="'Rp ' + Number(detailPayroll.total_allowance).toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Overtime Pay') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white"
                                x-text="'Rp ' + Number(detailPayroll.overtime_pay).toLocaleString('id-ID')"></span>
                        </div>

                        <div>
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-red-500 dark:text-red-400">
                                {{ __('Deductions') }}</h4>
                            <template x-for="(amount, name) in detailPayroll.deductions" :key="name">
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400" x-text="name"></span>
                                    <span class="text-red-500 dark:text-red-400"
                                        x-text="'-Rp ' + Number(amount).toLocaleString('id-ID')"></span>
                                </div>
                            </template>
                            <div
                                class="mt-1 flex justify-between border-t border-gray-100 pt-2 text-sm font-bold dark:border-gray-700">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Total Deductions') }}</span>
                                <span class="text-red-500 dark:text-red-400"
                                    x-text="'-Rp ' + Number(detailPayroll.total_deduction).toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between rounded-xl border border-green-100 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                            <span
                                class="text-sm font-bold uppercase tracking-wider text-green-800 dark:text-green-300">{{ __('Net Salary') }}</span>
                            <span class="text-xl font-bold text-green-700 dark:text-green-400"
                                x-text="'Rp ' + Number(detailPayroll.net_salary).toLocaleString('id-ID')"></span>
                        </div>
                        </div>
                        <div
                            class="flex justify-end border-t border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-700/50">
                            <x-actions.secondary-button type="button" @click="showDetail = false">
                                {{ __('Close') }}
                            </x-actions.secondary-button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </template>

    <x-overlays.confirmation-modal wire:model.live="showGenerateModal">
        <x-slot name="title">
            {{ __('Generate Payroll') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to generate payroll for') }}
            <strong>{{ \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') }}
                {{ $year }}</strong>?
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
