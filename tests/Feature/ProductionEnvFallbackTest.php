<?php

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

test('production bootstrap ignores blank infrastructure env values', function () {
    $php = (new PhpExecutableFinder)->find(false) ?: PHP_BINARY;

    $process = new Process([
        $php,
        'artisan',
        'about',
        '--only=environment',
    ], base_path(), [
        'APP_ENV' => 'testing',
        'BROADCAST_CONNECTION' => '',
        'CACHE_STORE' => '',
        'DB_CONNECTION' => '',
        'FILESYSTEM_DISK' => '',
        'LOG_CHANNEL' => '',
        'QUEUE_CONNECTION' => '',
        'SESSION_DRIVER' => '',
    ]);

    $process->setTimeout(30);
    $process->run();

    expect($process->isSuccessful())
        ->toBeTrue($process->getErrorOutput().$process->getOutput());
});
