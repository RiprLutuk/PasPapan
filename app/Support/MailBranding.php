<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\File;

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
        foreach (static::logoCandidates() as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return public_path('images/icons/logo.jpeg');
    }

    public static function logoUrl(): string
    {
        $path = static::logoPath();
        $relativePath = str_starts_with($path, public_path())
            ? ltrim(str_replace(public_path(), '', $path), DIRECTORY_SEPARATOR)
            : 'images/icons/logo.jpeg';

        return url(str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));
    }

    public static function logoDataUri(): ?string
    {
        $path = static::logoPath();

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return 'data:'.static::logoMimeType($path).';base64,'.base64_encode((string) file_get_contents($path));
    }

    public static function logoPdfSource(): ?string
    {
        $path = static::logoPath();

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return $path;
    }

    public static function logoMailSource(mixed $message = null): string
    {
        $path = static::logoPath();

        if (is_file($path) && is_readable($path) && is_object($message) && method_exists($message, 'embed')) {
            return $message->embed($path);
        }

        return static::logoUrl();
    }

    private static function logoMimeType(string $path): string
    {
        return File::mimeType($path) ?: match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    /**
     * @return array<int, string>
     */
    private static function logoCandidates(): array
    {
        return [
            public_path('images/icons/logo.jpeg'),
            public_path('images/icons/logo.jpg'),
            public_path('images/icons/logo.png'),
            public_path('images/icons/icon-192x192.png'),
        ];
    }
}
