@props(['disabled' => false])

@php
    $type = (string) $attributes->get('type', 'text');
    $isDatePicker = in_array($type, ['date', 'time', 'datetime-local'], true);
    $isNumberLike = $type === 'number';
    $isSearch = $type === 'search';
    $renderType = ($isDatePicker || $isNumberLike || $isSearch) ? 'text' : $type;
    $extraAttributes = [];

    if ($isDatePicker) {
        $extraAttributes['data-ui-picker'] = $type === 'datetime-local' ? 'datetime' : $type;
        $extraAttributes['autocomplete'] = 'off';
    }

    if ($isNumberLike) {
        $extraAttributes['inputmode'] = 'decimal';
    }

    if ($isSearch) {
        $extraAttributes['enterkeyhint'] = 'search';
    }
@endphp

<input
    type="{{ $renderType }}"
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->except('type')->merge($extraAttributes)->merge([
        'class' =>
            'h-11 min-h-11 rounded-xl border-gray-300 bg-white px-4 py-2.5 text-sm leading-5 text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:[color-scheme:dark] dark:focus:border-primary-600 dark:focus:ring-primary-600 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-60 read-only:cursor-not-allowed read-only:bg-gray-50 read-only:opacity-60 dark:disabled:bg-gray-800 dark:disabled:text-gray-400 dark:read-only:bg-gray-800 dark:read-only:text-gray-400',
    ]) !!}
>
