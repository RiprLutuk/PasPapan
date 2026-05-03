<?php

use App\Livewire\Admin\EmployeeDocumentRequestManager;
use App\Livewire\Admin\DocumentTemplateManager;
use App\Livewire\Admin\DocumentTemplateLibrary;
use App\Livewire\User\EmployeeDocumentRequestPage;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use App\Notifications\EmployeeDocumentRequestStatusUpdated;
use Database\Seeders\EmployeeDocumentTemplateSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    enableEnterpriseAttendanceForTests();
});

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
    EmployeeDocumentType::query()->updateOrCreate(
        ['code' => 'npwp'],
        [
            'name' => 'NPWP',
            'category' => 'finance',
            'is_active' => true,
            'employee_requestable' => false,
            'admin_requestable' => true,
            'requires_employee_upload' => true,
            'auto_generate_enabled' => false,
        ],
    );
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

test('admin requests an employee upload and employee submits private document', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('createRequest')
        ->set('targetUserId', $employee->id)
        ->set('documentType', 'npwp')
        ->set('purpose', 'Please upload NPWP for payroll tax data.')
        ->set('details', 'Finance needs the latest NPWP file.')
        ->set('dueDate', now()->addWeek()->toDateString())
        ->call('storeRequest')
        ->assertHasNoErrors();

    $request = EmployeeDocumentRequest::query()->firstOrFail();
    expect($request->request_source)->toBe(EmployeeDocumentRequest::SOURCE_ADMIN)
        ->and($request->status)->toBe(EmployeeDocumentRequest::STATUS_REQUESTED)
        ->and($request->due_date?->toDateString())->toBe(now()->addWeek()->toDateString());

    $this->actingAs($employee);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->call('prepareUpload', $request->id)
        ->set('attachment', UploadedFile::fake()->create('npwp.pdf', 100, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_UPLOADED)
        ->and($request->uploaded_path)->not->toBeNull();
    Storage::disk('local')->assertExists($request->uploaded_path);
});

test('admin generates a document from settings template', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create(['name' => 'Nadisha']);
    $type = EmployeeDocumentType::query()->firstOrCreate(
        ['code' => EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE],
        ['name' => 'Employment Certificate', 'auto_generate_enabled' => true],
    );
    $type->update(['auto_generate_enabled' => true]);
    EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Default SKK',
        'body' => '<h2>{{ request.document_type }}</h2><p>{{ employee.name }} - {{ request.purpose }}</p>',
        'is_active' => true,
    ]);
    $request = EmployeeDocumentRequest::create([
        'user_id' => $employee->id,
        'document_type_id' => $type->id,
        'document_type' => $type->code,
        'purpose' => 'Bank account opening.',
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('generate', $request->id)
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_GENERATED)
        ->and($request->generated_path)->not->toBeNull()
        ->and($request->generated_template_id)->not->toBeNull();
    Storage::disk('local')->assertExists($request->generated_path);
    expect(Storage::disk('local')->get($request->generated_path))->toContain('/Subtype /Image');
});

test('generated document status email attaches the pdf file', function () {
    Storage::fake('local');

    $employee = User::factory()->create();
    $request = EmployeeDocumentRequest::create([
        'user_id' => $employee->id,
        'document_type' => EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE,
        'purpose' => 'Bank account opening.',
        'status' => EmployeeDocumentRequest::STATUS_GENERATED,
        'generated_path' => 'employee-documents/generated/test-document.pdf',
    ]);
    Storage::disk('local')->put($request->generated_path, '%PDF-test');

    $mail = (new EmployeeDocumentRequestStatusUpdated($request))->toMail($employee);

    expect($mail->rawAttachments)->toHaveCount(1)
        ->and($mail->rawAttachments[0]['name'])->toContain('document-request-'.$request->id);
});

