@props([
    'options' => [],
    'placeholder' => 'Select an option',
    'selected' => null,
    'disabled' => false,
    'dropdownParent' => 'body',
])

@once
<style>
    /* User Theme Scope */
    .ts-wrapper-user .ts-wrapper {
        width: 100%;
    }

    .ts-wrapper-user .ts-control {
        background-color: #ffffff !important;
        border: 1px solid #d1d5db !important; /* border-gray-300 */
        color: #111827 !important; /* text-gray-900 */
        border-radius: 0.75rem !important; /* rounded-xl */
        padding: 0 2.5rem 0 1rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        height: 2.75rem !important;
        min-height: 2.75rem !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        overflow: hidden !important;
    }

    .ts-wrapper-user .ts-control > input {
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
        vertical-align: middle !important;
    }

    .ts-wrapper-user .ts-control .item {
        flex: 0 1 auto;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ts-wrapper-user .ts-wrapper.single:not(.has-items) .ts-control > input {
        margin-left: 0 !important;
    }

    .ts-wrapper-user .ts-wrapper.focus .ts-control,
    .ts-wrapper-user .ts-wrapper.input-active .ts-control,
    .ts-wrapper-user .ts-wrapper.dropdown-active .ts-control {
        border-color: #6ab45b !important; /* primary-500 */
        outline: 2px solid transparent;
        outline-offset: 2px;
        box-shadow: 0 0 0 3px rgba(106, 180, 91, 0.28), 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    }

    /* Dropdown */
    .ts-wrapper-user .ts-dropdown {
        background-color: #ffffff !important;
        border-color: #e5e7eb;
        color: #111827;
        border-radius: 0.75rem; /* rounded-xl */
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        z-index: 99999 !important;
        margin-top: 4px;
    }

    .ts-wrapper-user .ts-dropdown .option {
        padding: 0.75rem 1rem; /* Larger touch target */
    }

    .ts-wrapper-user .ts-dropdown .active {
        background-color: #f3f4f6;
        color: #111827;
    }

    /* Dark Mode */
    .dark .ts-wrapper-user .ts-control {
        background-color: rgba(17, 24, 39, 0.5) !important; /* bg-gray-900/50 */
        border-color: #374151 !important; /* border-gray-700 */
        color: #f3f4f6 !important; /* text-gray-100 */
    }

    .dark .ts-wrapper-user .ts-control input {
        color: #f3f4f6 !important;
    }

    .dark .ts-wrapper-user .ts-wrapper.focus .ts-control,
    .dark .ts-wrapper-user .ts-wrapper.input-active .ts-control,
    .dark .ts-wrapper-user .ts-wrapper.dropdown-active .ts-control {
        border-color: #6ab45b !important; /* primary-500 */
        box-shadow: 0 0 0 3px rgba(106, 180, 91, 0.28) !important;
    }

    .dark .ts-wrapper-user .ts-dropdown {
        background-color: #1f2937 !important;
        border-color: #374151 !important;
        color: #d1d5db !important;
    }

    .dark .ts-wrapper-user .ts-dropdown .active {
        background-color: #374151 !important;
        color: #ffffff !important;
    }

    /* Chevron */
    .ts-wrapper-user::after {
        content: '';
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        width: 1.25rem;
        height: 1.25rem;
        pointer-events: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%236b7280' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
    }
</style>
@endonce

<div wire:ignore
     x-data="tomSelectInput(
        @js($options), 
        '{{ $placeholder }}', 
        @if(isset($__livewire) && $attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else @js($selected) @endif,
        {{ $disabled ? 'true' : 'false' }},
        null,
        false,
        false,
        'auto',
        @js($dropdownParent)
     )"
     class="w-full ts-wrapper-user relative">
    
    <select
        x-ref="select"
        {{ $attributes->whereDoesntStartWith('wire:model')->except(['options', 'placeholder']) }}
        placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
