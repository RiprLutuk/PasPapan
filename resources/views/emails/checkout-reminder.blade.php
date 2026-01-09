@component('emails.layouts.modern')

# {{ __('Checkout Reminder') }}

{{ __('Hello') }} {{ $user->name }},

{{ __('The system detected that your shift has ended, but you have not checked out yet.') }}

{{ __('Please checkout immediately via the application to ensure your work hours are recorded correctly.') }}

<div style="text-align: center;">
    <a href="{{ route('home') }}" class="btn">{{ __('Go to App') }}</a>
</div>

{{ __('Thank you,') }}<br>
{{ config('app.name') }} HR Team

@endcomponent