test('document template manager keeps one active template per document type and preserves used templates', function () {
    $admin = User::factory()->admin()->create();
    $type = EmployeeDocumentType::query()->create([
        'code' => 'bank_letter',
        'name' => 'Bank Letter',
        'category' => 'finance',
        'is_active' => true,
        'employee_requestable' => true,
        'admin_requestable' => true,
        'requires_employee_upload' => false,
        'auto_generate_enabled' => true,
    ]);
    $oldTemplate = EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Old Template',
        'body' => '<p>Old</p>',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(DocumentTemplateManager::class)
        ->set('templateEditorMode', 'html')
        ->set('documentTemplateForm.document_type_id', $type->id)
        ->set('documentTemplateForm.name', 'New Template')
        ->set('documentTemplateForm.body', '<p>{{ employee.name }}</p>')
        ->set('documentTemplateForm.paper_size', 'a4')
        ->set('documentTemplateForm.orientation', 'portrait')
        ->set('documentTemplateForm.is_active', true)
        ->call('saveDocumentTemplate')
        ->assertHasNoErrors();

    $newTemplate = EmployeeDocumentTemplate::query()->where('name', 'New Template')->firstOrFail();
    expect($newTemplate->is_active)->toBeTrue()
        ->and($oldTemplate->refresh()->is_active)->toBeFalse();

    Livewire::test(DocumentTemplateLibrary::class)
        ->call('duplicateTemplate', $newTemplate->id)
        ->assertHasNoErrors();

    expect(EmployeeDocumentTemplate::query()
        ->where('name', __('Copy of :name', ['name' => 'New Template']))
        ->where('is_active', false)
        ->exists())->toBeTrue();

    $request = EmployeeDocumentRequest::create([
        'user_id' => User::factory()->create()->id,
        'document_type_id' => $type->id,
        'document_type' => $type->code,
        'generated_template_id' => $newTemplate->id,
        'purpose' => 'Audit trail.',
        'status' => EmployeeDocumentRequest::STATUS_GENERATED,
    ]);

    Livewire::test(DocumentTemplateLibrary::class)
        ->call('confirmDeleteTemplate', $newTemplate->id)
        ->call('deleteTemplate')
        ->assertHasNoErrors();

    expect($request->refresh()->generated_template_id)->toBe($newTemplate->id)
        ->and($newTemplate->refresh()->is_active)->toBeFalse();
});

test('document template seeder creates two templates for every default document type', function () {
    $this->seed(EmployeeDocumentTemplateSeeder::class);

    $types = EmployeeDocumentType::query()
        ->withCount('templates')
        ->get();

    expect($types)->toHaveCount(7);

    foreach ($types as $type) {
        expect($type->templates_count)->toBeGreaterThanOrEqual(2)
            ->and($type->templates()->where('is_active', true)->count())->toBe(1);
    }
});

test('admin can create and immediately generate an auto template document request', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $type = EmployeeDocumentType::query()->create([
        'code' => 'employment_letter_auto',
        'name' => 'Employment Letter Auto',
        'category' => 'hr',
        'is_active' => true,
        'employee_requestable' => true,
        'admin_requestable' => true,
        'requires_employee_upload' => false,
        'auto_generate_enabled' => true,
    ]);
    EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Active Letter',
        'body' => '<p>{{ employee.name }} - {{ request.purpose }}</p>',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('createRequest')
        ->set('targetUserId', $employee->id)
        ->set('documentType', $type->code)
        ->set('purpose', 'Bank account opening.')
        ->set('generateImmediately', true)
        ->call('storeRequest')
        ->assertHasNoErrors();

    $request = EmployeeDocumentRequest::query()->where('document_type', $type->code)->firstOrFail();

    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_GENERATED)
        ->and($request->generated_path)->not->toBeNull();
    Storage::disk('local')->assertExists($request->generated_path);
});

test('admin can create document requests for multiple employees and process them in bulk', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $employees = User::factory()->count(2)->create();
    $type = EmployeeDocumentType::query()->create([
        'code' => 'bulk_letter',
        'name' => 'Bulk Letter',
        'category' => 'hr',
        'is_active' => true,
        'employee_requestable' => true,
        'admin_requestable' => true,
        'requires_employee_upload' => false,
        'auto_generate_enabled' => true,
    ]);
    EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Bulk Letter Active',
        'body' => '<p>{{ employee.name }} - {{ request.purpose }}</p>',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('createRequest')
        ->set('targetUserIds', $employees->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->set('documentType', $type->code)
        ->set('purpose', 'Mass bank administration.')
        ->call('storeRequest')
        ->assertHasNoErrors();

    $requests = EmployeeDocumentRequest::query()->where('document_type', $type->code)->get();
    expect($requests)->toHaveCount(2);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->set('selectedRequestIds', $requests->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('bulkGenerate')
        ->assertHasNoErrors();

    $requests->each(function (EmployeeDocumentRequest $request): void {
        $request->refresh();
        expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_GENERATED)
            ->and($request->generated_path)->not->toBeNull();
        Storage::disk('local')->assertExists($request->generated_path);
    });
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
