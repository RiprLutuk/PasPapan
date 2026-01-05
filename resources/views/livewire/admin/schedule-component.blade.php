<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Controls --}}
        <div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-center bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            
            {{-- User Selector --}}
            <div class="w-full sm:w-1/3">
                <x-label for="user" value="Select Employee" class="mb-1" />
                <x-tom-select id="user" wire:model.live="selectedUser" placeholder="-- Select Employee --"
                    :options="$users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])" />
            </div>

            {{-- Date Filters --}}
            <div class="flex gap-2 w-full sm:w-auto">
                 <div class="w-full sm:w-32">
                    <x-label for="month" value="Month" class="mb-1" />
                    <x-tom-select id="month" wire:model.live="month" placeholder="Select Month"
                        :options="collect(range(1, 12))->map(fn($m) => ['id' => sprintf('%02d', $m), 'name' => Carbon\Carbon::create()->month($m)->translatedFormat('F')])" />
                 </div>
                 <div class="w-full sm:w-24">
                    <x-label for="year" value="Year" class="mb-1" />
                    <x-tom-select id="year" wire:model.live="year" placeholder="Select Year"
                        :options="collect(range(date('Y') - 1, date('Y') + 1))->map(fn($y) => ['id' => $y, 'name' => $y])" />
                 </div>
            </div>
        </div>

        {{-- Calendar --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
             {{-- Days Header --}}
            <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                @foreach (['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $index => $day)
                    <div class="text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 py-3 {{ $index === 0 ? 'text-red-500' : '' }}">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Grid --}}
             <div class="grid grid-cols-7 border-l border-gray-200 dark:border-gray-700">
                @foreach ($calendar as $date)
                    @php
                        $dateKey = $date->toDateString();
                        $schedule = $schedules[$dateKey] ?? null;
                        $isCurrentMonth = $date->month == $currentMonth;
                        $isToday = $date->isToday();
                        
                        $bgClass = $isCurrentMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900/50';
                        $textClass = $isCurrentMonth ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600';
                        
                        // Shift Style
                        $shiftColor = 'bg-gray-100 dark:bg-gray-700 text-gray-500';
                        if ($schedule) {
                            if ($schedule->is_off) {
                                $shiftColor = 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                            } else {
                                $shiftColor = 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
                            }
                        }
                    @endphp

                    <div class="{{ $bgClass }} border-b border-r border-gray-200 dark:border-gray-700 min-h-[100px] relative hover:bg-gray-50 transition cursor-pointer group"
                         wire:click="openModal('{{ $dateKey }}')">
                        
                        {{-- Date Number --}}
                        <div class="p-2 flex justify-between items-start">
                            <span class="text-sm font-semibold {{ $textClass }} {{ $isToday ? 'bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center' : '' }}">
                                {{ $date->day }}
                            </span>
                            
                            {{-- Edit Icon (Visible on Hover) --}}
                            <span class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-blue-500">
                                <x-heroicon-o-pencil class="w-4 h-4" />
                            </span>
                        </div>

                        {{-- Schedule Badge --}}
                        <div class="px-1 text-center">
                            @if ($schedule)
                                <div class="text-xs font-medium rounded px-1 py-1 {{ $shiftColor }} truncate">
                                    {{ $schedule->is_off ? 'OFF' : ($schedule->shift->name ?? 'Deleted') }}
                                    @if(!$schedule->is_off && $schedule->shift)
                                        <div class="text-[10px] opacity-75">
                                            {{ \Carbon\Carbon::parse($schedule->shift->start_time)->format('H:i') }} - {{ $schedule->shift->end_time ? \Carbon\Carbon::parse($schedule->shift->end_time)->format('H:i') : '?' }}
                                        </div>
                                    @endif
                                </div>
                            @elseif($isCurrentMonth)
                                <div class="text-[10px] text-gray-400 italic">Auto</div>
                            @endif
                        </div>
                    </div>
                @endforeach
             </div>
        </div>
    </div>

    {{-- Modal --}}
    <x-dialog-modal wire:model="showModal">
        <x-slot name="title">
            Set Schedule: {{ $selectedDate }}
        </x-slot>

        <x-slot name="content">
            <div class="mb-4">
                <x-label for="shift_select" value="Select Shift" />
                <div class="mt-1 w-full">
                    <x-tom-select id="shift_select" wire:model="selectedShiftId" placeholder="-- Use Auto/Default --"
                        :options="$shifts->map(fn($s) => ['id' => $s->id, 'name' => $s->name . ' (' . $s->start_time . ' - ' . $s->end_time . ')'])" />
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Leave blank to use automatic closest-time detection.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <x-checkbox id="is_off" wire:model="selectedIsOff" />
                <x-label for="is_off" value="Set as Day Off (Libur)" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showModal')" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            <x-button class="ml-2" wire:click="saveSchedule" wire:loading.attr="disabled">
                Save
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
