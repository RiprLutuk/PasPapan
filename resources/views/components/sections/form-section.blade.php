@props(['submit'])

<div {{ $attributes->merge(['class' => '']) }}>
    <form wire:submit="{{ $submit }}">
        <div class="rounded-2xl border border-primary-100 bg-white shadow-xl shadow-primary-100/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none relative overflow-hidden transition-all duration-300 hover:shadow-2xl hover:shadow-primary-200/50">
            
            {{-- Decorative Background Blob --}}
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

            <!-- Card Header -->
            <div class="relative z-10 rounded-t-2xl border-b border-primary-50 bg-white/50 px-4 py-5 backdrop-blur-sm dark:border-gray-700/50 dark:bg-gray-800/50 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    @if (isset($icon))
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-50 to-white border border-primary-100 text-primary-600 dark:from-primary-900/50 dark:to-gray-800 dark:border-primary-700/50 dark:text-primary-400 shadow-sm">
                            {{ $icon }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                        @if (isset($description) && filled(trim(strip_tags((string) $description))))
                            <div class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $description }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="relative z-10 bg-white/50 px-4 py-5 backdrop-blur-sm dark:bg-gray-800/50 sm:px-6 sm:py-6">
                <div class="grid grid-cols-6 gap-6">
                    {{ $form }}
                </div>
            </div>

            <!-- Card Footer -->
            @if (isset($actions))
                <div class="relative z-10 flex flex-col-reverse gap-3 rounded-b-2xl border-t border-primary-50 px-4 py-4 backdrop-blur-sm dark:border-gray-700/50 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </form>
</div>
