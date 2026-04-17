@component('emails.layouts.modern', ['title' => __('Team Invitation'), 'eyebrow' => __('Team Access'), 'message' => $message ?? null])

# {{ __('Team Invitation') }}

{{ __('You have been invited to join the :team team!', ['team' => $invitation->team->name]) }}

@if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::registration()))
{{ __('If you do not have an account, you may create one by clicking the button below. After creating an account, you may click the invitation acceptance button in this email to accept the team invitation:') }}

<div style="text-align: center;">
    <a href="{{ route('register') }}" class="button">{{ __('Create Account') }}</a>
</div>

{{ __('If you already have an account, you may accept this invitation by clicking the button below:') }}

@else
{{ __('You may accept this invitation by clicking the button below:') }}
@endif

<div style="text-align: center;">
    <a href="{{ $acceptUrl }}" class="button">{{ __('Accept Invitation') }}</a>
</div>

{{ __('If you did not expect to receive an invitation to this team, you may discard this email.') }}

@endcomponent
