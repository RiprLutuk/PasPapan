<x-admin.page-shell
    :title="__('User Activity Logs')"
    :description="__('Review login history and audit trails across admin-managed users.')"
>
    <x-slot name="toolbar">
        <x-admin.page-tools
            :title="__('Filter Audit Logs')"
            :description="__('Search user activity and tighten the audit window with a start and end date.')"
            grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5"
        >
            <x-slot name="summary">
                <div class="rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ trans_choice(':count log listed|:count logs listed', $logs->total(), ['count' => $logs->total()]) }}
                </div>
            </x-slot>

            <x-slot name="actions">
                @if(\App\Helpers\Editions::auditLocked())
                    <x-actions.button
                        href="{{ route('admin.activity-logs.export', ['search' => $search, 'start_date' => $dateStart ?: null, 'end_date' => $dateEnd ?: null]) }}"
                        target="_system"
                        rel="noopener noreferrer"
                        x-on:click.prevent="$dispatch('feature-lock', { title: 'Audit Export Locked', message: 'Audit Logs Export is an Enterprise Feature 🔒. Please Upgrade.' })"
                        variant="success"
                    >
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Export Excel') }} 🔒
                    </x-actions.button>
                @else
                    <x-actions.button
                        href="{{ route('admin.activity-logs.export', ['search' => $search, 'start_date' => $dateStart ?: null, 'end_date' => $dateEnd ?: null]) }}"
                        target="_system"
                        rel="noopener noreferrer"
                        variant="success"
                    >
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Export Excel') }}
                    </x-actions.button>
                @endif
            </x-slot>

            <div class="lg:col-span-3">
                <label for="activity-log-search" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Search audit logs') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <x-forms.input id="activity-log-search" type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search user, action, or detail...') }}"
                        class="block w-full pl-10 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:placeholder-gray-400" />
                </div>
            </div>

            <div class="lg:col-span-1">
                <label for="activity-log-start-date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Start Date') }}</label>
                <x-forms.input id="activity-log-start-date" type="date" wire:model.live="dateStart"
                    class="block w-full dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300" />
            </div>

            <div class="lg:col-span-1">
                <label for="activity-log-end-date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('End Date') }}</label>
                <x-forms.input id="activity-log-end-date" type="date" wire:model.live="dateEnd"
                    class="block w-full dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300" />
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div wire:poll.5s class="mb-6">
        <x-admin.import-export-run-list
            :runs="$recentExportRuns"
            :title="__('Audit log export jobs')"
            :description="__('Activity log exports run in the background so large audit windows do not block the page.')"
            :empty="__('No audit log export jobs yet.')"
        />
    </div>

    <x-admin.panel class="ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('User') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Action') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('IP Address') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Time') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @forelse($logs as $log)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 flex-shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs dark:bg-blue-900/30 dark:text-blue-400">
                                                {{ substr($log->user->name ?? '?', 0, 1) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->user->name ?? __('Unknown') }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->user->nip ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $log->action }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->description }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-700/30 dark:text-gray-400 dark:ring-gray-400/20">
                                            {{ $log->ip_address ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col">
                                            <span>{{ $log->created_at->diffForHumans() }}</span>
                                            <span class="text-xs text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <x-admin.empty-state :title="__('No activity logs found.')" class="border-0 bg-transparent p-0 shadow-none dark:bg-transparent">
                                            <x-slot name="icon">
                                                <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </x-slot>
                                        </x-admin.empty-state>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
        <div class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-4 dark:border-gray-700/60 dark:bg-gray-900/40">
            {{ $logs->links() }}
        </div>
    </x-admin.panel>
</x-admin.page-shell>
