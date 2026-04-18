@props(['disabled' => false])

<input
    type="file"
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-500 shadow-sm file:mr-4 file:rounded-full file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-primary-900/20 dark:file:text-primary-300',
    ]) !!}
/>
