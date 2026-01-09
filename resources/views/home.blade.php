<x-app-layout>
    {{-- Welcome Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4">
             <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Hi, :name', ['name' => Str::before(Auth::user()->name, ' ')]) }} 👋</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
             {{-- Quick Actions (New) --}}
             @livewire('quick-actions')

             {{-- Scan Component (Modified) --}}
             @livewire('scan-component')
             
             {{-- Summary Stats (New) --}}
             <div class="mt-6">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">{{ __('Monthly Statistics') }}</h3>
                @livewire('attendance-summary-widget')
             </div>

             {{-- Upcoming Events & Announcements (Unified) --}}
             <div class="mt-6 mb-6">
                @livewire('upcoming-events-widget')
             </div>
             
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session()->has('flash.banner'))
                Swal.fire({
                    icon: 'success',
                    title: "{{ __('Success!') }}",
                    text: "{{ session('flash.banner') }}",
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: "{{ __('OK') }}"
                });
            @endif
        });
    </script>
    @endpush
</x-app-layout>
