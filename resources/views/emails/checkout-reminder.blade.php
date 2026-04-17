@component('emails.layouts.modern', ['title' => __('Checkout Reminder'), 'eyebrow' => __('Attendance Reminder'), 'message' => $message ?? null])

# {{ __('Checkout Reminder') }}

{{ __('Hello, :name!', ['name' => $user->name]) }}

{{ __('The system detected that your shift has ended, but you have not checked out yet.') }}

{{ __('Please checkout immediately via the application to ensure your work hours are recorded correctly.') }}

<div style="text-align: center;">
    <a href="{{ route('home') }}" class="button">{{ __('Go to App') }}</a>
</div>

{{ __('Thank you,') }}<br>
{{ config('app.name') }} {{ __('HR Team') }}

@endcomponent
