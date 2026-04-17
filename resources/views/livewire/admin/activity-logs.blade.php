<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('User Activity Logs') }}
        </h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10 2xl:px-10">
        <div class="w-full">
            <!-- Filter Section -->
            <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <!-- Search & Date Filters -->
                    <div class="flex flex-1 flex-col gap-4 md:flex-row">
                        <!-- Search -->
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Search') }}</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search activity...') }}"
                                    class="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500" />
                            </div>
                        </div>

                        <!-- Date Start -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Start Date') }}</label>
                            <input type="date" wire:model.live="dateStart"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300" />
                        </div>

                        <!-- Date End -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('End Date') }}</label>
                            <input type="date" wire:model.live="dateEnd"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300" />
                        </div>
                    </div>

                    <!-- Export Button -->
                     <div class="flex-none">
                         <a href="{{ route('admin.activity-logs.export', ['search' => $search, 'start_date' => $dateStart ?: null, 'end_date' => $dateEnd ?: null]) }}" target="_system" 
                            @if(\App\Helpers\Editions::auditLocked()) 
                                @click.prevent="$dispatch('feature-lock', { title: 'Audit Export Locked', message: 'Audit Logs Export is an Enterprise Feature 🔒. Please Upgrade.' })"
                            @endif
                            class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                             <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ __('Export Excel') }}
                            @if(\App\Helpers\Editions::auditLocked()) 🔒 @endif
                        </a>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
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
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="mt-4 text-sm font-medium">{{ __('No activity logs found.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="bg-gray-50 px-6 py-4 dark:bg-gray-700/50">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
