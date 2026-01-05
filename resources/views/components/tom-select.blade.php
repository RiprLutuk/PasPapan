@props(['options' => [], 'placeholder' => 'Select an option', 'selected' => null])

@once
<style>
    /* Default (Light Mode) */
    .ts-control {
        background-color: #f9fafb; /* bg-gray-50 */
        border-color: #d1d5db; /* border-gray-300 */
        color: #111827; /* text-gray-900 */
        border-radius: 0.5rem; /* rounded-lg */
        padding-top: 0.625rem;
        padding-bottom: 0.625rem;
        padding-left: 0.75rem;
        padding-right: 2.5rem; /* Space for arrow */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        font-size: 0.875rem;
        line-height: 1.25rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
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
        border-color: #6366f1; /* indigo-500 */
        box-shadow: 0 0 0 1px #6366f1;
    }

    /* Dropdown */
    .ts-dropdown {
        background-color: #ffffff;
        border-color: #e5e7eb;
        color: #111827;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 50;
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
        border-color: #6366f1 !important; /* indigo-500 */
        box-shadow: 0 0 0 1px #6366f1 !important;
    }

    .dark .ts-dropdown {
        background-color: #111827 !important; /* bg-gray-900 */
        border-color: #374151 !important; /* border-gray-700 */
        color: #d1d5db !important; /* text-gray-300 */
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

<script>
    document.addEventListener('alpine:init', () => {
        if (!Alpine.data('tomSelect')) {
            Alpine.data('tomSelect', (options, placeholder, wireModel) => ({
                tomSelectInstance: null,
                options: options,
                value: wireModel,
                
                init() {
                    if (this.tomSelectInstance) {
                        this.tomSelectInstance.sync();
                        return;
                    }
                    
                    this.tomSelectInstance = new TomSelect(this.$refs.select, {
                        create: false,
                        sortField: {
                            field: '$order'
                        },
                        valueField: 'id',
                        labelField: 'name',
                        searchField: 'name',
                        options: this.options,
                        placeholder: placeholder,
                        onChange: (value) => {
                            this.value = value;
                        }
                    });

                    // Sync Livewire -> TomSelect
                    this.$watch('value', (newValue) => {
                        if (!this.tomSelectInstance) return;
                        const currentValue = this.tomSelectInstance.getValue();
                        // Only update if different to avoid loops
                        if (newValue != currentValue) {
                            this.tomSelectInstance.setValue(newValue, true); // true = silent
                        }
                    });

                    // Initial Value
                    if (this.value) {
                        this.tomSelectInstance.setValue(this.value, true);
                    }
                },

                destroy() {
                    if (this.tomSelectInstance) {
                        this.tomSelectInstance.destroy();
                        this.tomSelectInstance = null;
                    }
                }
            }));
        }
    });
</script>

<div wire:ignore
     x-data="tomSelect(
        @js($options), 
        '{{ $placeholder }}', 
        @if(isset($__livewire) && $attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else @js($selected) @endif
     )"
     class="w-full">
    
    <select x-ref="select" {{ $attributes->except(['options', 'placeholder']) }} placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
