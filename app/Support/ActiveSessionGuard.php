<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActiveSessionGuard
{
    public function hasActiveSession(User $user): bool
    {
        if (config('session.driver') !== 'database') {
            return false;
        }

        $table = config('session.table', 'sessions');
        $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp();

        return DB::table($table)
            ->where('user_id', $user->getKey())
            ->where('last_activity', '>=', $cutoff)
            ->exists();
    }
}
