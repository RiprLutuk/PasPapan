<?php

use App\Exports\AttendanceTemplateExport;
use App\Exports\UsersTemplateExport;
use App\Imports\UsersImport;
use App\Models\Division;
use App\Models\Education;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user import template matches current import structure', function () {
    $headings = (new UsersTemplateExport)->headings();

    expect($headings)->toContain(
        'ID',
        'NIP',
        'Email',
        'Employment Status',
        'Language',
        'Manager NIP',
        'Manager Email',
        'Provinsi Kode',
        'Kabupaten Kode',
        'Kecamatan Kode',
        'Kelurahan Kode',
        'Email Verified At',
    );
});

test('attendance import template uses datetime punch examples and overnight row', function () {
    $export = new AttendanceTemplateExport;

    expect($export->headings())->toContain('nip', 'date', 'time_in', 'time_out', 'status', 'shift')
        ->and($export->array()[0][2])->toBe('2026-04-20 07:02')
        ->and($export->array()[2][3])->toBe('2026-04-21 07:03');
});

test('user import updates existing users and creates new users with current fields', function () {
    Division::create(['name' => 'Operations']);
    JobTitle::create(['name' => 'Staff']);
    Education::create(['name' => 'S1']);

    $existing = User::factory()->create([
        'nip' => '0000000000001001',
        'email' => 'existing@example.com',
        'phone' => '081234560001',
        'name' => 'Old Name',
        'password' => Hash::make('old-password'),
    ]);
    $manager = User::factory()->create([
        'nip' => '0000000000099999',
        'email' => 'manager@example.com',
    ]);

    $import = new UsersImport;
    $import->model([
        'id' => '',
        'nip' => '0000000000001001',
        'name' => 'Updated Name',
        'email' => 'existing@example.com',
        'group' => 'user',
        'password' => '',
        'phone' => '081234560001',
        'gender' => 'female',
        'employment_status' => User::EMPLOYMENT_STATUS_ACTIVE,
        'language' => 'id',
        'basic_salary' => 5000000,
        'hourly_rate' => 28902,
        'division' => 'Operations',
        'job_title' => 'Staff',
        'education' => 'S1',
        'manager_nip' => $manager->nip,
        'birth_date' => '1996-04-20',
        'birth_place' => 'Jakarta',
        'address' => 'Jl. Merdeka No. 1',
    ]);
    $import->model([
        'id' => '',
        'nip' => '0000000000001002',
        'name' => 'New User',
        'email' => 'new.user@example.com',
        'group' => 'user',
        'password' => 'password',
        'phone' => '081234560002',
        'gender' => 'male',
        'employment_status' => User::EMPLOYMENT_STATUS_ACTIVE,
        'language' => 'id',
        'basic_salary' => 5000000,
        'hourly_rate' => 28902,
        'division' => 'Operations',
        'job_title' => 'Staff',
        'education' => 'S1',
        'manager_email' => $manager->email,
        'birth_date' => '1996-04-20',
        'birth_place' => 'Jakarta',
        'address' => 'Jl. Merdeka No. 2',
    ]);

    expect($existing->refresh()->name)->toBe('Updated Name')
        ->and($existing->gender)->toBe('female')
        ->and($existing->manager_id)->toBe($manager->id)
        ->and(User::query()->where('email', 'new.user@example.com')->exists())->toBeTrue();
});
