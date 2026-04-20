<?php

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

test('my payslips component only loads paid payrolls after password setup is valid', function () {
    enableEnterpriseAttendanceForTests();

    $user = User::factory()->create([
        'payslip_password' => Crypt::encryptString('1234'),
        'payslip_password_set_at' => now(),
    ]);

    Payroll::create([
        'user_id' => $user->id,
        'month' => 3,
        'year' => 2026,
        'net_salary' => 5000000,
        'status' => 'paid',
    ]);

    Payroll::create([
        'user_id' => $user->id,
        'month' => 4,
        'year' => 2026,
        'net_salary' => 5000000,
        'status' => 'draft',
    ]);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\User\MyPayslips::class)
        ->assertSet('needsSetup', false)
        ->assertViewHas('payrolls', function ($payrolls) {
            return $payrolls->count() === 1
                && $payrolls->first()->status === 'paid';
        });
});
