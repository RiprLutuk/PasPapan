<?php

namespace App\Contracts;

interface AuditServiceInterface
{
    /**
     * Record an activity log.
     *
     * @return mixed
     */
    public function record(string $action, ?string $description = null);
}
