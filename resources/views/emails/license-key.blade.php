@component('emails.layouts.modern', ['title' => \App\Support\MailBranding::companyName(), 'eyebrow' => __('Enterprise License')])

# {{ __('Hello, :name', ['name' => $clientName]) }}

{{ __('Your Enterprise license has been issued and is active until :date.', ['date' => $expiryDate]) }}

<div class="email-section">
    <p><strong>{{ __('Enabled features') }}</strong></p>
    <ul>
        @foreach ($features as $feature)
            <li>{{ $feature }}</li>
        @endforeach
    </ul>
</div>

<div class="email-section">
    <p><strong>{{ __('Enterprise License Key') }}</strong></p>
    <p>{{ __('Copy the complete key below, then paste it into Admin > Settings > Enterprise.') }}</p>
    <pre style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word; padding: 14px; border-radius: 8px; background: #f3f4f6; color: #111827; font-size: 12px; line-height: 1.5;">{{ $licenseKey }}</pre>
</div>

{{ __('Keep this key private. It is bound to the licensed server and must not be shared publicly.') }}

@endcomponent
