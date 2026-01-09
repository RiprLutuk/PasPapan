<div {{ $attributes->merge(['class' => '']) }}>
    <div class="rounded-2xl border border-primary-100 bg-white shadow-xl shadow-primary-100/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none relative overflow-hidden transition-all duration-300 hover:shadow-2xl hover:shadow-primary-200/50">
        
        {{-- Decorative Background Blob --}}
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

        <!-- Card Header -->
        <div class="relative z-10 px-6 py-5 border-b border-primary-50 dark:border-gray-700/50 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-t-2xl">
             <div class="flex items-center gap-3">
                @if (isset($icon))
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-50 to-white border border-primary-100 text-primary-600 dark:from-primary-900/50 dark:to-gray-800 dark:border-primary-700/50 dark:text-primary-400 shadow-sm">
                        {{ $icon }}
                    </div>
                @endif
                <div>
                     <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                     <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
                </div>
            </div>
        </div>

        <!-- Card Content -->
        <div class="relative z-10 px-6 py-6 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
            {{ $content }}
        </div>
    </div>
</div>
