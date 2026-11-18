<?php

namespace App\Mail;

use App\Support\MailBranding;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LicenseKeyMail extends Mailable
{
    /**
     * @param  array<int, string>  $features
     */
    public function __construct(
        public readonly string $clientName,
        public readonly string $licenseKey,
        public readonly string $expiryDate,
        public readonly array $features,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailBranding::fromAddress(), MailBranding::companyName()),
            replyTo: [new Address(MailBranding::replyToAddress(), MailBranding::companyName())],
            subject: MailBranding::subject(__('Enterprise License Key')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.license-key',
            with: [
                'licenseDetails' => $this->licenseDetails(),
                'featureLabels' => $this->featureLabels(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $licenseId = $this->licenseDetails()['license_id'] ?? 'key';

        return [
            Attachment::fromData(
                fn (): string => $this->licenseKey."\n",
                'PasPapan-Enterprise-License-'.$licenseId.'.txt',
            )->withMime('text/plain'),
        ];
    }

    /**
     * License data is signed, but this email only displays it; validation remains
     * exclusively the responsibility of LicenseGuard when the key is applied.
     *
     * @return array{license_id: string, client: string, domain: string, hwid: string, support_contact: string, max_users: string}
     */
    private function licenseDetails(): array
    {
        $defaults = [
            'license_id' => '—',
            'client' => $this->clientName,
            'domain' => '—',
            'hwid' => '—',
            'support_contact' => 'Not bound',
            'max_users' => '—',
        ];

        $payload = explode('.', $this->licenseKey, 2)[0] ?? '';
        $decoded = base64_decode($payload, true);
        $data = is_string($decoded) ? json_decode($decoded, true) : null;

        if (! is_array($data)) {
            return $defaults;
        }

        return [
            'license_id' => (string) ($data['license_id'] ?? $defaults['license_id']),
            'client' => (string) ($data['client'] ?? $defaults['client']),
            'domain' => (string) ($data['domain'] ?? $defaults['domain']),
            'hwid' => (string) ($data['hwid'] ?? $defaults['hwid']),
            'support_contact' => (string) ($data['support_contact'] ?? $defaults['support_contact']),
            'max_users' => ((int) ($data['max_users'] ?? -1)) === 0
                ? __('Unlimited')
                : (string) ($data['max_users'] ?? $defaults['max_users']),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function featureLabels(): array
    {
        $labels = [
            'attendance' => 'Attendance management',
            'face_verification' => 'Face verification',
            'payroll' => 'Payroll management',
            'cash_advance' => 'Cash advance / Kasbon',
            'reporting' => 'Reporting & import/export',
            'audit' => 'Audit logs',
            'analytics' => 'Analytics dashboard',
            'asset_management' => 'Asset management',
            'appraisal' => 'Performance appraisal',
            'system_backup' => 'System backup',
            'document_requests' => 'Document workflow',
            'toko_pos' => 'Toko / POS add-on',
        ];

        return array_values(array_map(
            fn (string $feature): string => $labels[$feature] ?? str_replace('_', ' ', $feature),
            $this->features,
        ));
    }
}
