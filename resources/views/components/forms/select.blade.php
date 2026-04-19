@props(['disabled' => false])

@php
    $requestPath = '/' . ltrim(request()->path(), '/');
    $refererPath = parse_url(request()->headers->get('referer') ?? '', PHP_URL_PATH) ?? '';
    $isAdminContext = str_starts_with($requestPath, '/admin') || str_starts_with($refererPath, '/admin');
@endphp

@if ($isAdminContext)
    <x-forms.tom-select :disabled="$disabled" {{ $attributes }}>
        {{ $slot }}
    </x-forms.tom-select>
@else
    <select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
        'class' =>
            'rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:[color-scheme:dark] dark:focus:border-primary-600 dark:focus:ring-primary-600 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-60 dark:disabled:bg-gray-800 dark:disabled:text-gray-500',
    ]) !!}>
        {{ $slot }}
    </select>
@endif
