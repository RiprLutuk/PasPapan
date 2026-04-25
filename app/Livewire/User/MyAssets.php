<?php

namespace App\Livewire\User;

use App\Models\CompanyAsset;
use App\Support\UserAssetService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyAssets extends Component
{
    use AuthorizesRequests;

    protected UserAssetService $assetService;

    public $assetFilter = 'active';

    public $returnAssetId = null;

    public $selectedAssetName = '';

    public $otpRequested = false;

    public $otpCode = '';

    public $showReturnModal = false;

    public function boot(UserAssetService $assetService): void
    {
        $this->assetService = $assetService;
    }

    public function openReturnModal($assetId)
    {
        $asset = $this->resolveReturnableAsset($assetId);

        if (! $asset) {
            return;
        }

        $this->resetErrorBag();
        $this->returnAssetId = $asset->id;
        $this->selectedAssetName = $asset->name;
        $this->otpRequested = false;
        $this->otpCode = '';
        $this->showReturnModal = true;
    }

    public function closeReturnModal()
    {
        $this->showReturnModal = false;
        $this->returnAssetId = null;
        $this->selectedAssetName = '';
        $this->otpRequested = false;
        $this->otpCode = '';
        $this->resetErrorBag();
    }

    public function setAssetFilter(string $filter): void
    {
        if (! in_array($filter, ['active', 'returned'], true)) {
            return;
        }

        $this->assetFilter = $filter;
    }

    public function requestOtp()
    {
        if (! $this->returnAssetId) {
            return;
        }

        $asset = $this->resolveReturnableAsset($this->returnAssetId);

        if (! $asset) {
            return;
        }

        $this->resetErrorBag('otpCode');
        $this->assetService->requestReturnOtp(auth()->user(), $asset);

        $this->otpRequested = true;
    }

    public function verifyOtp()
    {
        if (! $this->returnAssetId) {
            return;
        }

        $asset = $this->resolveReturnableAsset($this->returnAssetId);

        if (! $asset) {
            return;
        }

        $this->resetErrorBag('otpCode');
        if (! $this->assetService->verifyReturnOtp(auth()->user(), $asset, $this->otpCode)) {
            $this->addError('otpCode', __('Invalid or expired OTP code.'));

            return;
        }

        $this->closeReturnModal();

        session()->flash('success', __('Asset returned successfully.'));
    }

    protected function resolveReturnableAsset($assetId): ?CompanyAsset
    {
        $asset = $this->assetService->resolveReturnableAsset(auth()->user(), $assetId);

        if ($asset) {
            return $asset;
        }

        $this->closeReturnModal();

        session()->flash('error', __('The selected asset is not available for return.'));

        return null;
    }

    public function render()
    {
        $this->authorize('viewAny', CompanyAsset::class);

        $assets = $this->assetService->assignedAssetsForUser(auth()->id());
        $returnedHistories = $this->assetService->returnedHistoriesForUser(auth()->id());

        return view('livewire.user.my-assets', compact('assets', 'returnedHistories'));
    }
}
