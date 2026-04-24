<?php

use App\Livewire\Admin\SystemMaintenance;

function verifyBackupSqlForTest(string $sql): string
{
    $component = new SystemMaintenance;
    $method = new ReflectionMethod(SystemMaintenance::class, 'verifiedBackupSql');
    $method->setAccessible(true);

    return $method->invoke($component, $sql);
}

test('database restore accepts only signed application backups', function () {
    $sql = "-- Absensi GPS & Enterprise Database Backup\nSET FOREIGN_KEY_CHECKS=0;\nSET FOREIGN_KEY_CHECKS=1;\n";
    $signature = hash_hmac('sha256', $sql, config('app.key'));

    expect(verifyBackupSqlForTest($sql."\n-- APP_BACKUP_SIGNATURE: {$signature}\n"))->toBe($sql);
});

test('database restore rejects unsigned or modified sql backups', function () {
    $sql = "-- Absensi GPS & Enterprise Database Backup\nSET FOREIGN_KEY_CHECKS=0;\n";
    $signature = hash_hmac('sha256', $sql, config('app.key'));

    expect(fn () => verifyBackupSqlForTest($sql))->toThrow(Exception::class);
    expect(fn () => verifyBackupSqlForTest($sql."DROP TABLE users;\n-- APP_BACKUP_SIGNATURE: {$signature}\n"))->toThrow(Exception::class);
});
