<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;

class ScheduledArtisanCommand
{
    public function __construct(
        private readonly string $command,
        private readonly array $parameters = [],
    ) {}

    public function __invoke(): void
    {
        $exitCode = Artisan::call($this->command, $this->parameters, new NullOutput);

        if ($exitCode !== 0) {
            throw new RuntimeException("Scheduled command [{$this->command}] failed with exit code {$exitCode}.");
        }
    }
}
