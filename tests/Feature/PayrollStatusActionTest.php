<?php

use App\Jobs\SendPayrollPayslipEmail;
use App\Livewire\Admin\PayrollManager;
use App\Mail\PayrollPayslipPasswordRequiredMail;
use App\Mail\PayrollPayslipPdfMail;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PayrollPaid;
use App\Support\PayslipPdfFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
            && ! in_array('mail', $channels, true);
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

test('payroll payslip email job sends encrypted pdf when password is available', function () {
    Mail::fake();

    $employee = User::factory()->create([
        'payslip_password' => Crypt::encryptString('latest-secret'),
        'payslip_password_set_at' => now(),
    ]);

    $payroll = Payroll::create([
        'user_id' => $employee->id,
        'month' => 4,
        'year' => 2026,
        'basic_salary' => 5000000,
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'total_allowance' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    (new SendPayrollPayslipEmail($payroll->id))->handle(app(PayslipPdfFactory::class));

    Mail::assertSent(PayrollPayslipPdfMail::class, function (PayrollPayslipPdfMail $mail) use ($payroll) {
        $details = $mail->content()->with['details'] ?? [];

        return $mail->payroll->is($payroll)
            && str_starts_with($mail->pdfContent, '%PDF')
            && ! array_key_exists(__('Net Salary'), $details);
    });
    expect($payroll->refresh()->pdf_emailed_at)->not->toBeNull();
});

test('payroll payslip email job asks user to set password before sending pdf', function () {
    Mail::fake();

    $employee = User::factory()->create();
    $payroll = Payroll::create([
        'user_id' => $employee->id,
        'month' => 4,
        'year' => 2026,
        'basic_salary' => 5000000,
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'total_allowance' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    (new SendPayrollPayslipEmail($payroll->id))->handle(app(PayslipPdfFactory::class));

    Mail::assertSent(PayrollPayslipPasswordRequiredMail::class, function (PayrollPayslipPasswordRequiredMail $mail) {
        $details = $mail->content()->with['details'] ?? [];

        return ! array_key_exists(__('Net Salary'), $details);
    });
    Mail::assertNotSent(PayrollPayslipPdfMail::class);
    expect($payroll->refresh()->payslip_password_requested_at)->not->toBeNull()
        ->and($payroll->pdf_emailed_at)->toBeNull();
});

test('setting a payslip password queues pending paid payroll pdf emails', function () {
    Queue::fake();

    $employee = User::factory()->create();
    $payroll = Payroll::create([
        'user_id' => $employee->id,
        'month' => 4,
        'year' => 2026,
        'basic_salary' => 5000000,
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'total_allowance' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'status' => 'paid',
        'paid_at' => now(),
        'payslip_password_requested_at' => now(),
    ]);

    $employee->forceFill([
        'payslip_password' => Crypt::encryptString('latest-secret'),
        'payslip_password_set_at' => now(),
    ])->save();

    Queue::assertPushed(SendPayrollPayslipEmail::class, fn (SendPayrollPayslipEmail $job) => $job->payrollId === $payroll->id);
});
