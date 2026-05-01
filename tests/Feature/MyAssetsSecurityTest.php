<?php

use App\Models\CompanyAsset;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    enableEnterpriseAttendanceForTests();
});

test('user cannot request return otp for asset that is not assigned to them', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $asset = CompanyAsset::create([
        'name' => 'MacBook Pro',
        'type' => 'electronics',
        'user_id' => $otherUser->id,
        'date_assigned' => now()->toDateString(),
        'status' => 'assigned',
    ]);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\MyAssets::class)
        ->call('openReturnModal', $asset->id)
        ->assertSet('returnAssetId', null)
        ->assertSet('showReturnModal', false);
});
