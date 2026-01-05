<div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow dark:border-gray-700 dark:bg-gray-800">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ __('Select Shift') }}</label>
    <x-tom-select id="shift"
        class="w-full"
        wire:model="shift_id"
        :options="$shifts->map(fn($shift) => [
            'id' => $shift->id,
            'name' => $shift->name . ' | ' . \App\Helpers::format_time($shift->start_time) . ' - ' . \App\Helpers::format_time($shift->end_time)
        ])->values()->toArray()"
        placeholder="{{ __('Select Shift') }}"
        :disabled="!is_null($attendance)" />
    @error('shift_id')
        <x-input-error for="shift" class="mt-2" message="{{ $message }}" />
    @enderror
</div>
