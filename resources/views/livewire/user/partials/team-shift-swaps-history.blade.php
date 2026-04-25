<div class="hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Employee') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Schedule') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Requested Shift') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Reason') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse ($shiftSwapRequests as $request)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img class="h-10 w-10 rounded-full object-cover" src="{{ $request->user->profile_photo_url }}" alt="{{ $request->user->name }}">
                                <div class="ml-4 min-w-0">
                                    <div class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $request->user->name }}</div>
                                    <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $request->user->jobTitle->name ?? __('N/A') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $request->effectiveScheduleDate()?->translatedFormat('d M Y') ?? '-' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Current') }}: {{ $request->currentShift->name ?? __('No current schedule') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $request->requestedShift->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $request->statusLabel() }}
                            </span>
                            @if ($request->reviewer)
                                <div class="mt-1 text-[10px] text-gray-400">{{ __('by') }} {{ $request->reviewer->name }}</div>
                            @endif
                            @if ($request->rejection_note)
                                <div class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $request->rejection_note }}</div>
                            @endif
                        </td>
                        <td class="max-w-sm px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            <div class="line-clamp-2">{{ $request->reason }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No shift swap requests found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($shiftSwapRequests->hasPages())
        <div class="px-4 py-3">{{ $shiftSwapRequests->links() }}</div>
    @endif
</div>

<div class="space-y-4 md:hidden">
    @forelse ($shiftSwapRequests as $request)
        <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-3">
                <div class="flex min-w-0 flex-1 items-center">
                    <img class="h-10 w-10 rounded-full object-cover" src="{{ $request->user->profile_photo_url }}" alt="{{ $request->user->name }}">
                    <div class="ml-3 min-w-0">
                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $request->user->name }}</div>
                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $request->user->jobTitle->name ?? __('N/A') }}</div>
                    </div>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                    {{ $request->statusLabel() }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Schedule') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $request->effectiveScheduleDate()?->translatedFormat('d M Y') ?? '-' }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Current') }}: {{ $request->currentShift->name ?? __('No current schedule') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Requested Shift') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $request->requestedShift->name ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Reason') }}</p>
                <div class="mt-1">{{ $request->reason }}</div>
            </div>

            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                @if ($request->reviewer)
                    <div>{{ __('by') }} {{ $request->reviewer->name }}</div>
                @endif
                @if ($request->rejection_note)
                    <div class="mt-2 rounded-xl bg-red-50 p-3 text-red-600 dark:bg-red-900/20 dark:text-red-300">{{ $request->rejection_note }}</div>
                @endif
            </div>
        </article>
    @empty
        <div class="rounded-xl border border-gray-100 bg-white p-8 text-center text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            {{ __('No shift swap requests found') }}
        </div>
    @endforelse

    @if ($shiftSwapRequests->hasPages())
        <div class="px-4 py-3">{{ $shiftSwapRequests->links() }}</div>
    @endif
</div>
