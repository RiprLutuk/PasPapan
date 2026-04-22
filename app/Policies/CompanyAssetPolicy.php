<?php

namespace App\Policies;

use App\Models\CompanyAsset;
use App\Models\User;

class CompanyAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('accessAdminPanel');
    }

    public function view(User $user, CompanyAsset $companyAsset): bool
    {
        return $user->isAdmin || $companyAsset->user_id === $user->id;
    }

    public function returnAsset(User $user, CompanyAsset $companyAsset): bool
    {
        return $companyAsset->user_id === $user->id && $companyAsset->status === 'assigned';
    }
}
