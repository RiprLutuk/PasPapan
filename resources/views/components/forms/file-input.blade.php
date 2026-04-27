@props(['disabled' => false])

<div
    x-data="{ fileName: '' }"
    {!! $attributes->only('class')->merge([
        'class' => 'flex min-h-11 w-full flex-col gap-2',
    ]) !!}
>
    <input
        x-ref="file"
        type="file"
        class="block min-h-11 w-full cursor-pointer rounded-xl border border-gray-300 bg-white text-sm leading-5 text-gray-700 shadow-sm file:me-3 file:min-h-11 file:cursor-pointer file:border-0 file:bg-primary-100 file:px-4 file:text-sm file:font-semibold file:text-primary-800 hover:file:bg-primary-200 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-primary-900/30 dark:file:text-primary-200 dark:hover:file:bg-primary-900/50 dark:focus:ring-offset-gray-900"
        {{ $disabled ? 'disabled' : '' }}
        x-on:change="fileName = $refs.file.files && $refs.file.files[0] ? $refs.file.files[0].name : ''"
        {!! $attributes->except('class') !!}
    />
    <span class="min-w-0 truncate text-xs text-gray-500 dark:text-gray-400" x-show="fileName" x-cloak x-text="fileName"></span>
</div>
