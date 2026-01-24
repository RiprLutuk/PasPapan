@props(['disabled' => false])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' =>
        'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-lg shadow-sm dark:[color-scheme:dark] py-2.5 text-sm disabled:opacity-60 disabled:bg-gray-50 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-500 read-only:opacity-60 read-only:bg-gray-50 read-only:cursor-not-allowed dark:read-only:bg-gray-800 dark:read-only:text-gray-500',
]) !!}>
  {{ $slot }}
</select>
