<?php

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Reimbursement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;

test('notifications page hides dismissed announcements for the current user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $visibleAnnouncement = Announcement::create([
        'title' => 'Visible Notice',
        'content' => 'This announcement should still be visible.',
        'priority' => 'high',
        'publish_date' => now()->toDateString(),
        'expire_date' => now()->addDay()->toDateString(),
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $dismissedAnnouncement = Announcement::create([
        'title' => 'Dismissed Notice',
        'content' => 'This announcement should be hidden.',
        'priority' => 'normal',
        'publish_date' => now()->toDateString(),
        'expire_date' => now()->addDay()->toDateString(),
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $dismissedAnnouncement->dismissedByUsers()->attach($user->id, [
        'dismissed_at' => now(),
    ]);

    Livewire::test(\App\Livewire\NotificationsPage::class)
        ->assertSee($visibleAnnouncement->title)
        ->assertDontSee($dismissedAnnouncement->title);
});

test('notifications page can mark all unread notifications as read', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    DatabaseNotification::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'title' => 'Test Notification',
            'message' => 'Please review this item.',
        ],
    ]);

    expect($user->fresh()->unreadNotifications()->count())->toBe(1);

    Livewire::test(\App\Livewire\NotificationsPage::class)
        ->call('markAllAsRead');

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('reimbursement page filters claims by status and type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Reimbursement::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'type' => 'medical',
        'amount' => 150000,
        'description' => 'Medical reimbursement',
        'status' => 'approved',
    ]);

    Reimbursement::create([
        'user_id' => $user->id,
        'date' => now()->subDay()->toDateString(),
        'type' => 'transport',
        'amount' => 50000,
        'description' => 'Transport reimbursement',
        'status' => 'pending',
    ]);

    Livewire::test(\App\Livewire\ReimbursementPage::class)
        ->set('statusFilter', 'approved')
        ->set('typeFilter', 'medical')
        ->assertSee('Medical reimbursement')
        ->assertDontSee('Transport reimbursement');
});

test('attendance history summary counts inferred absences for past working days', function () {
    Carbon::setTestNow('2026-04-03 09:00:00');

    $user = User::factory()->create();
    $this->actingAs($user);

    Attendance::create([
        'user_id' => $user->id,
        'date' => '2026-04-01',
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    Livewire::test(\App\Livewire\AttendanceHistoryComponent::class)
        ->set('selectedYear', '2026')
        ->set('selectedMonth', '04')
        ->assertViewHas('counts', function ($counts) {
            return ($counts['present'] ?? null) === 1
                && ($counts['absent'] ?? null) === 1;
        });

    Carbon::setTestNow();
});
