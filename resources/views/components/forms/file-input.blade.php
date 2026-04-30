@props([
    'disabled' => false,
    'buttonLabel' => __('Choose file'),
    'emptyText' => __('No file selected'),
])

@php
    $inputId = $attributes->get('id', 'file-input-' . \Illuminate\Support\Str::uuid()->toString());
@endphp

<div
    x-data="{ fileName: '' }"
    {!! $attributes->only('class')->merge([
        'class' => 'flex min-h-11 w-full flex-col gap-2',
    ]) !!}
>
    <input
        x-ref="file"
        type="file"
        id="{{ $inputId }}"
        class="sr-only"
        {{ $disabled ? 'disabled' : '' }}
        x-on:change="fileName = $refs.file.files && $refs.file.files[0] ? $refs.file.files[0].name : ''"
        {!! $attributes->except(['class', 'id']) !!}
    />

    <label
        for="{{ $inputId }}"
        @class([
            'inline-flex min-h-11 w-full cursor-pointer items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:focus-within:ring-offset-gray-900',
            'cursor-not-allowed opacity-60' => $disabled,
        ])
    >
        {{ $buttonLabel }}
    </label>

    <span class="min-w-0 truncate text-xs text-gray-500 dark:text-gray-400" x-text="fileName || @js($emptyText)"></span>
</div>
