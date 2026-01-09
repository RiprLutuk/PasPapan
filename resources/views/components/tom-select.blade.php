@props(['options' => [], 'placeholder' => 'Select an option', 'selected' => null])

@once
<style>
    /* Default (Light Mode) */
    .ts-control {
        background-color: #f9fafb; /* bg-gray-50 */
        border-color: #d1d5db; /* border-gray-300 */
        color: #111827; /* text-gray-900 */
        border-radius: 0.375rem; /* rounded-md to match x-input */
        padding-top: 0.5rem;   /* Increased from 0.4rem */
        padding-bottom: 0.5rem; /* Increased from 0.4rem */
        padding-left: 0.75rem;
        padding-right: 2.5rem; /* Space for arrow */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
        font-size: 0.875rem;
        line-height: 1.25rem;
        min-height: 42px; /* Ensure consistent height */
        display: flex;
        flex-wrap: nowrap; /* Prevent cursor/input from dropping to next line */
        align-items: center;
        overflow: hidden;
    }

    .ts-control > input {
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
    
    .ts-wrapper.focus .ts-control {
        border-color: #6366f1; /* primary-500 */
        box-shadow: 0 0 0 1px #6366f1;
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
        background-color: #f3f4f6; /* gray-100 */
        color: #111827;
    }

    /* Dark Mode - Root selector to ensure specificity */
    .dark .ts-control {
        background-color: #111827 !important; /* bg-gray-900 */
        border-color: #374151 !important; /* border-gray-700 */
        color: #ffffff !important; /* text-white */
    }

    .dark .ts-control input {
        color: #ffffff !important;
    }

    .dark .ts-wrapper.focus .ts-control {
        border-color: #6366f1 !important; /* primary-500 */
        box-shadow: 0 0 0 1px #6366f1 !important;
    }

    .dark .ts-dropdown {
        background-color: #1f2937 !important; /* bg-gray-800 - Match card bg for better blend or use 900 */
        border-color: #374151 !important; /* border-gray-700 */
        color: #d1d5db !important; /* text-gray-300 */
    }

    .dark .ts-dropdown .ts-dropdown-content {
        background-color: #1f2937 !important;
    }

    .dark .ts-dropdown .option {
        color: #d1d5db !important;
    }

    .dark .ts-dropdown .active {
        background-color: #374151 !important; /* bg-gray-700 */
        color: #ffffff !important;
    }

    .dark .ts-dropdown .option:hover,
    .dark .ts-dropdown .option.active {
        background-color: #374151 !important;
        color: #ffffff !important;
    }

    /* Input placeholder color in dark mode */
    .dark .ts-control ::placeholder {
        color: #9ca3af !important; /* gray-400 */
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
</style>
@endonce



<div wire:ignore
     x-data="tomSelectInput(
        @js($options), 
        '{{ $placeholder }}', 
        @if(isset($__livewire) && $attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else @js($selected) @endif
     )"
     class="w-full">
    
    <select x-ref="select" {{ $attributes->except(['options', 'placeholder']) }} placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
