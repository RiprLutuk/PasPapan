<?php

use App\Livewire\User\ReimbursementPage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

test('shared file input renders a label backed file control', function () {
    $html = Blade::render('<x-forms.file-input id="leave-attachment-upload" name="attachment" />');

    expect($html)
        ->toContain('type="file"')
        ->toContain('class="sr-only"')
        ->toContain('id="leave-attachment-upload"')
        ->toContain('for="leave-attachment-upload"')
        ->not->toContain('opacity-0')
        ->not->toContain('$refs.file.click()');
});

test('upload controls use labels instead of hidden inputs or click proxies', function () {
    $files = [
        resource_path('views/components/forms/file-input.blade.php'),
        resource_path('views/attendances/apply-leave.blade.php'),
        resource_path('views/profile/update-profile-information-form.blade.php'),
        resource_path('views/livewire/user/reimbursement-page.blade.php'),
        resource_path('views/livewire/admin/import-export/user.blade.php'),
        resource_path('views/livewire/admin/import-export/attendance.blade.php'),
        resource_path('views/livewire/admin/master-data/admin.blade.php'),
    ];

    $expectedRelationships = [
        resource_path('views/attendances/apply-leave.blade.php') => ['attachment'],
        resource_path('views/profile/update-profile-information-form.blade.php') => ['profile-photo-input'],
        resource_path('views/livewire/user/reimbursement-page.blade.php') => ['reimbursement-attachment-upload'],
        resource_path('views/livewire/admin/import-export/user.blade.php') => ['user-import-file-upload'],
        resource_path('views/livewire/admin/import-export/attendance.blade.php') => ['attendance-import-file-upload'],
        resource_path('views/livewire/admin/master-data/admin.blade.php') => ['create_photo', 'edit_photo'],
    ];

    foreach ($files as $file) {
        $contents = File::get($file);

        expect($contents)
            ->not->toMatch('/<input[^>]*type=["\']file["\'][^>]*class=["\'][^"\']*\bhidden\b[^"\']*["\']/')
            ->not->toMatch('/<input[^>]*class=["\'][^"\']*\bhidden\b[^"\']*["\'][^>]*type=["\']file["\']/')
            ->not->toMatch('/<input[^>]*type=["\']file["\'][^>]*opacity-\[0\.01\]/')
            ->not->toContain('showPicker()')
            ->not->toContain('.click()');

        foreach ($expectedRelationships[$file] ?? [] as $id) {
            expect($contents)
                ->toContain('id="'.$id.'"')
                ->toContain('for="'.$id.'"');
        }
    }
});

test('leave attachment upload uses native file input for capacitor webview taps', function () {
    $contents = File::get(resource_path('views/attendances/apply-leave.blade.php'));

    expect($contents)
        ->toContain('type="file"')
        ->toContain('name="attachment"')
        ->toContain('id="attachment"')
        ->toContain('for="attachment"')
        ->toContain('class="sr-only"')
        ->toContain('accept="image/*,application/pdf"')
        ->not->toContain('showPicker()')
        ->not->toContain('.click()')
        ->not->toContain('opacity-[0.01]');
});

test('android manifest declares gallery and media permissions for webview uploads', function () {
    $contents = File::get(base_path('android/app/src/main/AndroidManifest.xml'));

    expect($contents)
        ->toContain('android.permission.READ_EXTERNAL_STORAGE')
        ->toContain('android:maxSdkVersion="32"')
        ->toContain('android.permission.READ_MEDIA_IMAGES')
        ->toContain('android.permission.READ_MEDIA_VIDEO')
        ->toContain('android.permission.READ_MEDIA_VISUAL_USER_SELECTED');
});

test('reimbursement attachment validation still rejects unsafe files', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ReimbursementPage::class)
        ->set('date', now()->toDateString())
        ->set('type', 'medical')
        ->set('amount', 100000)
        ->set('description', 'Medical reimbursement receipt')
        ->set('attachment', UploadedFile::fake()->create('malware.exe', 1, 'application/x-msdownload'))
        ->call('save')
        ->assertHasErrors(['attachment']);
});
