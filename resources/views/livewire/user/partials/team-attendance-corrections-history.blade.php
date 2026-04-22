<div class="hidden md:block rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Employee') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Request') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Processed By') }}</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $correction->status === 'approved'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                    : ($correction->status === 'rejected'
                                        ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                        : 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200') }}">
                                {{ $correction->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                            @if ($correction->status === 'pending_admin')
                                <span>{{ $correction->headApprover?->name ?? __('Supervisor') }}</span>
                            @else
                                <span>{{ $correction->reviewer?->name ?? __('System') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No attendance correction history found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="space-y-4 md:hidden">
    @forelse ($attendanceCorrections as $correction)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <img class="h-10 w-10 rounded-full object-cover"
                        src="{{ $correction->user->profile_photo_url }}"
                        alt="{{ $correction->user->name }}">
                    <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $correction->user->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $correction->user->jobTitle->name ?? __('N/A') }}</div>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full
                    {{ $correction->status === 'approved'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                        : ($correction->status === 'rejected'
                            ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                            : 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200') }}">
                    {{ $correction->statusLabel() }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Attendance Date') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $correction->attendance_date->translatedFormat('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Processed By') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $correction->status === 'pending_admin' ? ($correction->headApprover?->name ?? __('Supervisor')) : ($correction->reviewer?->name ?? __('System')) }}
                    </p>
                </div>
            </div>

            <div class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-700/50 dark:text-gray-300">
                <div class="font-semibold">{{ $correction->requestTypeLabel() }}</div>
                <div class="mt-1">{{ $correction->reason }}</div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
            {{ __('No attendance correction history found') }}
        </div>
    @endforelse
</div>

<div class="px-4 py-3">
    {{ $attendanceCorrections->links() }}
</div>
