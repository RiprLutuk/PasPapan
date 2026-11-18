<?php

namespace App\Mail;

use App\Support\MailBranding;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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
        return new Content(view: 'emails.license-key');
    }

    /**
     * @return array<int, never>
     */
    public function attachments(): array
    {
        return [];
    }
}
