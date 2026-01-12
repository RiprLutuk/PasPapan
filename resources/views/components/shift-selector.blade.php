@props(['disabled' => false])

<div class="relative z-20">
    <x-tom-select-user
        id="shift_id"
        name="shift_id"
        wire:model.live="shift_id"
        placeholder="{{ __('Select Shift') }}"
        :options="$shifts"
        class="w-full"
        :disabled="$disabled"
    />
        @error('shift_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
</div>
