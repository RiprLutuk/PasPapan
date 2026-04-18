<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

test('regular user notifications page renders notification data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    DatabaseNotification::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'title' => 'User Notification Title',
            'message' => 'User notification body appears on the page.',
        ],
    ]);

    $this->get(route('notifications'))
        ->assertOk()
        ->assertSee('User Notification Title')
        ->assertSee('User notification body appears on the page.');
});

test('notifications page normalizes absolute notification urls to internal paths', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    DatabaseNotification::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'title' => 'Normalized Notification',
            'message' => 'This link should stay on the current host.',
            'url' => 'http://localhost:8000/overtime?tab=history',
        ],
    ]);

    $this->get(route('notifications'))
        ->assertOk()
        ->assertSee('href="/overtime?tab=history"', false);
});

test('admin notifications route uses dedicated admin page', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    DatabaseNotification::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $admin->id,
        'data' => [
            'title' => 'Admin Notification Title',
            'message' => 'Admin notification body appears on the page.',
        ],
    ]);

    $this->get(route('notifications'))
        ->assertRedirect(route('admin.notifications'));

    $this->get(route('admin.notifications'))
        ->assertOk()
        ->assertSee('Admin Notification Title')
        ->assertSee('Admin notification body appears on the page.')
        ->assertSee('Notification History');
});
