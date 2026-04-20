<?php

use App\Livewire\Admin\ReimbursementManager;
use App\Livewire\User\TeamApprovals;
use App\Models\CashAdvance;
use App\Models\Division;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Reimbursement;
use App\Models\User;
use App\Notifications\CashAdvanceUpdated;
use App\Notifications\ReimbursementStatusUpdated;
use App\Support\TeamApprovalQueryService;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function createApprovalHierarchy(string $divisionName = 'Operations'): array
{
    $division = Division::create(['name' => $divisionName]);

    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => $divisionName . ' Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => $divisionName . ' Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $managerTitle->id,
    ]);

    $employee = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $staffTitle->id,
    ]);

    return [$manager, $employee, $division, $managerTitle, $staffTitle];
}

function createFinanceHead(): User
{
    $division = Division::create(['name' => 'Finance']);
    $level = JobLevel::create(['name' => 'Finance Head', 'rank' => 2]);
    $title = JobTitle::create([
        'name' => 'Finance Manager',
        'job_level_id' => $level->id,
        'division_id' => $division->id,
    ]);

    return User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $title->id,
    ]);
}

test('supervisor approval forwards reimbursement to finance', function () {
    Notification::fake();

    [$manager, $employee] = createApprovalHierarchy();

    $reimbursement = Reimbursement::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'type' => 'Transport',
        'amount' => 150000,
        'description' => 'Airport pickup',
        'status' => 'pending',
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'reimbursements')
        ->call('approveReimbursement', $reimbursement->id);

    $reimbursement->refresh();

    expect($reimbursement->status)->toBe('pending_finance')
        ->and($reimbursement->head_approved_by)->toBe($manager->id)
        ->and($reimbursement->head_approved_at)->not->toBeNull()
        ->and($reimbursement->finance_approved_by)->toBeNull();

    Notification::assertSentTo($employee, ReimbursementStatusUpdated::class);
});

test('team approval history keeps finance-forwarded requests visible to supervisors', function () {
    [$manager, $employee] = createApprovalHierarchy();

    $reimbursement = Reimbursement::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'type' => 'Meal',
        'amount' => 50000,
        'description' => 'Client lunch',
        'status' => 'pending_finance',
        'head_approved_by' => $manager->id,
        'head_approved_at' => now(),
    ]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 300000,
        'purpose' => 'Team transport advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending_finance',
        'head_approved_by' => $manager->id,
        'head_approved_at' => now(),
    ]);

    $service = app(TeamApprovalQueryService::class);

    $reimbursementHistory = collect($service->history($manager, 'reimbursements')->items());
    $cashAdvanceHistory = collect($service->history($manager, 'kasbons')->items());

    expect($reimbursementHistory->pluck('id'))->toContain($reimbursement->id)
        ->and($cashAdvanceHistory->pluck('id'))->toContain($advance->id);
});

test('supervisor approval forwards cash advance to finance', function () {
    Notification::fake();

    [$manager, $employee] = createApprovalHierarchy();

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 700000,
        'purpose' => 'Project field advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'kasbons')
        ->call('approveKasbon', $advance->id);

    $advance->refresh();

    expect($advance->status)->toBe('pending_finance')
        ->and($advance->head_approved_by)->toBe($manager->id)
        ->and($advance->head_approved_at)->not->toBeNull()
        ->and($advance->approved_by)->toBeNull();

    Notification::assertSentTo($employee, CashAdvanceUpdated::class);
});

test('finance head can finalize pending finance reimbursements from manager queue', function () {
    Notification::fake();

    [, $employee] = createApprovalHierarchy();
    $financeHead = createFinanceHead();

    $reimbursement = Reimbursement::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'type' => 'Hotel',
        'amount' => 450000,
        'description' => 'Site visit stay',
        'status' => 'pending_finance',
    ]);

    $this->actingAs($financeHead);

    Livewire::test(ReimbursementManager::class)
        ->set('statusFilter', 'pending_finance')
        ->call('approve', $reimbursement->id);

    $reimbursement->refresh();

    expect($reimbursement->status)->toBe('approved')
        ->and($reimbursement->finance_approved_by)->toBe($financeHead->id)
        ->and($reimbursement->finance_approved_at)->not->toBeNull()
        ->and($reimbursement->approved_by)->toBe($financeHead->id);

    Notification::assertSentTo($employee, ReimbursementStatusUpdated::class);
});
