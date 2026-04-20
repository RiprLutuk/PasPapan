<?php

namespace App\Support;

use App\Models\CompanyAsset;
use App\Models\CompanyAssetHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserAssetService
{
    public function __construct(
        protected UserNotificationRecipientService $notificationRecipients,
    ) {
    }

    /**
     * @return Collection<int, CompanyAsset>
     */
    public function assignedAssetsForUser(string|int $userId): Collection
    {
        return CompanyAsset::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, CompanyAssetHistory>
     */
    public function returnedHistoriesForUser(string|int $userId): Collection
    {
        return CompanyAssetHistory::query()
            ->with('asset')
            ->where('user_id', $userId)
            ->where('action', 'returned')
            ->latest('date')
            ->get();
    }

    public function resolveReturnableAsset(User $user, mixed $assetId): ?CompanyAsset
    {
        $asset = CompanyAsset::find($assetId);

        if ($asset && $user->can('returnAsset', $asset)) {
            return $asset;
        }

        return null;
    }

    public function requestReturnOtp(User $user, CompanyAsset $asset): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->otpCacheKey($asset->id, $user->id), $otp, now()->addMinutes(15));
        $this->notificationRecipients->notifyAssetReturnOtp($user, $asset, $otp);

        return $otp;
    }

    public function verifyReturnOtp(User $user, CompanyAsset $asset, string $otpCode): bool
    {
        $cacheKey = $this->otpCacheKey($asset->id, $user->id);
        $cachedOtp = Cache::get($cacheKey);

        if (! is_string($cachedOtp) || $cachedOtp !== $otpCode) {
            return false;
        }

        $asset->update([
            'user_id' => null,
            'status' => 'available',
        ]);

        CompanyAssetHistory::create([
            'company_asset_id' => $asset->id,
            'user_id' => $user->id,
            'action' => 'returned',
            'notes' => __('Returned by User via OTP code'),
            'date' => now(),
        ]);

        Cache::forget($cacheKey);

        return true;
    }

    protected function otpCacheKey(int $assetId, string $userId): string
    {
        return "asset_return_otp_{$assetId}_{$userId}";
    }
}
