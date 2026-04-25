<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'description', 'ip_address', 'count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record($action, $description = null)
    {
        // Open Core: Delegate to Service (Community = No-op, Enterprise = Logged)
        $service = app(\App\Contracts\AuditServiceInterface::class);

        return $service->record($action, $description);
    }
}
