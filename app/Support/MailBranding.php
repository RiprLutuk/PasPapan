<?php

namespace App\Support;

use App\Models\Setting;

class MailBranding
{
    public static function companyName(): string
    {
        return (string) Setting::getValue('app.company_name', config('app.name', 'PasPapan'));
    }

    public static function fromAddress(): string
    {
        return (string) config('mail.from.address');
    }

    public static function replyToAddress(): string
    {
        return (string) Setting::getValue('mail.reply_to_address', config('mail.from.address'));
    }

    public static function supportAddress(): string
    {
        return (string) Setting::getValue('app.support_contact', static::replyToAddress());
    }

    public static function subject(string $label): string
    {
        return static::companyName().' | '.$label;
    }

    public static function logoPath(): string
    {
        return public_path('images/icons/logo.jpeg');
    }
}
