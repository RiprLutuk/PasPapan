@component('emails.layouts.modern', ['title' => $title ?? config('app.name'), 'eyebrow' => $eyebrow ?? __('Account Notification'), 'message' => $message ?? null])

# {{ $greeting ?? __('Hello!') }}

@foreach (($introLines ?? []) as $line)
{{ $line }}
@endforeach

@if (!empty($verificationCode))
<div style="text-align: center; margin-top: 24px; margin-bottom: 24px;">
    <div style="display: inline-block; padding: 16px 22px; border-radius: 16px; border: 1px solid #d5ead1; background: #f8fcf7; color: #163020; font-size: 28px; font-weight: 800; letter-spacing: 0.18em;">
        {{ $verificationCode }}
    </div>
</div>
@endif

@if (!empty($actionText) && !empty($actionUrl))
<div style="text-align: center; margin-top: 24px; margin-bottom: 24px;">
    <a href="{{ $actionUrl }}" class="button" target="_blank" rel="noopener">{{ $actionText }}</a>
</div>
@endif

@foreach (($outroLines ?? []) as $line)
{{ $line }}
@endforeach

@if (!empty($helpText))
<div class="email-note">
    {{ $helpText }}
</div>
@endif

@if (!empty($actionText) && !empty($actionUrl))
<div class="subcopy">
    <p>@lang('If you\'re having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser:', ['actionText' => $actionText])</p>
    <p><a href="{{ $actionUrl }}">{{ $actionUrl }}</a></p>
</div>
@endif

@endcomponent
