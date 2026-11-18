@component('emails.layouts.modern', ['title' => \App\Support\MailBranding::companyName(), 'eyebrow' => __('Enterprise License')])

# {{ __('Enterprise License ready') }}

{{ __('Active until :date.', ['date' => $expiryDate]) }}

<div class="email-section">
    <p><strong>{{ __('Activation profile — must match exactly') }}</strong></p>
    <table class="email-data-table" role="presentation">
        <tr><td class="email-data-label">{{ __('Company') }}</td><td class="email-data-value">{{ $licenseDetails['client'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('Domain') }}</td><td class="email-data-value">{{ $licenseDetails['domain'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('Server HWID') }}</td><td class="email-data-value" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px;">{{ $licenseDetails['hwid'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('Support contact') }}</td><td class="email-data-value">{{ $licenseDetails['support_contact'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('User limit') }}</td><td class="email-data-value">{{ $licenseDetails['max_users'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('License ID') }}</td><td class="email-data-value">{{ $licenseDetails['license_id'] }}</td></tr>
    </table>
</div>

<p style="margin: 14px 0 0; font-size: 13px; line-height: 1.65;"><strong>{{ __('Included:') }}</strong> {{ implode(' · ', $featureLabels) }}</p>

<div class="email-section">
    <p><strong>{{ __('Your activation key') }}</strong></p>
    <p>{{ __('Download the attached .txt file in one click, or copy the full key below.') }}</p>
    <pre style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word; padding: 14px; border-radius: 8px; background: #f3f4f6; color: #111827; font-size: 12px; line-height: 1.5;">{{ $licenseKey }}</pre>
</div>

<p style="margin: 14px 0 0; font-size: 13px; line-height: 1.65;">{{ __('Activate: sign in as Super Admin → Admin > Settings > Enterprise → paste the complete key → Apply.') }}</p>

<div class="email-note email-note--warning">
    <strong>{{ __('Keep this private.') }}</strong> {{ __('This key is bound to the activation profile above. Do not publish it or forward it outside the licensed organization.') }}
</div>

@endcomponent
