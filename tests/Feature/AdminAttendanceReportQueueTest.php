<?php

use App\Jobs\ProcessAttendanceReportExportRun;
use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('admin attendance report export queues pdf and excel runs', function (string $format) {
    enableEnterpriseAttendanceForTests();
    Queue::fake();

    $admin = User::factory()->admin(true)->create();

    $response = $this->actingAs($admin)->get(route('admin.attendances.report', [
        'startDate' => '2026-04-01',
        'endDate' => '2026-04-26',
        'format' => $format,
    ]));

    $response->assertRedirect(route('admin.attendances'));

    $run = ImportExportRun::query()
        ->where('resource', 'attendance_report')
        ->where('operation', 'export')
        ->latest('id')
        ->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('queued')
        ->and($run->requested_by_user_id)->toBe($admin->id)
        ->and($run->meta['startDate'])->toBe('2026-04-01')
        ->and($run->meta['endDate'])->toBe('2026-04-26')
        ->and($run->meta['format'])->toBe($format);

    Queue::assertPushed(ProcessAttendanceReportExportRun::class, fn (ProcessAttendanceReportExportRun $job) => $job->runId === $run->id);
})->with(['pdf', 'excel']);
