<?php

use App\Contracts\AuditServiceInterface;
use App\Livewire\Admin\DashboardComponent;
use App\Mail\CheckoutReminderMail;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use App\Support\AdminDashboardQueryService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function fakeDashboardAuditRecorder(): object
{
    return new class implements AuditServiceInterface
    {
        public array $records = [];

        public function record(string $action, ?string $description = null)
        {
            $this->records[] = compact('action', 'description');

            return null;
        }
    };
}

test('admin dashboard renders recent activity and overdue checkout data', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create(['name' => 'Dashboard Employee']);
    $shiftEnd = now()->subHour();
    $shiftStart = $shiftEnd->copy()->subHours(8);
    $shift = Shift::factory()->create([
        'start_time' => $shiftStart->format('H:i:s'),
        'end_time' => $shiftEnd->format('H:i:s'),
    ]);

    Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'time_in' => $shiftStart->copy()->addMinutes(5),
        'time_out' => null,
        'shift_id' => $shift->id,
        'status' => 'present',
    ]);

    ActivityLog::create([
        'user_id' => $employee->id,
        'action' => 'Login Successful',
        'description' => 'User logged in.',
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($admin);

    Livewire::test(DashboardComponent::class)
        ->assertSee('Dashboard Employee')
        ->assertViewHas('overdueUsers', fn ($users) => $users->contains(fn ($attendance) => $attendance->user_id === $employee->id));
});

test('dashboard reminder action queues checkout reminder email and audits it', function () {
    Mail::fake();

    $audit = fakeDashboardAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create([
        'name' => 'Reminder Employee',
        'email' => 'reminder@example.com',
    ]);

    $attendance = Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'present',
    ]);

    $this->actingAs($admin);

    Livewire::test(DashboardComponent::class)
        ->call('notifyUser', $attendance->id);

    Mail::assertQueued(CheckoutReminderMail::class, function (CheckoutReminderMail $mail) use ($employee) {
        return $mail->user->is($employee);
    });

    expect($audit->records)->toHaveCount(1)
        ->and($audit->records[0]['action'])->toBe('Notification Sent')
        ->and($audit->records[0]['description'])->toBe('Sent checkout reminder to Reminder Employee');
});

test('admin dashboard movement chart separates excused and sick leave', function () {
    $admin = User::factory()->admin(true)->create();
    $excusedEmployee = User::factory()->create();
    $sickEmployee = User::factory()->create();
    $date = now()->startOfDay();

    Attendance::create([
        'user_id' => $excusedEmployee->id,
        'date' => $date->toDateString(),
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    Attendance::create([
        'user_id' => $sickEmployee->id,
        'date' => $date->toDateString(),
        'status' => 'sick',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    $chartData = app(AdminDashboardQueryService::class)->chartData($admin, $date, 'week_1');

    expect($chartData['excused'][array_key_last($chartData['excused'])])->toBe(1)
        ->and($chartData['sick'][array_key_last($chartData['sick'])])->toBe(1)
        ->and($chartData['absent'][array_key_last($chartData['absent'])])->toBeGreaterThanOrEqual(0);
});

test('admin dashboard upcoming leaves excludes dates before today', function () {
    $admin = User::factory()->admin(true)->create();
    $pastEmployee = User::factory()->create(['name' => 'Past Leave Employee']);
    $futureEmployee = User::factory()->create(['name' => 'Future Leave Employee']);

    Attendance::create([
        'user_id' => $pastEmployee->id,
        'date' => now()->subDay()->toDateString(),
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    Attendance::create([
        'user_id' => $futureEmployee->id,
        'date' => now()->addDay()->toDateString(),
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    $dashboard = app(AdminDashboardQueryService::class)->build($admin, now()->startOfDay());

    expect($dashboard['calendarLeaves']->pluck('title')->all())
        ->toContain('Future Leave Employee')
        ->not->toContain('Past Leave Employee');
});
