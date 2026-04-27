@props(['disabled' => false])

@php
    $inputId = $attributes->get('id', 'file-input');
@endphp

<div
    x-data="{ fileName: '' }"
    {!! $attributes->only('class')->merge([
        'class' => 'flex h-11 min-h-11 w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm leading-5 text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
    ]) !!}
>
    <input
        x-ref="file"
        type="file"
        class="sr-only"
        {{ $disabled ? 'disabled' : '' }}
        x-on:change="fileName = $refs.file.files && $refs.file.files[0] ? $refs.file.files[0].name : ''"
        {!! $attributes->except('class') !!}
    />

    <label
        for="{{ $inputId }}"
        class="inline-flex h-8 shrink-0 items-center justify-center rounded-xl bg-primary-100 px-3 text-sm font-semibold text-primary-800 transition hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-primary-900/30 dark:text-primary-200 dark:hover:bg-primary-900/50"
    >
        {{ __('Choose File') }}
    </label>

    <span class="min-w-0 flex-1 truncate text-sm text-gray-500 dark:text-gray-400" x-text="fileName || @js(__('No file selected'))"></span>
</div>
