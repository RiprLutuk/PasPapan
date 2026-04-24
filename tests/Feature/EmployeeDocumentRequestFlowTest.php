<?php

use App\Livewire\Admin\EmployeeDocumentRequestManager;
use App\Livewire\User\EmployeeDocumentRequestPage;
use App\Models\EmployeeDocumentRequest;
use App\Models\User;
use App\Notifications\EmployeeDocumentRequestStatusUpdated;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('employee submits a document request for admin fulfillment', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->call('create')
        ->set('documentType', EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE)
        ->set('purpose', 'Bank account opening requirement.')
        ->set('details', 'Please include job title and active employment status.')
        ->call('store')
        ->assertHasNoErrors();

    $request = EmployeeDocumentRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->user_id)->toBe($user->id)
        ->and($request->document_type)->toBe(EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE)
        ->and($request->purpose)->toBe('Bank account opening requirement.')
        ->and($request->status)->toBe(EmployeeDocumentRequest::STATUS_PENDING);
});

test('admin marks a document request as ready and notifies employee', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $request = EmployeeDocumentRequest::create([
        'user_id' => $employee->id,
        'document_type' => EmployeeDocumentRequest::TYPE_SALARY_STATEMENT,
        'purpose' => 'Apartment rental verification.',
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('confirmReady', $request->id)
        ->set('reviewNote', 'Ready for pickup at HR desk.')
        ->call('markReady')
        ->assertHasNoErrors();

    $request->refresh();

    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_READY)
        ->and($request->reviewed_by)->toBe($admin->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($request->fulfillment_note)->toBe('Ready for pickup at HR desk.')
        ->and($request->rejection_note)->toBeNull();

    Notification::assertSentTo($employee, EmployeeDocumentRequestStatusUpdated::class);
});

test('admin rejects a document request and stores rejection note', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $request = EmployeeDocumentRequest::create([
        'user_id' => $employee->id,
        'document_type' => EmployeeDocumentRequest::TYPE_VISA_LETTER,
        'purpose' => 'Travel visa application.',
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('confirmReject', $request->id)
        ->set('reviewNote', 'Please update your address profile first.')
        ->call('reject')
        ->assertHasNoErrors();

    $request->refresh();

    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_REJECTED)
        ->and($request->reviewed_by)->toBe($admin->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($request->rejection_note)->toBe('Please update your address profile first.');

    Notification::assertSentTo($employee, EmployeeDocumentRequestStatusUpdated::class);
});
