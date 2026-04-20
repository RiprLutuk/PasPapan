@props([
    'runs' => [],
    'title' => __('Recent background jobs'),
    'description' => __('Track progress and download completed files from here.'),
    'empty' => __('No background jobs yet.'),
])

<x-admin.panel class="ring-1 ring-gray-950/5 dark:ring-white/10">
    <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/20">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                    {{ $title }}
                </h4>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $description }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ count($runs) }} {{ __('jobs') }}
            </span>
        </div>
    </div>

    @if (!empty($runs))
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($runs as $run)
                @php
                    $statusClass = match ($run['status']) {
                        'completed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                        'failed' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
                        'running' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
                        default => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                    };
                @endphp

                <div class="space-y-4 px-6 py-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $run['label'] }}
                                </p>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ __(ucfirst($run['status'])) }}
                                </span>
                            </div>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                <span>#{{ $run['id'] }}</span>
                                <span>{{ __('Updated') }} {{ $run['updated_at_human'] ?? '-' }}</span>
                                @if (!empty($run['file_name']))
                                    <span>{{ $run['file_name'] }}</span>
                                @endif
                                @if (!empty($run['size_human']))
                                    <span>{{ $run['size_human'] }}</span>
                                @endif
                            </div>
                        </div>

                        @if (!empty($run['download_url']))
                            <x-actions.button href="{{ $run['download_url'] }}" target="_system" variant="soft-primary" size="sm">
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                {{ __('Download') }}
                            </x-actions.button>
                        @endif
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
                            <span>{{ __('Progress') }}</span>
                            <span>{{ $run['progress_percentage'] }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-2 rounded-full transition-all duration-300 {{ $run['status'] === 'failed' ? 'bg-rose-500' : ($run['status'] === 'completed' ? 'bg-emerald-500' : 'bg-primary-600') }}"
                                style="width: {{ max(4, (int) $run['progress_percentage']) }}%;"
                            ></div>
                        </div>
                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ number_format((int) $run['processed_rows']) }} / {{ number_format((int) ($run['total_rows'] ?? 0)) }} {{ __('rows processed') }}
                        </div>
                    </div>

                    @if (!empty($run['error_message']))
                        <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/30 dark:bg-rose-900/10 dark:text-rose-300">
                            {{ $run['error_message'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="px-6 py-8 text-sm text-slate-500 dark:text-slate-400">
            {{ $empty }}
        </div>
    @endif
</x-admin.panel>
