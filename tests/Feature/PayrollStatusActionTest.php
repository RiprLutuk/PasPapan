<?php

use App\Livewire\Admin\PayrollManager;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PayrollPaid;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    enableEnterpriseAttendanceForTests();
});

test('admin can publish and pay payroll records without activity log append only failures', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Payroll Manager',
        'slug' => 'payroll_manager',
        'description' => 'Can manage payroll records.',
        'permissions' => ['admin.payroll.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    $payroll = Payroll::create([
        'user_id' => $employee->id,
        'month' => (int) now()->month,
        'year' => (int) now()->year,
        'basic_salary' => 5000000,
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'total_allowance' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'status' => 'draft',
        'generated_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('publish', (string) $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe('published');

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('pay', (string) $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe('paid')
        ->and($payroll->paid_at)->not->toBeNull();

    Notification::assertSentTo($employee, PayrollPaid::class, function (PayrollPaid $notification, array $channels) use ($payroll) {
        return $notification->payroll->is($payroll)
            && in_array('database', $channels, true)
            && in_array('mail', $channels, true);
    });
});

test('admin can publish and pay selected payroll records in bulk', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employees = User::factory()->count(2)->create();
    $role = Role::create([
        'name' => 'Bulk Payroll Manager',
        'slug' => 'bulk_payroll_manager',
        'description' => 'Can manage payroll records in bulk.',
        'permissions' => ['admin.payroll.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    $payrolls = $employees->map(fn (User $employee) => Payroll::create([
        'user_id' => $employee->id,
        'month' => (int) now()->month,
        'year' => (int) now()->year,
        'basic_salary' => 5000000,
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'total_allowance' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'status' => 'draft',
        'generated_by' => $admin->id,
    ]));

    $ids = $payrolls->pluck('id')->map(fn ($id) => (string) $id)->all();

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->set('selectedPayrolls', $ids)
        ->call('bulkPublish')
        ->assertHasNoErrors();

    expect(Payroll::query()->whereIn('id', $ids)->pluck('status')->unique()->all())->toBe(['published']);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->set('selectedPayrolls', $ids)
        ->call('bulkPay')
        ->assertHasNoErrors();

    expect(Payroll::query()->whereIn('id', $ids)->pluck('status')->unique()->all())->toBe(['paid'])
        ->and(Payroll::query()->whereIn('id', $ids)->whereNull('paid_at')->exists())->toBeFalse();

    foreach ($employees as $employee) {
        Notification::assertSentTo($employee, PayrollPaid::class);
    }
});
