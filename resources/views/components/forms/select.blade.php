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
    <x-user.tom-select-user :disabled="$disabled" dropdown-parent="self" {{ $attributes }}>
        {{ $slot }}
    </x-user.tom-select-user>
@endif
