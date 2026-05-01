<?php

namespace App\Policies;

use App\Helpers\Editions;
use App\Models\Appraisal;
use App\Models\User;

class AppraisalPolicy
{
    public function viewAny(User $user): bool
    {
        return ! Editions::appraisalLocked();
    }

    public function viewAdminAny(User $user): bool
    {
        return ! Editions::appraisalLocked() && $user->can('viewAdminAppraisals');
    }

    public function manage(User $user): bool
    {
        return ! Editions::appraisalLocked() && $user->allowsAdminPermission('admin.appraisals.manage');
    }

    public function view(User $user, Appraisal $appraisal): bool
    {
        return ! Editions::appraisalLocked()
            && ($user->can('viewAdminAppraisals') || $appraisal->user_id === $user->id);
    }

    public function calibrate(User $user, Appraisal $appraisal): bool
    {
        return ! Editions::appraisalLocked() && $user->allowsAdminPermission('admin.appraisals.calibrate');
    }

    public function exportPdf(User $user, Appraisal $appraisal): bool
    {
        return $this->view($user, $appraisal);
    }

    public function selfAssess(User $user, Appraisal $appraisal): bool
    {
        return $appraisal->user_id === $user->id && $appraisal->status === 'self_assessment';
    }

    public function acknowledge(User $user, Appraisal $appraisal): bool
    {
        return $appraisal->user_id === $user->id && $appraisal->status === 'completed';
    }
}
