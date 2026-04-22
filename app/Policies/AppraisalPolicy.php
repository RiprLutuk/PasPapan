<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\User;

class AppraisalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('accessAdminPanel');
    }

    public function view(User $user, Appraisal $appraisal): bool
    {
        return $user->isAdmin || $appraisal->user_id === $user->id;
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
