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
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function leaveApprovers(User $user): Collection
    {
        return $this->reviewersWithSupervisor(
            $user,
            fn (User $reviewer): bool => $reviewer->can('manageLeaveApprovals'),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function reimbursementApprovers(User $user): Collection
    {
        return $this->reviewersWithSupervisor(
            $user,
            fn (User $reviewer): bool => $this->approvalActors->canFinalizeReimbursementApproval($reviewer),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function overtimeApprovers(User $user): Collection
    {
        return $this->reviewersWithSupervisor(
            $user,
            fn (User $reviewer): bool => $reviewer->can('manageOvertime'),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function assetReturnApprovers(User $user): Collection
    {
        $supervisor = $this->supervisor($user);

        if ($supervisor !== null) {
            return collect([$supervisor]);
        }

        return $this->usersMatching(fn (User $reviewer): bool => $reviewer->can('viewAdminAssets'));
    }

    public function notifyReimbursementRequested(Reimbursement $reimbursement): int
    {
        $reimbursement->loadMissing('user.division', 'user.jobTitle.jobLevel');
        $recipients = $this->reimbursementApprovers($reimbursement->user);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ReimbursementRequested($reimbursement));
            Notification::send($recipients, new ReimbursementRequestedEmail($reimbursement));
        }

        $this->notifyConfiguredAdminEmail(new ReimbursementRequestedEmail($reimbursement));

        return $recipients->count();
    }

    public function notifyOvertimeRequested(Overtime $overtime): int
    {
        $overtime->loadMissing('user.division', 'user.jobTitle.jobLevel');
        $recipients = $this->overtimeApprovers($overtime->user);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new OvertimeRequested($overtime));
            Notification::send($recipients, new OvertimeRequestedEmail($overtime));
        }

        $this->notifyConfiguredAdminEmail(new OvertimeRequestedEmail($overtime));

        return $recipients->count();
    }

    public function notifyAssetReturnOtp(User $user, CompanyAsset $asset, string $otp): int
    {
        $user->loadMissing('division', 'jobTitle.jobLevel');
        $recipients = $this->assetReturnApprovers($user);

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

        return $this->reviewersWithSupervisor(
            $cashAdvance->user,
            fn (User $reviewer): bool => $this->approvalActors->canFinalizeCashAdvanceApproval($reviewer),
        );
    }

    /**
     * @param  callable(User): bool  $reviewerFilter
     * @return Collection<int, User>
     */
    protected function reviewersWithSupervisor(User $user, callable $reviewerFilter): Collection
    {
        $recipients = collect();
        $supervisor = $this->supervisor($user);

        if ($supervisor !== null) {
            $recipients->push($supervisor);
        }

        return $recipients
            ->merge($this->usersMatching($reviewerFilter))
            ->unique('id')
            ->values();
    }

    /**
     * @param  callable(User): bool  $filter
     * @return Collection<int, User>
     */
    protected function usersMatching(callable $filter): Collection
    {
        return User::query()
            ->with(['roles', 'division', 'jobTitle.jobLevel'])
            ->get()
            ->reject(fn (User $user): bool => $user->isDemo)
            ->filter(fn (User $user): bool => $filter($user))
            ->values();
    }

    protected function supervisor(User $user): ?User
    {
        $user->loadMissing('division', 'jobTitle.jobLevel');

        return $user->supervisor;
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
