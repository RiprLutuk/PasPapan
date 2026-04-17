<?php

namespace App\Livewire;

use App\Models\CompanyAsset;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyAssets extends Component
{
    public $returnAssetId = null;
    public $otpRequested = false;
    public $otpCode = '';
    public $showReturnModal = false;

    public function openReturnModal($assetId)
    {
        $asset = $this->resolveReturnableAsset($assetId);

        if (!$asset) {
            return;
        }

        $this->returnAssetId = $asset->id;
        $this->otpRequested = false;
        $this->otpCode = '';
        $this->showReturnModal = true;
    }

    public function requestOtp()
    {
        if (!$this->returnAssetId) return;

        $asset = $this->resolveReturnableAsset($this->returnAssetId);

        if (!$asset) {
            return;
        }

        $user = auth()->user();

        // 1. Generate 6 digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 2. Cache the OTP for 15 minutes mapped to asset & user combination
        $cacheKey = "asset_return_otp_{$asset->id}_{$user->id}";
        \Illuminate\Support\Facades\Cache::put($cacheKey, $otp, now()->addMinutes(15));

        // 3. Find Supervisor
        $supervisor = $user->supervisor;
        
        if ($supervisor) {
            $supervisor->notify(new \App\Notifications\AssetReturnOtpRequested($asset->name, $user->name, $otp));
            $supervisor->notify(new \App\Notifications\AssetReturnOtpRequestedEmail($asset->name, $user->name, $otp));
        } else {
            // Fallback to all admins if no direct supervisor
            $admins = \App\Models\User::whereIn('group', ['admin', 'superadmin'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\AssetReturnOtpRequested($asset->name, $user->name, $otp));
                $admin->notify(new \App\Notifications\AssetReturnOtpRequestedEmail($asset->name, $user->name, $otp));
            }
        }

        $this->otpRequested = true;
    }

    public function verifyOtp()
    {
        if (!$this->returnAssetId) return;

        $asset = $this->resolveReturnableAsset($this->returnAssetId);

        if (!$asset) {
            return;
        }

        $user = auth()->user();
        $cacheKey = "asset_return_otp_{$asset->id}_{$user->id}";

        $cachedOtp = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp !== $this->otpCode) {
            session()->flash('error', __('Invalid or expired OTP code.'));
            return;
        }

        // Return asset
        $asset->update([
            'user_id' => null,
            'status' => 'available', // returned to storage pool
        ]);

        \App\Models\CompanyAssetHistory::create([
            'company_asset_id' => $asset->id,
            'user_id' => $user->id,
            'action' => 'returned',
            'notes' => __('Returned by User via OTP code')
        ]);

        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        $this->showReturnModal = false;
        $this->returnAssetId = null;
        $this->otpCode = '';
        
        session()->flash('success', __('Asset returned successfully.'));
    }

    protected function resolveReturnableAsset($assetId): ?CompanyAsset
    {
        $asset = CompanyAsset::query()
            ->whereKey($assetId)
            ->where('user_id', auth()->id())
            ->where('status', 'assigned')
            ->first();

        if ($asset) {
            return $asset;
        }

        $this->showReturnModal = false;
        $this->returnAssetId = null;
        $this->otpRequested = false;
        $this->otpCode = '';

        session()->flash('error', __('The selected asset is not available for return.'));

        return null;
    }

    public function render()
    {
        $assets = CompanyAsset::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('livewire.my-assets', compact('assets'));
    }
}
