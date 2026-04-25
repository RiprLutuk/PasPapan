<?php

use App\Livewire\Admin\AssetManager;
use App\Livewire\User\MyAssets;
use App\Models\CompanyAsset;
use App\Models\CompanyAssetHistory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

test('user return flow marks asset ready and clears assignment dates', function () {
    $user = User::factory()->create();

    $asset = CompanyAsset::create([
        'name' => 'ThinkPad X1',
        'type' => 'electronics',
        'user_id' => $user->id,
        'date_assigned' => now()->subDays(7)->toDateString(),
        'return_date' => now()->addDays(7)->toDateString(),
        'status' => CompanyAsset::STATUS_ASSIGNED,
    ]);

    $otp = '123456';
    Cache::put("asset_return_otp_{$asset->id}_{$user->id}", $otp, now()->addMinutes(15));

    $this->actingAs($user);

    Livewire::test(MyAssets::class)
        ->set('returnAssetId', $asset->id)
        ->set('otpCode', $otp)
        ->call('verifyOtp')
        ->assertHasNoErrors();

    $asset->refresh();

    expect($asset->user_id)->toBeNull()
        ->and($asset->status)->toBe(CompanyAsset::STATUS_AVAILABLE)
        ->and($asset->date_assigned)->toBeNull()
        ->and($asset->return_date)->toBeNull();

    $history = CompanyAssetHistory::query()
        ->where('company_asset_id', $asset->id)
        ->latest('date')
        ->first();

    expect($history)->not()->toBeNull()
        ->and($history->action)->toBe('returned')
        ->and($history->notes)->toContain(__('Returned by user via OTP and marked ready for reassignment.'));
});

test('admin retrieval marks asset ready and records retrieval note', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $asset = CompanyAsset::create([
        'name' => 'Toyota Avanza',
        'type' => 'vehicle',
        'user_id' => $user->id,
        'date_assigned' => now()->subDays(10)->toDateString(),
        'return_date' => now()->addDays(3)->toDateString(),
        'status' => CompanyAsset::STATUS_ASSIGNED,
    ]);

    $this->actingAs($admin);

    Livewire::test(AssetManager::class)
        ->call('edit', $asset->id)
        ->set('user_id', '')
        ->set('status', CompanyAsset::STATUS_AVAILABLE)
        ->call('save')
        ->assertHasNoErrors();

    $asset->refresh();

    expect($asset->user_id)->toBeNull()
        ->and($asset->status)->toBe(CompanyAsset::STATUS_AVAILABLE)
        ->and($asset->date_assigned)->toBeNull()
        ->and($asset->return_date)->toBeNull();

    $history = CompanyAssetHistory::query()
        ->where('company_asset_id', $asset->id)
        ->where('action', 'returned')
        ->latest('date')
        ->first();

    expect($history)->not()->toBeNull()
        ->and($history->user_id)->toBe($user->id)
        ->and($history->notes)->toContain('Retrieved by Admin')
        ->and($history->notes)->toContain('ready for reassignment');
});

test('admin selecting ready automatically releases the assigned user', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $asset = CompanyAsset::create([
        'name' => 'Dell Latitude',
        'type' => 'electronics',
        'user_id' => $user->id,
        'date_assigned' => now()->subDays(5)->toDateString(),
        'return_date' => now()->addDays(2)->toDateString(),
        'status' => CompanyAsset::STATUS_ASSIGNED,
    ]);

    $this->actingAs($admin);

    Livewire::test(AssetManager::class)
        ->call('edit', $asset->id)
        ->set('status', CompanyAsset::STATUS_AVAILABLE)
        ->call('save')
        ->assertHasNoErrors();

    $asset->refresh();

    expect($asset->user_id)->toBeNull()
        ->and($asset->status)->toBe(CompanyAsset::STATUS_AVAILABLE)
        ->and($asset->date_assigned)->toBeNull()
        ->and($asset->return_date)->toBeNull();
});
