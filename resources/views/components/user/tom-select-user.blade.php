@props(['options' => [], 'placeholder' => 'Select an option', 'selected' => null, 'disabled' => false])

@once
<style>
    /* User Theme Scope */
    .ts-wrapper-user .ts-control {
        background-color: #f9fafb; /* bg-gray-50 */
        border-color: #e5e7eb; /* border-gray-200 */
        color: #111827; /* text-gray-900 */
        border-radius: 0.75rem; /* rounded-xl */
        padding-top: 0.75rem;   /* py-3 */
        padding-bottom: 0.75rem; /* py-3 */
        padding-left: 1rem;     /* px-4 */
        padding-right: 2.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        font-size: 0.875rem;
        line-height: 1.25rem;
        min-height: 48px; /* Taller for user inputs */
        display: flex;
        align-items: center;
        overflow: hidden;
    }

    .ts-wrapper-user .ts-control > input {
        flex: 1 1 auto;
        display: inline-block !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: auto !important;
        min-width: 4px;
    }

    .ts-wrapper-user.focus .ts-control {
        border-color: #6ab45b; /* primary-500 */
        box-shadow: 0 0 0 1px #6ab45b;
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

    .dark .ts-wrapper-user.focus .ts-control {
        border-color: #6ab45b !important; /* primary-500 */
        box-shadow: 0 0 0 1px #6ab45b !important;
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
        {{ $disabled ? 'true' : 'false' }}
     )"
     class="w-full ts-wrapper-user relative">
    
    <select x-ref="select" {{ $attributes->except(['options', 'placeholder']) }} placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
