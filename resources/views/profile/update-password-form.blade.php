<x-sections.form-section submit="updatePassword" class="profile-card">
    <x-slot name="icon">
        <x-heroicon-o-key class="h-6 w-6" />
    </x-slot>

    <x-slot name="title">
        {{ __('Update Password') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Ensure your account is using a long, random password to stay secure.') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6">
            <x-forms.label for="current_password" value="{{ __('Current Password') }}" />
            <x-forms.input id="current_password" type="password" class="mt-1 block w-full" wire:model="state.current_password" autocomplete="current-password" />
            <x-forms.input-error for="current_password" class="mt-2" />
        </div>

        <div class="col-span-6">
            <x-forms.label for="password" value="{{ __('New Password') }}" />
            <x-forms.input id="password" type="password" class="mt-1 block w-full" wire:model="state.password" autocomplete="new-password" />
            <x-forms.input-error for="password" class="mt-2" />
        </div>

        <div class="col-span-6">
            <x-forms.label for="password_confirmation" value="{{ __('Confirm Password') }}" />
            <x-forms.input id="password_confirmation" type="password" class="mt-1 block w-full" wire:model="state.password_confirmation" autocomplete="new-password" />
            <x-forms.input-error for="password_confirmation" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-actions.action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-actions.action-message>

        <x-actions.button>
            {{ __('Save') }}
        </x-actions.button>
    </x-slot>
</x-sections.form-section>
