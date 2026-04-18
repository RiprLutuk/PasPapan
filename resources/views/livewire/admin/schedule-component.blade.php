<x-admin.page-shell
    :title="__('Schedules')"
    :description="__('Set monthly work schedules and day-off overrides for each employee.')"
>
    <x-slot name="toolbar">
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
            {{-- User Selector --}}
            <div class="w-full sm:w-1/3">
                <x-forms.label for="user" value="{{ __('Select Employee') }}" class="mb-1" />
                <x-forms.tom-select id="user" wire:model.live="selectedUser" placeholder="-- {{ __('Select Employee') }} --"
                    :options="$users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])" />
            </div>

            {{-- Date Filters --}}
            <div class="flex gap-2 w-full sm:w-auto">
                 <div class="w-full sm:w-32">
                    <x-forms.label for="month" value="{{ __('Month') }}" class="mb-1" />
                    <x-forms.tom-select id="month" wire:model.live="month" placeholder="{{ __('Month') }}"
                        :options="collect(range(1, 12))->map(fn($m) => ['id' => sprintf('%02d', $m), 'name' => Carbon\Carbon::create()->month($m)->translatedFormat('F')])" />
                 </div>
                 <div class="w-full sm:w-24">
                    <x-forms.label for="year" value="{{ __('Year') }}" class="mb-1" />
                    <x-forms.tom-select id="year" wire:model.live="year" placeholder="{{ __('Year') }}"
                        :options="collect(range(date('Y') - 1, date('Y') + 1))->map(fn($y) => ['id' => $y, 'name' => $y])" />
                 </div>
            </div>
        </div>
    </x-slot>

        {{-- Calendar --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
             {{-- Days Header --}}
            <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $index => $day)
                    <div class="text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 py-3 {{ $index === 0 ? 'text-red-500' : '' }}">
                        {{ __($day) }}
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

                    <button type="button"
                         class="{{ $bgClass }} group relative min-h-[100px] cursor-pointer border-b border-r border-gray-200 text-left transition hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-inset dark:border-gray-700"
                         wire:click="openModal('{{ $dateKey }}')"
                         aria-label="{{ __('Edit schedule for') }} {{ $date->translatedFormat('d F Y') }}">
                        
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
                                    {{ $schedule->is_off ? __('OFF') : ($schedule->shift->name ?? 'Deleted') }}
                                    @if(!$schedule->is_off && $schedule->shift)
                                        <div class="text-[10px] opacity-75">
                                            {{ \App\Helpers::format_time($schedule->shift->start_time) }} - {{ $schedule->shift->end_time ? \App\Helpers::format_time($schedule->shift->end_time) : '?' }}
                                        </div>
                                    @endif
                                </div>
                            @elseif($isCurrentMonth)
                                <div class="text-[10px] text-gray-400 italic">{{ __('Auto') }}</div>
                            @endif
                        </div>
                    </button>
                @endforeach
             </div>
        </div>
    </div>

    {{-- Modal --}}
    <x-overlays.dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ __('Set Schedule') }}: {{ $selectedDate }}
        </x-slot>

        <x-slot name="content">
            <div class="mb-4">
                <x-forms.label for="shift_select" value="{{ __('Select Shift') }}" />
                <div class="mt-1 w-full">
                    <x-forms.tom-select id="shift_select" wire:model="selectedShiftId" placeholder="-- {{ __('Use Auto/Default') }} --"
                        :options="$shifts->map(fn($s) => ['id' => $s->id, 'name' => $s->name . ' (' . $s->start_time . ' - ' . $s->end_time . ')'])" />
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    {{ __('Leave blank to use automatic closest-time detection.') }}
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <x-forms.checkbox id="is_off" wire:model="selectedIsOff" />
                <x-forms.label for="is_off" value="{{ __('Set as Day Off') }}" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('showModal')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.button class="ml-2" wire:click="saveSchedule" wire:loading.attr="disabled">
                {{ __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
