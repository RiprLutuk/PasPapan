<div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Employee') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Schedule') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Requested Shift') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Replacement') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
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
                            <div class="mt-1 max-w-xs truncate text-xs text-gray-500 dark:text-gray-400">{{ $request->reason }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $request->requestedShift->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $request->replacementUser->name ?? __('Not specified') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button wire:click="approveShiftSwap('{{ $request->id }}')"
                                    class="rounded-lg bg-green-50 p-2 text-green-600 transition-colors hover:bg-green-100 hover:text-green-900 dark:bg-green-900/30 dark:hover:bg-green-900/50"
                                    title="{{ __('Approve') }}">
                                    <x-heroicon-o-check class="h-5 w-5" />
                                </button>
                                <button wire:click="rejectShiftSwap('{{ $request->id }}')"
                                    class="rounded-lg bg-red-50 p-2 text-red-600 transition-colors hover:bg-red-100 hover:text-red-900 dark:bg-red-900/30 dark:hover:bg-red-900/50"
                                    title="{{ __('Reject') }}">
                                    <x-heroicon-o-x-mark class="h-5 w-5" />
                                </button>
                            </div>
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
