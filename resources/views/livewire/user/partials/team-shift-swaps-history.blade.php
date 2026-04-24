<div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
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
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $request->schedule?->date?->translatedFormat('d M Y') ?? '-' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Current') }}: {{ $request->currentShift->name ?? '-' }}</div>
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
