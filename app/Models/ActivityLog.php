<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'description', 'ip_address', 'count'];

    protected $hidden = ['integrity_hash'];

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $activityLog): void {
            $activityLog->count ??= 1;
            $activityLog->integrity_hash = $activityLog->makeIntegrityHash();
        });

        static::updating(function (ActivityLog $activityLog): void {
            $dirtyFields = array_keys($activityLog->getDirty());
            $protectedDirtyFields = array_diff($dirtyFields, ['count', 'updated_at', 'integrity_hash']);

            if ($protectedDirtyFields !== []) {
                throw new AuthorizationException('Activity logs are append-only and cannot be modified.');
            }

            $activityLog->integrity_hash = $activityLog->makeIntegrityHash();
        });

        static::deleting(function (): void {
            throw new AuthorizationException('Activity logs are append-only and cannot be deleted.');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record($action, $description = null)
    {
        // Open Core: Delegate to Service (Community = No-op, Enterprise = Logged)
        $service = app(\App\Contracts\AuditServiceInterface::class);

        try {
            return $service->record($action, $description);
        } catch (\Throwable $e) {
            Log::warning('ActivityLog::record failed without blocking the request.', [
                'action' => $action,
                'description' => $description,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function hasValidIntegrityHash(): bool
    {
        return is_string($this->integrity_hash)
            && hash_equals($this->integrity_hash, $this->makeIntegrityHash());
    }

    public function makeIntegrityHash(): string
    {
        return hash_hmac('sha256', $this->integrityPayload(), (string) Config::get('app.key'));
    }

    protected function integrityPayload(): string
    {
        return json_encode([
            'user_id' => $this->user_id,
            'action' => $this->action,
            'description' => $this->description,
            'ip_address' => $this->ip_address,
            'count' => (int) ($this->count ?? 1),
        ], JSON_THROW_ON_ERROR);
    }
}
