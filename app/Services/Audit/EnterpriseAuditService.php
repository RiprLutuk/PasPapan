<?php

namespace App\Services\Audit;

use App\Contracts\AuditServiceInterface;
use App\Models\ActivityLog;
use App\Services\Enterprise\LicenseGuard;

class EnterpriseAuditService implements AuditServiceInterface
{
    public function __construct()
    {
        LicenseGuard::check();
    }

    public function record(string $action, ?string $description = null)
    {
        $userId = auth()->id();
        $ip = request()->ip();

        // Throttling: Check for recent similar log (e.g., within last 1 hour)
        $recentLog = ActivityLog::where('user_id', $userId)
            ->where('action', $action)
            ->where('description', $description)
            ->where('created_at', '>=', now()->subHour())
            ->latest()
            ->first();

        if ($recentLog) {
            $recentLog->increment('count');
            $recentLog->touch(); // Update updated_at

            return $recentLog;
        }

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'count' => 1,
        ]);
    }
}
