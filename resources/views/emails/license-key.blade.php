@component('emails.layouts.modern', ['title' => \App\Support\MailBranding::companyName(), 'eyebrow' => __('Enterprise License')])

# {{ __('Your Enterprise License is ready') }}

{{ __('Hello, :name. Your Enterprise access is active until :date.', ['name' => $licenseDetails['client'], 'date' => $expiryDate]) }}

<div class="email-section">
    <p><strong>{{ __('Activation profile') }}</strong></p>
    <p>{{ __('These values must match the target installation exactly. A mismatch will prevent activation.') }}</p>
    <table class="email-data-table" role="presentation">
        <tr><td class="email-data-label">{{ __('Company') }}</td><td class="email-data-value">{{ $licenseDetails['client'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('Domain') }}</td><td class="email-data-value">{{ $licenseDetails['domain'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('Server HWID') }}</td><td class="email-data-value" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px;">{{ $licenseDetails['hwid'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('Support contact') }}</td><td class="email-data-value">{{ $licenseDetails['support_contact'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('User limit') }}</td><td class="email-data-value">{{ $licenseDetails['max_users'] }}</td></tr>
        <tr><td class="email-data-label">{{ __('License ID') }}</td><td class="email-data-value">{{ $licenseDetails['license_id'] }}</td></tr>
    </table>
</div>

<div class="email-section email-section--subtle">
    <p><strong>{{ __('Included access') }}</strong></p>
    <ul>
        @foreach ($featureLabels as $feature)
            <li>{{ $feature }}</li>
        @endforeach
    </ul>
</div>

<div class="email-section">
    <p><strong>{{ __('Your activation key') }}</strong></p>
    <p>{{ __('Use the attached .txt file for a one-click download, or copy the full key below.') }}</p>
    <pre style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word; padding: 14px; border-radius: 8px; background: #f3f4f6; color: #111827; font-size: 12px; line-height: 1.5;">{{ $licenseKey }}</pre>
</div>

<div class="email-section email-section--subtle">
    <p><strong>{{ __('Activate in three steps') }}</strong></p>
    <table class="email-steps" role="presentation">
        <tr class="email-step-row"><td><span class="email-step-badge">1</span></td><td class="email-step-copy"><p class="email-step-title">{{ __('Verify the activation profile') }}</p><p>{{ __('Confirm the company, domain, HWID, and support contact above match the target server.') }}</p></td></tr>
        <tr class="email-step-row"><td><span class="email-step-badge">2</span></td><td class="email-step-copy"><p class="email-step-title">{{ __('Open Enterprise settings') }}</p><p>{{ __('Sign in as Super Admin, then open Admin > Settings > Enterprise.') }}</p></td></tr>
        <tr class="email-step-row"><td><span class="email-step-badge">3</span></td><td class="email-step-copy"><p class="email-step-title">{{ __('Paste and apply') }}</p><p>{{ __('Paste the complete key without changing it, then select Apply.') }}</p></td></tr>
    </table>
</div>

<div class="email-note email-note--warning">
    <strong>{{ __('Keep this private.') }}</strong> {{ __('This key is bound to the activation profile above. Do not publish it or forward it outside the licensed organization.') }}
</div>

@endcomponent
