@component('emails.layouts.modern', ['title' => __('Asset Return Request'), 'eyebrow' => __('Asset Return'), 'message' => $message ?? null])

# {{ __('Hello,') }}

{{ __(':username has requested to return their assigned company asset: :asset.', [
    'username' => $userName,
    'asset' => $assetName,
]) }}

{{ __('To confirm and finalize the return process, please provide the following 6-digit OTP code to :username:', [
    'username' => $userName,
]) }}

<div class="email-section" style="text-align: center;">
    <p style="margin-bottom: 8px; color: #44733a; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em;">
        {{ __('Verification Code') }}
    </p>
    <p style="margin: 0; color: #163020; font-size: 28px; font-family: 'Courier New', monospace; font-weight: 800; line-height: 1.2; letter-spacing: 0.32em;">
        {{ $otp }}
    </p>
</div>

<div style="text-align: center; margin-top: 24px; margin-bottom: 24px;">
    <a href="{{ route('admin.assets') }}" class="button" target="_blank" rel="noopener">{{ __('View Asset Management') }}</a>
</div>

{{ __('If this request is a mistake, you may ignore this message.') }}

@endcomponent
