<?php

use App\Models\Holiday;
use Illuminate\Support\Facades\Http;

it('imports holidays from the configured api for the requested year only', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'success',
            'code' => 200,
            'data' => [
                [
                    'date' => '2026-01-01',
                    'description' => 'Tahun Baru 2026 Masehi',
                ],
                [
                    'date' => '2026-02-18',
                    'description' => 'Cuti Bersama Tahun Baru Imlek 2577 Kongzili',
                ],
            ],
            'message' => 'Holidays Found',
        ], 200),
    ]);

    $this->artisan('holidays:fetch', ['--year' => 2026])
        ->expectsOutput('Fetching holidays for 2026...')
        ->expectsOutput('Imported 2 holidays for 2026.')
        ->expectsOutput('Done.')
        ->assertExitCode(0);

    expect(Holiday::count())->toBe(2);

    expect(Holiday::query()
        ->whereDate('date', '2026-01-01')
        ->where('name', 'Tahun Baru 2026 Masehi')
        ->where('description', 'National Holiday')
        ->exists())->toBeTrue();

    expect(Holiday::query()
        ->whereDate('date', '2026-02-18')
        ->where('name', 'Cuti Bersama Tahun Baru Imlek 2577 Kongzili')
        ->where('description', 'Cuti Bersama')
        ->exists())->toBeTrue();

    expect(Holiday::query()->whereYear('date', 2027)->exists())->toBeFalse();
});

it('falls back to local holiday data when the remote api is unavailable', function () {
    Http::fake([
        '*' => Http::failedConnection(),
    ]);

    $this->artisan('holidays:fetch', ['--year' => 2026])
        ->expectsOutput('Fetching holidays for 2026...')
        ->expectsOutput('Imported 23 holidays for 2026.')
        ->expectsOutput('Done.')
        ->assertExitCode(0);

    expect(Holiday::query()
        ->whereDate('date', '2026-08-17')
        ->where('name', 'Hari Kemerdekaan Republik Indonesia ke 81')
        ->where('description', 'National Holiday')
        ->exists())->toBeTrue();

    expect(Holiday::query()
        ->whereDate('date', '2026-12-26')
        ->where('name', 'Cuti Bersama Hari Raya Natal')
        ->where('description', 'Cuti Bersama')
        ->exists())->toBeTrue();
});
