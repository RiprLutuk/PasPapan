<div class="rounded-2xl border border-primary-100 bg-white dark:border-gray-700 dark:bg-gray-800 relative overflow-hidden transition-all">

    {{-- Decorative Blob --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

    {{-- Header --}}
    <div class="p-4 border-b border-primary-50 dark:border-gray-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 relative z-10">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
            <span class="text-lg">📅</span>
            {{ __('Upcoming Events') }}
        </h3>
    </div>

    <div class="p-4 relative z-10 min-h-[100px]">
        @if(!$hasEvents)
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <p class="text-xs text-gray-400">{{ __('No upcoming events.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                
                {{-- Announcements --}}
                @if($announcements->isNotEmpty())
                    @foreach($announcements as $announcement)
                         <div class="flex items-center gap-2.5 p-2 bg-blue-50/50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800/30 sm:col-span-2">
                            <div class="shrink-0 flex flex-col items-center justify-center w-8 h-8 bg-white dark:bg-gray-800 rounded-lg border border-blue-100 dark:border-blue-800/30 text-blue-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $announcement->title }}</p>
                                    @if($announcement->priority === 'high')
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ Str::limit(strip_tags($announcement->content), 40) }}</p>
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- Holidays --}}
                @if($holidays->isNotEmpty())
                    @foreach($holidays as $holiday)
                        <div class="flex items-center gap-2.5 p-2 bg-rose-50/50 dark:bg-rose-900/20 rounded-xl border border-rose-100 dark:border-rose-800/30">
                            <div class="shrink-0 flex flex-col items-center justify-center w-8 h-8 bg-white dark:bg-gray-800 rounded-lg border border-rose-100 dark:border-rose-800/30">
                                <span class="text-[8px] font-bold text-rose-500 uppercase tracking-tighter leading-none mb-0.5">{{ $holiday->date->shortMonthName }}</span>
                                <span class="text-xs font-black text-gray-900 dark:text-white leading-none">{{ $holiday->date->day }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $holiday->name }}</p>
                                <span class="text-[9px] font-medium text-rose-500 bg-rose-100 dark:bg-rose-900/50 px-1.5 py-0.5 rounded">{{ __('Holiday') }}</span>
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- Birthdays --}}
                @if($birthdays->isNotEmpty())
                    @foreach($birthdays as $user)
                        <div class="flex items-center gap-2.5 p-2 bg-amber-50/50 dark:bg-amber-900/20 rounded-xl border border-amber-100 dark:border-amber-800/30">
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-lg object-cover border border-amber-100 dark:border-amber-800/30">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[140px]">{{ $user->name }}</p>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="text-[10px]">🎂</span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($user->birth_date)->format('d M') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
    </div>
</div>
