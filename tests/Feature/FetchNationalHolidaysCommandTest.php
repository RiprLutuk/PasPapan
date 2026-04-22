<?php

use App\Models\Holiday;
use Illuminate\Support\Facades\Http;

it('imports holidays from the configured api for the requested year only when no curated local data exists', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'success',
            'code' => 200,
            'data' => [
                [
                    'date' => '2030-01-01',
                    'description' => 'Tahun Baru 2030 Masehi',
                ],
                [
                    'date' => '2030-02-08',
                    'description' => 'Cuti Bersama Tahun Baru Imlek 2581 Kongzili',
                ],
            ],
            'message' => 'Holidays Found',
        ], 200),
    ]);

    $this->artisan('holidays:fetch', ['--year' => 2030])
        ->expectsOutput('Fetching holidays for 2030...')
        ->expectsOutput('Imported 2 holidays for 2030.')
        ->expectsOutput('Done.')
        ->assertExitCode(0);

    expect(Holiday::count())->toBe(2);

    expect(Holiday::query()
        ->whereDate('date', '2030-01-01')
        ->where('name', 'Tahun Baru 2030 Masehi')
        ->where('description', 'National Holiday')
        ->exists())->toBeTrue();

    expect(Holiday::query()
        ->whereDate('date', '2030-02-08')
        ->where('name', 'Cuti Bersama Tahun Baru Imlek 2581 Kongzili')
        ->where('description', 'Cuti Bersama')
        ->exists())->toBeTrue();

    expect(Holiday::query()->whereYear('date', 2031)->exists())->toBeFalse();
});

it('uses curated local holiday data when available', function () {
    Http::fake([
        '*' => Http::failedConnection(),
    ]);

    $this->artisan('holidays:fetch', ['--year' => 2026])
        ->expectsOutput('Fetching holidays for 2026...')
        ->expectsOutput('Using curated local holiday data for 2026.')
        ->expectsOutput('Imported 25 holidays for 2026.')
        ->expectsOutput('Done.')
        ->assertExitCode(0);

    expect(Holiday::query()
        ->whereDate('date', '2026-08-17')
        ->where('name', 'Proklamasi Kemerdekaan')
        ->where('description', 'National Holiday')
        ->exists())->toBeTrue();

    expect(Holiday::query()
        ->whereDate('date', '2026-12-24')
        ->where('name', 'Cuti Bersama Kelahiran Yesus Kristus')
        ->where('description', 'Cuti Bersama')
        ->exists())->toBeTrue();
});

it('can disable curated local data and fall back to third-party apis only', function () {
    config()->set('holidays.local_enabled', false);
    config()->set('holidays.sources', [
        'https://third-party.test/api',
    ]);

    Http::fake([
        'https://third-party.test/api*' => Http::response([
            'data' => [
                [
                    'date' => '2027-01-01',
                    'description' => 'Tahun Baru 2027 Masehi',
                ],
                [
                    'date' => '2027-02-11',
                    'description' => 'Cuti Bersama Tahun Baru Imlek 2578 Kongzili',
                ],
            ],
        ], 200),
    ]);

    $this->artisan('holidays:fetch', ['--year' => 2027])
        ->expectsOutput('Fetching holidays for 2027...')
        ->expectsOutput('Imported 2 holidays for 2027.')
        ->expectsOutput('Done.')
        ->assertExitCode(0);

    expect(Holiday::query()
        ->whereDate('date', '2027-01-01')
        ->where('name', 'Tahun Baru 2027 Masehi')
        ->where('description', 'National Holiday')
        ->exists())->toBeTrue()
        ->and(Holiday::query()
            ->whereDate('date', '2027-02-11')
            ->where('description', 'Cuti Bersama')
            ->exists())->toBeTrue();
});

it('removes stale holiday rows for the imported year while keeping the curated set', function () {
    Holiday::create([
        'date' => '2026-04-19',
        'name' => 'Hari Raya Idul Fitri 1447H',
        'description' => 'National Holiday',
        'is_recurring' => false,
    ]);

    Holiday::create([
        'date' => '2026-04-21',
        'name' => 'Cuti Bersama Hari Raya Idul Fitri 1447H',
        'description' => 'Cuti Bersama',
        'is_recurring' => false,
    ]);

    $this->artisan('holidays:fetch', ['--year' => 2026])
        ->assertExitCode(0);

    expect(Holiday::query()->whereDate('date', '2026-04-19')->exists())->toBeFalse()
        ->and(Holiday::query()->whereDate('date', '2026-04-21')->exists())->toBeFalse()
        ->and(Holiday::query()->whereDate('date', '2026-03-21')->where('name', 'Idulfitri 1447 H')->exists())->toBeTrue()
        ->and(Holiday::query()->whereDate('date', '2026-03-24')->where('description', 'Cuti Bersama')->exists())->toBeTrue();
});
