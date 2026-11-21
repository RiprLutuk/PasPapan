<?php

namespace App\Services;

use App\Jobs\SendEmailDelivery;
use App\Models\Announcement;
use App\Models\EmailDelivery;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\AssetReturnOtpRequested;
use App\Notifications\CashAdvanceRequested;
use App\Notifications\CashAdvanceUpdated;
use App\Notifications\LeaveRequested;
use App\Notifications\OvertimeRequested;
use App\Notifications\PayrollPaid;
use App\Notifications\ReimbursementRequested;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmailDeliveryService
{
    /** @var array<int, class-string<Notification>> */
    private const SPECIALIZED_EMAIL_NOTIFICATIONS = [
        LeaveRequested::class,
        OvertimeRequested::class,
        ReimbursementRequested::class,
        CashAdvanceRequested::class,
        CashAdvanceUpdated::class,
        AssetReturnOtpRequested::class,
        PayrollPaid::class,
    ];

    public function queueDatabaseNotification(User $user, Notification $notification, DatabaseNotification $stored): ?EmailDelivery
    {
        if (! config('mail.notification_delivery_enabled', true)
            || blank($user->email)
            || in_array($notification::class, self::SPECIALIZED_EMAIL_NOTIFICATIONS, true)
            || $this->notificationAlreadyHasMailChannel($notification, $user)
            || ! $this->wantsEmail($user, $notification)) {
            return null;
        }

        $data = (array) $stored->data;
        $title = trim((string) ($data['title'] ?? Str::headline(class_basename($notification))));
        $body = trim((string) ($data['message'] ?? $data['body'] ?? 'Ada pembaruan yang memerlukan perhatian Anda.'));
        $actionUrl = $this->absoluteUrl($data['url'] ?? null);

        return $this->createAndDispatch([
            'user_id' => $user->id,
            'source_notification_id' => $stored->getKey(),
            'category' => 'notification',
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'subject' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);
    }

    public function queueEmployeeInvitation(User $user): ?EmailDelivery
    {
        if (blank($user->email)) {
            return null;
        }

        return $this->createAndDispatch([
            'user_id' => $user->id,
            'dedupe_key' => 'employee-invitation:'.$user->id,
            'category' => 'employee_invitation',
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'subject' => 'Akun PasPapan Anda siap',
            'body' => 'Akun kerja Anda telah dibuat. Gunakan alamat email ini untuk masuk. Demi keamanan, password tidak dikirim melalui email; gunakan menu Lupa Password bila perlu membuat atau mengganti password.',
            'action_url' => url('/login'),
        ]);
    }

    public function queueAnnouncement(Announcement $announcement): int
    {
        if (! $announcement->is_active || $announcement->publish_date?->isFuture()) {
            return 0;
        }

        $count = 0;
        User::query()->whereNotNull('email')->where('email', '!=', '')->chunkById(200, function ($users) use ($announcement, &$count): void {
            foreach ($users as $user) {
                $delivery = $this->createAndDispatch([
                    'user_id' => $user->id,
                    'dedupe_key' => "announcement:{$announcement->id}:user:{$user->id}",
                    'category' => 'announcement',
                    'recipient_name' => $user->name,
                    'recipient_email' => $user->email,
                    'subject' => $announcement->title,
                    'body' => $announcement->content,
                    'action_url' => url('/user/notifications'),
                ]);
                $count += $delivery !== null ? 1 : 0;
            }
        });

        $announcement->forceFill(['email_sent_at' => now()])->saveQuietly();

        return $count;
    }

    public function queueOperationalAlert(string $email, string $subject, string $body): ?EmailDelivery
    {
        return $this->createAndDispatch([
            'dedupe_key' => 'operational-alert:'.sha1($email.'|'.$subject.'|'.$body.'|'.now()->format('YmdHi')),
            'category' => 'operational_alert',
            'recipient_name' => 'Administrator',
            'recipient_email' => $email,
            'subject' => $subject,
            'body' => $body,
            'action_url' => url('/admin/system-maintenance'),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function createAndDispatch(array $attributes): ?EmailDelivery
    {
        $lookup = isset($attributes['source_notification_id'])
            ? ['source_notification_id' => $attributes['source_notification_id']]
            : ['dedupe_key' => $attributes['dedupe_key']];

        $delivery = EmailDelivery::query()->firstOrCreate($lookup, $attributes + [
            'status' => EmailDelivery::STATUS_QUEUED,
            'available_at' => now(),
        ]);

        if (! $delivery->wasRecentlyCreated) {
            return null;
        }

        SendEmailDelivery::dispatch($delivery->id);

        return $delivery;
    }

    private function wantsEmail(User $user, Notification $notification): bool
    {
        $preference = $user->notificationPreferences()
            ->where('event_key', $notification::class)
            ->first();

        return $preference === null
            || in_array(UserNotificationPreference::CHANNEL_EMAIL, (array) $preference->channels, true);
    }

    private function notificationAlreadyHasMailChannel(Notification $notification, User $user): bool
    {
        return in_array('mail', $notification->via($user), true);
    }

    private function absoluteUrl(mixed $path): ?string
    {
        if (! is_string($path) || blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : URL::to($path);
    }
}
