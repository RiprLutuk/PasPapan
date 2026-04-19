<?php

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\User;
use Livewire\Livewire;

test('analytics dashboard month and year filters update the selected period', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin(true)->create();
    $this->actingAs($admin);

    Livewire::withQueryParams(['month' => 3, 'year' => now()->subYear()->year])
        ->test(AnalyticsDashboard::class)
        ->assertSet('month', 3)
        ->assertSet('year', now()->subYear()->year)
        ->assertSet('period', now()->subYear()->year . '-03');

    Livewire::withQueryParams([])
        ->test(AnalyticsDashboard::class)
        ->call('selectMonth', 3)
        ->assertSet('month', 3)
        ->assertSet('period', now()->year . '-03')
        ->assertDispatched('chart-update')
        ->call('selectYear', now()->subYear()->year)
        ->assertSet('year', now()->subYear()->year)
        ->assertSet('period', now()->subYear()->year . '-03')
        ->assertDispatched('chart-update');
});
