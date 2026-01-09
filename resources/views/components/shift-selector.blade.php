    <div class="relative z-20">
        <x-tom-select id="shift"
            class="w-full"
            wire:model="shift_id"
            :options="$shifts->map(fn($shift) => [
                'id' => $shift->id,
                'name' => $shift->name . ' | ' . \App\Helpers::format_time($shift->start_time) . ' - ' . \App\Helpers::format_time($shift->end_time)
            ])->values()->toArray()"
            placeholder="{{ __('Select Shift') }}" />
        @error('shift_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>
