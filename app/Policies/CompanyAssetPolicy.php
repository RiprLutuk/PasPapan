<?php

namespace App\Policies;

use App\Helpers\Editions;
use App\Models\CompanyAsset;
use App\Models\User;

class CompanyAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return ! Editions::assetLocked();
    }

    public function viewAdminAny(User $user): bool
    {
        return ! Editions::assetLocked() && $user->can('viewAdminAssets');
    }

    public function view(User $user, CompanyAsset $companyAsset): bool
    {
        return ! Editions::assetLocked()
            && ($user->can('viewAdminAssets') || $companyAsset->user_id === $user->id);
    }

    public function returnAsset(User $user, CompanyAsset $companyAsset): bool
    {
        return ! Editions::assetLocked()
            && $companyAsset->user_id === $user->id
            && $companyAsset->status === 'assigned';
    }
}
