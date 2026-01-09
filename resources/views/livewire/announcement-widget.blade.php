<div>
@if($announcements->isNotEmpty())
<div class="mb-6">
    <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl border border-amber-200/50 dark:border-amber-700/30 overflow-hidden">
        <div class="p-4 border-b border-amber-200/50 dark:border-amber-700/30">
            <h3 class="text-sm font-bold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                📢 {{ __('Announcements') }}
            </h3>
        </div>
        
        <div class="divide-y divide-amber-200/50 dark:divide-amber-700/30">
            @foreach($announcements as $announcement)
                <div class="p-4 hover:bg-amber-100/50 dark:hover:bg-amber-900/30 transition-colors relative group" wire:key="announcement-{{ $announcement->id }}">
                    {{-- Close Button --}}
                    <button 
                        wire:click="dismiss({{ $announcement->id }})"
                        wire:loading.attr="disabled"
                        class="absolute top-2 right-2 p-1.5 rounded-full text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 opacity-0 group-hover:opacity-100 transition-all"
                        title="{{ __('Dismiss') }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    
                    <div class="flex items-start gap-3 pr-8">
                        {{-- Priority Indicator --}}
                        <div class="flex-shrink-0 mt-1">
                            @if($announcement->priority === 'high')
                                <span class="inline-flex h-3 w-3 rounded-full bg-red-500 animate-pulse" title="{{ __('High Priority') }}"></span>
                            @elseif($announcement->priority === 'normal')
                                <span class="inline-flex h-3 w-3 rounded-full bg-amber-500" title="{{ __('Normal Priority') }}"></span>
                            @else
                                <span class="inline-flex h-3 w-3 rounded-full bg-gray-400" title="{{ __('Low Priority') }}"></span>
                            @endif
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $announcement->title }}
                            </h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ Str::limit(strip_tags($announcement->content), 150) }}
                            </p>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                                {{ $announcement->created_at->diffForHumans() }}
                                @if($announcement->creator)
                                    · {{ $announcement->creator->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
</div>
