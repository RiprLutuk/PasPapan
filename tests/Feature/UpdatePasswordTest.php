<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Jetstream\Http\Livewire\UpdatePasswordForm;
use Livewire\Livewire;

test('password can be updated', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test(UpdatePasswordForm::class)
        ->set('state', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    expect(Schema::hasColumn('users', 'raw_password'))->toBeFalse();
});

test('superadmin can update own password from profile', function () {
    $this->actingAs($user = User::factory()->admin(true)->create());

    Livewire::test(UpdatePasswordForm::class)
        ->set('state', [
            'current_password' => 'password',
            'password' => 'new-superadmin-password',
            'password_confirmation' => 'new-superadmin-password',
        ])
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-superadmin-password', $user->fresh()->password))->toBeTrue();
});

test('current password must be correct', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test(UpdatePasswordForm::class)
        ->set('state', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('new passwords must match', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test(UpdatePasswordForm::class)
        ->set('state', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'wrong-password',
        ])
        ->call('updatePassword')
        ->assertHasErrors(['password']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
