<?php

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use App\Support\RbacRegistry;

test('operational reports are registered in rbac presets', function () {
    $module = RbacRegistry::module('reports');

    expect($module)->not->toBeNull()
        ->and($module['route_names'])->toContain('admin.reports.index')
        ->and($module['route_names'])->toContain('admin.reports.leaves.export')
        ->and($module['route_names'])->toContain('admin.reports.schedules.export')
        ->and($module['route_names'])->toContain('admin.reports.overtime.export')
        ->and($module['route_names'])->toContain('admin.reports.payrolls.export')
        ->and(RbacRegistry::permissionKeys())->toContain('admin.reports.view');

    expect(RbacRegistry::presets()['admin']['permissions'])->toContain('admin.reports.view')
        ->and(RbacRegistry::presets()['hr']['permissions'])->toContain('admin.reports.view')
        ->and(RbacRegistry::presets()['finance']['permissions'])->toContain('admin.reports.view');
});

test('admin can open the operational report center', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee(__('Report Center'))
        ->assertSee(__('Leave Request Report'))
        ->assertSee(__('Schedule Roster Report'))
        ->assertSee(__('Payroll Summary Report'))
        ->assertSee(__('Overtime Report'));

    $this->actingAs($employee)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});

test('leave report export returns an excel download', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    Attendance::create([
        'user_id' => $employee->id,
        'date' => '2026-04-20',
        'status' => 'leave',
        'approval_status' => Attendance::STATUS_APPROVED,
        'note' => 'Annual leave',
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.leaves.export', [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'approval_status' => 'approved',
            'request_type' => 'leave',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('leave-report-');
});

test('overtime report export returns an excel download', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create([
        'hourly_rate' => 25000,
    ]);

    Overtime::create([
        'user_id' => $employee->id,
        'date' => '2026-04-21',
        'start_time' => '2026-04-21 18:00:00',
        'end_time' => '2026-04-21 20:00:00',
        'duration' => 120,
        'reason' => 'Month-end processing',
        'status' => 'approved',
        'approved_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.overtime.export', [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => 'approved',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('overtime-report-');
});

test('schedule roster report export returns an excel download', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $shift = Shift::create([
        'name' => 'Morning',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $shift->id,
        'date' => '2026-04-22',
        'is_off' => false,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.schedules.export', [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'shift_id' => $shift->id,
            'off_status' => 'working',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('schedule-roster-report-');
});

test('payroll summary report export returns an excel download', function () {
    $finance = User::factory()->admin(true)->create();
    $employee = User::factory()->create();

    Payroll::create([
        'user_id' => $employee->id,
        'type' => 'regular',
        'month' => 4,
        'year' => 2026,
        'basic_salary' => 5000000,
        'allowances' => ['Transport' => 500000],
        'deductions' => ['Tax' => 100000],
        'overtime_pay' => 250000,
        'total_allowance' => 500000,
        'total_deduction' => 100000,
        'net_salary' => 5650000,
        'status' => 'paid',
        'generated_by' => $finance->id,
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($finance)
        ->get(route('admin.reports.payrolls.export', [
            'month' => 4,
            'year' => 2026,
            'status' => 'paid',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('payroll-summary-report-');
});
