<div class="hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
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
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $request->effectiveScheduleDate()?->translatedFormat('d M Y') ?? '-' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Current') }}: {{ $request->currentShift->name ?? __('No current schedule') }}</div>
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
                <span class="shrink-0 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 dark:bg-sky-900 dark:text-sky-200">
                    {{ __('Pending') }}
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

            <div class="mt-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Replacement') }}</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $request->replacementUser->name ?? __('Not specified') }}</p>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                <button wire:click="rejectShiftSwap('{{ $request->id }}')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                    <x-heroicon-o-x-mark class="h-4 w-4" />
                    {{ __('Reject') }}
                </button>
                <button wire:click="approveShiftSwap('{{ $request->id }}')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-50 px-3 py-2.5 text-sm font-medium text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                    <x-heroicon-o-check class="h-4 w-4" />
                    {{ __('Approve') }}
                </button>
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
