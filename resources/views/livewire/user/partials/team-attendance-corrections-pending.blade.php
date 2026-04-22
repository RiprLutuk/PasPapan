<div class="hidden md:block rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Employee') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Request') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Requested Change') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Submitted') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse ($attendanceCorrections as $correction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="{{ $correction->user->profile_photo_url }}"
                                        alt="{{ $correction->user->name }}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $correction->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $correction->user->jobTitle->name ?? __('N/A') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-200">
                            <div class="font-semibold">{{ $correction->requestTypeLabel() }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $correction->attendance_date->translatedFormat('d M Y') }}</div>
                            <div class="mt-1 max-w-xs truncate text-xs text-gray-500 dark:text-gray-400">{{ $correction->reason }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-200">
                            <div class="space-y-1 text-xs">
                                @if ($correction->requested_time_in)
                                    <div>{{ __('Check in') }}: {{ $correction->requested_time_in->translatedFormat('d M Y H:i') }}</div>
                                @endif
                                @if ($correction->requested_time_out)
                                    <div>{{ __('Check out') }}: {{ $correction->requested_time_out->translatedFormat('d M Y H:i') }}</div>
                                @endif
                                @if ($correction->requestedShift)
                                    <div>{{ __('Shift') }}: {{ $correction->requestedShift->name }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $correction->created_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <button wire:click="approveAttendanceCorrection('{{ $correction->id }}')"
                                    class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors"
                                    title="{{ __('Approve') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                                <button wire:click="rejectAttendanceCorrection('{{ $correction->id }}')"
                                    class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors"
                                    title="{{ __('Reject') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No attendance correction requests found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="space-y-4 md:hidden">
    @forelse ($attendanceCorrections as $correction)
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-3">
                <div class="flex min-w-0 flex-1 items-center">
                    <img class="h-10 w-10 rounded-full object-cover"
                        src="{{ $correction->user->profile_photo_url }}"
                        alt="{{ $correction->user->name }}">
                    <div class="ml-3 min-w-0">
                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $correction->user->name }}</div>
                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $correction->user->jobTitle->name ?? __('N/A') }}</div>
                    </div>
                </div>
                <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                    {{ $correction->requestTypeLabel() }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Attendance Date') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $correction->attendance_date->translatedFormat('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Submitted') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $correction->created_at->diffForHumans() }}</p>
                </div>
            </div>

            <div class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-700/50 dark:text-gray-300">
                {{ $correction->reason }}
            </div>

            <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                @if ($correction->requested_time_in)
                    <div>{{ __('Check in') }}: {{ $correction->requested_time_in->translatedFormat('d M Y H:i') }}</div>
                @endif
                @if ($correction->requested_time_out)
                    <div>{{ __('Check out') }}: {{ $correction->requested_time_out->translatedFormat('d M Y H:i') }}</div>
                @endif
                @if ($correction->requestedShift)
                    <div>{{ __('Shift') }}: {{ $correction->requestedShift->name }}</div>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                <button wire:click="rejectAttendanceCorrection('{{ $correction->id }}')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{ __('Reject') }}
                </button>
                <button wire:click="approveAttendanceCorrection('{{ $correction->id }}')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-50 px-3 py-2.5 text-sm font-medium text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ __('Approve') }}
                </button>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-gray-100 bg-white p-8 text-center text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            {{ __('No attendance correction requests found') }}
        </div>
    @endforelse
</div>

<div class="px-4 py-3">
    {{ $attendanceCorrections->links() }}
</div>
