<?php

use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\CompanyAsset;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('policies cover attendance appraisal reimbursement asset and payslip access', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $attendance = Attendance::create([
        'user_id' => $owner->id,
        'date' => now()->toDateString(),
        'status' => 'excused',
        'note' => 'Family matter',
        'attachment' => 'secure/attendance-proof.pdf',
    ]);

    $reimbursement = Reimbursement::create([
        'user_id' => $owner->id,
        'date' => now()->toDateString(),
        'type' => 'medical',
        'amount' => 150000,
        'description' => 'Clinic reimbursement',
        'attachment' => 'secure/reimbursement-proof.pdf',
        'status' => 'pending',
    ]);

    $selfAssessment = Appraisal::create([
        'user_id' => $owner->id,
        'evaluator_id' => $admin->id,
        'period_month' => 1,
        'period_year' => 2026,
        'status' => 'self_assessment',
    ]);

    $completedAppraisal = Appraisal::create([
        'user_id' => $owner->id,
        'evaluator_id' => $admin->id,
        'period_month' => 2,
        'period_year' => 2026,
        'status' => 'completed',
    ]);

    $asset = CompanyAsset::create([
        'name' => 'Laptop Kerja',
        'type' => 'electronics',
        'user_id' => $owner->id,
        'date_assigned' => now()->toDateString(),
        'status' => 'assigned',
    ]);

    $payroll = Payroll::create([
        'user_id' => $owner->id,
        'month' => 1,
        'year' => 2026,
        'status' => 'paid',
    ]);

    expect(Gate::forUser($owner)->allows('view', $attendance))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $attendance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $attendance))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $reimbursement))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $reimbursement))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $reimbursement))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('exportPdf', $selfAssessment))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('exportPdf', $selfAssessment))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('exportPdf', $selfAssessment))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $asset))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $asset))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $asset))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('download', $payroll))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('selfAssess', $selfAssessment))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('acknowledge', $selfAssessment))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('acknowledge', $completedAppraisal))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('returnAsset', $asset))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('returnAsset', $asset))->toBeFalse();
});

test('backup policy only allows admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($user)->allows('viewAny', SystemBackupRun::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewAny', SystemBackupRun::class))->toBeTrue();
});

test('attachment and appraisal export routes deny unrelated users', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $attendance = Attendance::create([
        'user_id' => $owner->id,
        'date' => now()->toDateString(),
        'status' => 'excused',
        'note' => 'Personal matter',
        'attachment' => 'secure/attendance-proof.pdf',
    ]);

    $reimbursement = Reimbursement::create([
        'user_id' => $owner->id,
        'date' => now()->toDateString(),
        'type' => 'transport',
        'amount' => 50000,
        'description' => 'Taxi reimbursement',
        'attachment' => 'secure/reimbursement-proof.pdf',
        'status' => 'pending',
    ]);

    $appraisal = Appraisal::create([
        'user_id' => $owner->id,
        'evaluator_id' => $admin->id,
        'period_month' => 3,
        'period_year' => 2026,
        'status' => 'completed',
    ]);

    $this->actingAs($otherUser)
        ->get(route('attendance.attachment.download', $attendance))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->get(route('reimbursement.attachment.download', $reimbursement))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->get(route('appraisal.export-pdf', $appraisal))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('attendance.attachment.download', $attendance))
        ->assertNotFound();

    $this->actingAs($owner)
        ->get(route('reimbursement.attachment.download', $reimbursement))
        ->assertNotFound();
});
