<?php

namespace App\Console\Commands;

use App\Services\EmailDeliveryService;
use App\Support\OperationalHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendOperationalHealthAlerts extends Command
{
    protected $signature = 'health:send-email-alerts';

    protected $description = 'Queue deduplicated email alerts for operational health warnings.';

    public function handle(OperationalHealthService $health, EmailDeliveryService $deliveries): int
    {
        $emails = collect(explode(',', (string) config('mail.operational_alert_emails', '')))
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return self::SUCCESS;
        }

        $alerts = $health->snapshot()['alerts'];
        if ($alerts === []) {
            Cache::forget('health:email-alert-fingerprint');

            return self::SUCCESS;
        }

        $fingerprint = sha1(json_encode($alerts));
        if (Cache::get('health:email-alert-fingerprint') === $fingerprint) {
            return self::SUCCESS;
        }

        $body = collect($alerts)
            ->map(fn (array $alert): string => "[{$alert['level']}] {$alert['message']}")
            ->prepend('PasPapan mendeteksi kondisi operasional yang membutuhkan perhatian:')
            ->implode("\n");

        foreach ($emails as $email) {
            $deliveries->queueOperationalAlert($email, 'Peringatan operasional PasPapan', $body);
        }

        Cache::put('health:email-alert-fingerprint', $fingerprint, now()->addHour());

        return self::SUCCESS;
    }
}
