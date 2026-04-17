<x-guest-layout>
    <x-overlays.authentication-card>
        <x-slot name="logo">
            <x-branding.authentication-card-logo />
        </x-slot>

        <x-forms.validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="block">
                <x-forms.label for="email" value="{{ __('Email') }}" />
                <x-forms.input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-forms.label for="password" value="{{ __('Password') }}" />
                <x-forms.input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-forms.label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-forms.input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-actions.button>
                    {{ __('Reset Password') }}
                </x-actions.button>
            </div>
        </form>
    </x-overlays.authentication-card>
</x-guest-layout>
