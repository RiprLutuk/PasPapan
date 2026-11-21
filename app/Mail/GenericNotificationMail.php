<?php

namespace App\Mail;

use App\Models\EmailDelivery;
use App\Support\MailBranding;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class GenericNotificationMail extends Mailable
{
    public function __construct(public readonly EmailDelivery $delivery) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailBranding::fromAddress(), MailBranding::companyName()),
            replyTo: [new Address(MailBranding::replyToAddress(), MailBranding::companyName())],
            subject: MailBranding::subject($this->delivery->subject),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.generic-notification');
    }
}
