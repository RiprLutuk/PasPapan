<div class="flex items-start gap-3">
  <div class="w-full">
    <x-forms.label for="value" value="{{ __('Barcode Code') }}" />
    <x-forms.input name="value" id="value" class="mt-1 block w-full" type="text" placeholder="{{ __('Barcode Code') }}"
      wire:model="value" />
    @error('value')
      <x-forms.input-error for="value" class="mt-2" message="{{ $message }}" />
    @enderror
  </div>
  <x-actions.button type="button" wire:click="generate" class="mt-2">{{ __('Generate') }}</x-actions.button>
</div>
