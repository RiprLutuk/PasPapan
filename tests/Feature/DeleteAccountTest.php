<?php

use App\Livewire\Profile\RequestAccountDeletionForm;
use App\Models\User;
use Laravel\Jetstream\Features;
use Livewire\Livewire;

test('user accounts can request deletion', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test(RequestAccountDeletionForm::class)
        ->set('password', 'password')
        ->set('reason', 'I have left the company and need this account removed.')
        ->call('deleteUser');

    $user->refresh();

    expect($user)->not->toBeNull()
        ->and($user->employment_status)->toBe(User::EMPLOYMENT_STATUS_DELETION_REQUESTED)
        ->and($user->account_deletion_reason)->toBe('I have left the company and need this account removed.')
        ->and($user->account_deletion_requested_at)->not->toBeNull();
})->skip(function () {
    return ! Features::hasAccountDeletionFeatures();
}, 'Account deletion is not enabled.');

test('correct password must be provided before account deletion can be requested', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test(RequestAccountDeletionForm::class)
        ->set('password', 'wrong-password')
        ->set('reason', 'I have left the company and need this account removed.')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull()
        ->and($user->fresh()->employment_status)->toBe(User::EMPLOYMENT_STATUS_ACTIVE);
})->skip(function () {
    return ! Features::hasAccountDeletionFeatures();
}, 'Account deletion is not enabled.');
