<?php

namespace App\Support;

use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AssetReturnOtpRequested;
use App\Notifications\AssetReturnOtpRequestedEmail;
use App\Notifications\CashAdvanceRequested;
use App\Notifications\CashAdvanceRequestedEmail;
use App\Notifications\OvertimeRequested;
use App\Notifications\OvertimeRequestedEmail;
use App\Notifications\ReimbursementRequested;
use App\Notifications\ReimbursementRequestedEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class UserNotificationRecipientService
{
    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::query()
            ->whereIn('group', ['admin', 'superadmin'])
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function adminsAndSupervisor(User $user): Collection
    {
        $recipients = $this->admins();

        if ($user->supervisor) {
            $recipients = $recipients->push($user->supervisor);
        }

        return $recipients->unique('id')->values();
    }

    /**
     * @return Collection<int, User>
     */
    public function supervisorOrAdmins(User $user): Collection
    {
        if ($user->supervisor) {
            return collect([$user->supervisor]);
        }

        return $this->admins();
    }

    public function notifyReimbursementRequested(Reimbursement $reimbursement): int
    {
        $recipients = $this->adminsAndSupervisor($reimbursement->user);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ReimbursementRequested($reimbursement));
            Notification::send($recipients, new ReimbursementRequestedEmail($reimbursement));
        }

        $this->notifyConfiguredAdminEmail(new ReimbursementRequestedEmail($reimbursement));

        return $recipients->count();
    }

    public function notifyOvertimeRequested(Overtime $overtime): int
    {
        $recipients = $this->adminsAndSupervisor($overtime->user);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new OvertimeRequested($overtime));
            Notification::send($recipients, new OvertimeRequestedEmail($overtime));
        }

        $this->notifyConfiguredAdminEmail(new OvertimeRequestedEmail($overtime));

        return $recipients->count();
    }

    public function notifyAssetReturnOtp(User $user, CompanyAsset $asset, string $otp): int
    {
        $recipients = $this->supervisorOrAdmins($user);

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new AssetReturnOtpRequested($asset->name, $user->name, $otp));
        Notification::send($recipients, new AssetReturnOtpRequestedEmail($asset->name, $user->name, $otp));

        return $recipients->count();
    }

    public function notifyCashAdvanceRequested(CashAdvance $cashAdvance): int
    {
        $recipients = $this->cashAdvanceReviewers($cashAdvance);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new CashAdvanceRequested($cashAdvance));
            Notification::send($recipients, new CashAdvanceRequestedEmail($cashAdvance));
        }

        $this->notifyConfiguredAdminEmail(new CashAdvanceRequestedEmail($cashAdvance));

        return $recipients->count();
    }

    /**
     * @return Collection<int, User>
     */
    protected function cashAdvanceReviewers(CashAdvance $cashAdvance): Collection
    {
        $cashAdvance->loadMissing('user.jobTitle.jobLevel', 'user.division');

        $recipients = collect();

        if ($cashAdvance->user?->supervisor) {
            $recipients->push($cashAdvance->user->supervisor);
        }

        return $recipients
            ->merge($this->admins()->filter(fn (User $admin): bool => $admin->can('manageCashAdvances')))
            ->unique('id')
            ->values();
    }

    protected function notifyConfiguredAdminEmail(object $notification): void
    {
        $adminEmail = Setting::getValue('notif.admin_email');

        if (! is_string($adminEmail) || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Notification::route('mail', $adminEmail)->notify($notification);
        } catch (\Throwable) {
            // Intentionally ignore mail routing failures for optional admin copies.
        }
    }
}
