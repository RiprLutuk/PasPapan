@php
    $statusClass = match ($status ?? null) {
        'late' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        default => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    };
    $bgClass = "bg-{$bgColor}-100 dark:bg-{$bgColor}-900/30";
    $textClass = "text-{$bgColor}-600 dark:text-{$bgColor}-400";
    $isCompact = $compact ?? false;
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-{{ $isCompact ? '2' : '4' }} sm:p-{{ $isCompact ? '2' : '6' }} shadow dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-center gap-3 mb-{{ $isCompact ? '2' : '3' }}">
        <div class="p-2 {{ $bgClass }} rounded-lg">
            <svg class="w-5 h-5 {{ $textClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
            </svg>
        </div>
        @if (isset($status))
            <span class="px-2 py-1 {{ $statusClass }} text-xs font-semibold rounded-lg">
                {{ $status == 'late' ? __('Late') : __('On Time') }}
            </span>
        @endif
    </div>
    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</p>
    <p class="text-{{ $isCompact ? 'xl' : '2xl' }} font-bold text-gray-900 dark:text-white">{{ $time }}</p>
</div>
