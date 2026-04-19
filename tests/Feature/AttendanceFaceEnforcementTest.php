<?php

use App\Contracts\AttendanceServiceInterface;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Setting;
use App\Models\User;
use App\Services\Attendance\CommunityService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('admin settings shows face verification as the single face id attendance toggle', function () {
    $admin = User::factory()->admin(superadmin: true)->create();

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        [
            'value' => '0',
            'group' => 'attendance',
            'type' => 'boolean',
            'description' => 'Require Face ID enrollment before attendance',
        ]
    );
    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        [
            'value' => '1',
            'group' => 'attendance',
            'type' => 'boolean',
            'description' => 'Require Face ID verification during attendance capture',
        ]
    );

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Admin\Settings::class)
        ->assertSee('attendance.require_face_verification')
        ->assertDontSee('attendance.require_face_enrollment');
});

test('community service uses dedicated face enrollment setting instead of require photo setting', function () {
    Setting::updateOrCreate(
        ['key' => 'feature.require_photo'],
        ['value' => '1', 'group' => 'features', 'type' => 'boolean']
    );

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );

    Setting::flushCache('feature.require_photo');
    Setting::flushCache('attendance.require_face_enrollment');

    $service = new CommunityService();

    expect($service->shouldEnforceFaceEnrollment())->toBeFalse();

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '1', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache('attendance.require_face_enrollment');

    expect($service->shouldEnforceFaceEnrollment())->toBeTrue();
});

test('home attendance status ignores face enrollment setting when enterprise attendance is locked', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '1', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache('attendance.require_face_enrollment');

    app()->instance(AttendanceServiceInterface::class, new class implements AttendanceServiceInterface {
        public function storeAttachment(UploadedFile $file): string { return 'ignored'; }
        public function getAttachmentUrl(Attendance $attendance): string|array|null { return null; }
        public function shouldEnforceFaceEnrollment(): bool { return false; }
        public function storeAttendancePhoto(string $base64Data, string $filename): string { return $filename; }
        public function registerFace(User $user, array $descriptor): void {}
        public function removeFace(User $user): void {}
    });

    expect(\App\Helpers\Editions::attendanceLocked())->toBeTrue();

    Livewire::test(\App\Livewire\HomeAttendanceStatus::class)
        ->assertSet('requiresFaceEnrollment', false);
});

test('scan component does not redirect to face enrollment when enterprise attendance is locked', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '1', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache('attendance.require_face_enrollment');

    app()->instance(AttendanceServiceInterface::class, new class implements AttendanceServiceInterface {
        public function storeAttachment(UploadedFile $file): string { return 'ignored'; }
        public function getAttachmentUrl(Attendance $attendance): string|array|null { return null; }
        public function shouldEnforceFaceEnrollment(): bool { return false; }
        public function storeAttendancePhoto(string $base64Data, string $filename): string { return $filename; }
        public function registerFace(User $user, array $descriptor): void {}
        public function removeFace(User $user): void {}
    });

    expect(\App\Helpers\Editions::attendanceLocked())->toBeTrue();

    Livewire::test(\App\Livewire\ScanComponent::class)
        ->assertNoRedirect();
});

test('home attendance status requires face enrollment when face verification is enabled', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    enableEnterpriseAttendanceForTests();

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        ['value' => '1', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache('attendance.require_face_enrollment');
    Setting::flushCache('attendance.require_face_verification');

    app()->instance(AttendanceServiceInterface::class, new class implements AttendanceServiceInterface {
        public function storeAttachment(UploadedFile $file): string { return 'ignored'; }
        public function getAttachmentUrl(Attendance $attendance): string|array|null { return null; }
        public function shouldEnforceFaceEnrollment(): bool { return false; }
        public function storeAttendancePhoto(string $base64Data, string $filename): string { return $filename; }
        public function registerFace(User $user, array $descriptor): void {}
        public function removeFace(User $user): void {}
    });

    Livewire::test(\App\Livewire\HomeAttendanceStatus::class)
        ->assertSet('requiresFaceEnrollment', true);
});

test('scan component redirects to face enrollment when face verification is enabled and user has no face id', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    enableEnterpriseAttendanceForTests();

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        ['value' => '1', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache('attendance.require_face_enrollment');
    Setting::flushCache('attendance.require_face_verification');

    app()->instance(AttendanceServiceInterface::class, new class implements AttendanceServiceInterface {
        public function storeAttachment(UploadedFile $file): string { return 'ignored'; }
        public function getAttachmentUrl(Attendance $attendance): string|array|null { return null; }
        public function shouldEnforceFaceEnrollment(): bool { return false; }
        public function storeAttendancePhoto(string $base64Data, string $filename): string { return $filename; }
        public function registerFace(User $user, array $descriptor): void {}
        public function removeFace(User $user): void {}
    });

    Livewire::test(\App\Livewire\ScanComponent::class)
        ->assertRedirect(route('face.enrollment'));
});

test('home attendance status ignores pending overtime for active overtime label', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Overtime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->setTime(18, 0),
        'end_time' => now()->setTime(20, 0),
        'duration' => 120,
        'reason' => 'Need extra work tonight',
        'status' => 'pending',
    ]);

    Livewire::test(\App\Livewire\HomeAttendanceStatus::class)
        ->assertSet('hasApprovedOvertime', false);
});

test('home attendance status keeps approved overtime for active overtime label', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Overtime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->setTime(18, 0),
        'end_time' => now()->setTime(20, 0),
        'duration' => 120,
        'reason' => 'Approved overtime',
        'status' => 'approved',
    ]);

    Livewire::test(\App\Livewire\HomeAttendanceStatus::class)
        ->assertSet('hasApprovedOvertime', true);
});
