<?php

namespace App\Services\Attendance;

class LeaveRequestResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $error = null,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function error(string $error): self
    {
        return new self(false, $error);
    }
}
