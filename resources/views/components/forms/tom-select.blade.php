@props([
    'options' => [],
    'placeholder' => 'Select an option',
    'selected' => null,
    'submitOnChange' => false,
    'disabled' => false,
    'dropdownDirection' => 'auto',
])

@php
    $requestPath = '/' . ltrim(request()->path(), '/');
    $refererPath = parse_url(request()->headers->get('referer') ?? '', PHP_URL_PATH) ?? '';
    $isAdminContext = str_starts_with($requestPath, '/admin') || str_starts_with($refererPath, '/admin');
    $wireModelDirective = $attributes->wire('model');
    $wireModel = $wireModelDirective->value();
    $livewireSetLive = $wireModel && $wireModelDirective->hasModifier('live');
    $alpineModelAttributes = $attributes->whereStartsWith('x-model');
    $wrapperClass = trim(collect([
        $attributes->get('class') ? null : 'w-full',
        $isAdminContext ? 'ts-wrapper-admin' : null,
        $attributes->get('class'),
    ])->filter()->implode(' '));
@endphp

@once
    <style>
        .ts-control {
            background-color: #ffffff;
            border: 0 !important;
            box-shadow: inset 0 0 0 1px #d1d5db;
            color: #111827;
            border-radius: 0.5rem;
            padding: 0.375rem 2.5rem 0.375rem 0.75rem;
            font-size: 0.875rem;
            height: 42px;
            display: flex !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            overflow: hidden;
        }

        .ts-wrapper-admin.ts-wrapper {
            min-height: 44px !important;
            height: 44px !important;
        }

        .ts-wrapper-admin .ts-control {
            height: 44px !important;
            min-height: 44px !important;
            padding: 0 2.5rem 0 0.75rem !important;
            line-height: 1.25rem !important;
        }

        .ts-wrapper-admin .ts-control .item,
        .ts-wrapper-admin .ts-control > input {
            line-height: 1.25rem !important;
            height: 1.25rem !important;
        }

        .ts-control .item,
        .ts-control .option,
        .ts-control>input {
            line-height: 1.25rem !important;
        }

        .ts-control>input {
            flex: 1 1 auto;
            display: inline-block !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 0 0 0.25rem !important;
            width: 1ch !important;
            max-width: 100% !important;
            min-width: 1ch !important;
            height: auto !important;
            line-height: 1.25rem !important;
            min-height: 0 !important;
            vertical-align: middle !important;
        }

        .ts-control .item {
            flex: 0 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ts-wrapper.single:not(.has-items) .ts-control>input {
            margin-left: 0 !important;
        }

        .ts-wrapper.focus .ts-control {
            box-shadow: inset 0 0 0 2px #6ab45b !important;
        }

        /* Dropdown */
        .ts-dropdown {
            background-color: #ffffff !important;
            border-color: #e5e7eb;
            color: #111827;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            z-index: 99999 !important;
            opacity: 1 !important;
        }

        .ts-dropdown .ts-dropdown-content {
            background-color: #ffffff !important;
        }

        .ts-dropdown .option {
            padding: 0.5rem 0.75rem;
        }

        .ts-dropdown .active {
            background-color: #f3f4f6;
            /* gray-100 */
            color: #111827;
        }

        /* Dark Mode - Root selector to ensure specificity */
        .dark .ts-control {
            background-color: #111827 !important;
            /* bg-gray-900 */
            box-shadow: inset 0 0 0 1px #374151 !important;
            /* ring-gray-700 */
            color: #d1d5db !important;
            /* text-gray-300 */
        }

        .dark .ts-control input {
            color: #d1d5db !important;
            /* text-gray-300 */
        }

        .dark .ts-wrapper.focus .ts-control {
            box-shadow: inset 0 0 0 2px #6ab45b !important;
            /* primary-500 */
        }

        .dark .ts-dropdown {
            background-color: #1f2937 !important;
            /* bg-gray-800 */
            border-color: #374151 !important;
            /* border-gray-700 */
            color: #d1d5db !important;
            /* text-gray-300 */
        }

        .dark .ts-dropdown .ts-dropdown-content {
            background-color: #1f2937 !important;
        }

        .dark .ts-dropdown .option {
            color: #d1d5db !important;
        }

        .dark .ts-dropdown .active {
            background-color: #374151 !important;
            /* bg-gray-700 */
            color: #ffffff !important;
        }

        .dark .ts-dropdown .option:hover,
        .dark .ts-dropdown .option.active {
            background-color: #374151 !important;
            color: #ffffff !important;
        }

        /* Input placeholder color in dark mode */
        .dark .ts-control ::placeholder {
            color: #9ca3af !important;
            /* gray-400 */
        }

        /* Chevron Arrow */
        .ts-wrapper {
            position: relative;
        }

        .ts-wrapper::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 0.75rem;
            transform: translateY(-50%);
            width: 1.25rem;
            height: 1.25rem;
            pointer-events: none;
            background-repeat: no-repeat;
            background-position: center;
            /* Heroicons Chevron Down - Gray 500 */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%236b7280' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9' /%3E%3C/svg%3E");
            background-size: contain;
        }

        .dark .ts-wrapper::after {
            /* Heroicons Chevron Down - Gray 400 */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%239ca3af' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9' /%3E%3C/svg%3E");
        }

        /* High Z-Index for Dropdown */
        .ts-dropdown {
            z-index: 99999 !important;
        }

        .ts-wrapper,
        .ts-wrapper *,
        .ts-wrapper *:after,
        .ts-wrapper *:before {
            box-sizing: border-box !important;
        }

        @supports (-moz-appearance: none) {
            .ts-wrapper-admin.ts-wrapper,
            .ts-wrapper-admin .ts-control {
                height: 44px !important;
                min-height: 44px !important;
            }
        }
    </style>
@endonce



<div wire:ignore x-data="tomSelectInput(
    @js($options),
    '{{ $placeholder }}',
    @if (isset($__livewire) && $wireModel) @entangle($attributes->wire('model')) @else @js($selected) @endif,
    @js((bool) $disabled),
    @js($wireModel),
    @js((bool) $submitOnChange),
    @js((bool) $livewireSetLive),
    @js($dropdownDirection)
)" class="{{ $wrapperClass }}" @if ($alpineModelAttributes->isNotEmpty()) x-modelable="value" {{ $alpineModelAttributes }} @endif>

    <select
        x-ref="select"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except(['options', 'placeholder', 'selected', 'class']) }}
        placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
