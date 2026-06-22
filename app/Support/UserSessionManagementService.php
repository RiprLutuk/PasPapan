<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UserSessionManagementService
{
    public function databaseSessionsAvailable(): bool
    {
        return config('session.driver') === 'database'
            && Schema::hasTable($this->table());
    }

    public function redisSessionsAvailable(): bool
    {
        return config('session.driver') === 'redis';
    }

    /**
     * @return Collection<int, User>
     */
    public function usersWithActiveSessions(User $actor, string $search = '', int $limit = 25): Collection
    {
        if (! $this->databaseSessionsAvailable() && ! $this->redisSessionsAvailable()) {
            return collect();
        }

        if ($this->redisSessionsAvailable()) {
            return $this->getRedisUsersWithActiveSessions($actor, $search, $limit);
        }

        $cutoff = $this->activeCutoff();
        $search = trim($search);

        return User::query()
            ->select('users.*')
            ->selectSub(function ($query) use ($cutoff): void {
                $query->from($this->table())
                    ->selectRaw('count(*)')
                    ->whereColumn("{$this->table()}.user_id", 'users.id')
                    ->where("{$this->table()}.last_activity", '>=', $cutoff);
            }, 'active_sessions_count')
            ->selectSub(function ($query): void {
                $query->from('personal_access_tokens')
                    ->selectRaw('count(*)')
                    ->whereColumn('personal_access_tokens.tokenable_id', 'users.id')
                    ->where('personal_access_tokens.tokenable_type', User::class)
                    ->where(function ($query): void {
                        $query->whereNull('personal_access_tokens.expires_at')
                            ->orWhere('personal_access_tokens.expires_at', '>', now());
                    });
            }, 'active_api_tokens_count')
            ->where(function (Builder $query) use ($cutoff): void {
                $query
                    ->whereExists(function ($query) use ($cutoff): void {
                        $query->from($this->table())
                            ->selectRaw('1')
                            ->whereColumn("{$this->table()}.user_id", 'users.id')
                            ->where("{$this->table()}.last_activity", '>=', $cutoff);
                    })
                    ->orWhereExists(function ($query): void {
                        $query->from('personal_access_tokens')
                            ->selectRaw('1')
                            ->whereColumn('personal_access_tokens.tokenable_id', 'users.id')
                            ->where('personal_access_tokens.tokenable_type', User::class)
                            ->where(function ($query): void {
                                $query->whereNull('personal_access_tokens.expires_at')
                                    ->orWhere('personal_access_tokens.expires_at', '>', now());
                            });
                    });
            })
            ->when(! $this->canUseGlobalScope($actor), fn (Builder $query) => $query->where('company_id', $actor->company_id))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, array{id: string, ip_address: ?string, user_agent: string, last_activity: Carbon, is_current_device: bool}>
     */
    public function activeSessionsFor(User $actor, User $target, ?string $currentSessionId = null): Collection
    {
        $this->ensureCanManageTarget($actor, $target);

        if (! $this->databaseSessionsAvailable() && ! $this->redisSessionsAvailable()) {
            return collect();
        }

        if ($this->redisSessionsAvailable()) {
            return $this->getRedisActiveSessionsFor($target, $currentSessionId);
        }

        return DB::table($this->table())
            ->where('user_id', $target->getKey())
            ->where('last_activity', '>=', $this->activeCutoff())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session): array => [
                'id' => (string) $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $this->friendlyUserAgent((string) $session->user_agent),
                'last_activity' => Carbon::createFromTimestamp((int) $session->last_activity),
                'is_current_device' => $currentSessionId !== null && hash_equals((string) $session->id, $currentSessionId),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, name: string, abilities: array<int, string>, last_used_at: ?Carbon, expires_at: ?Carbon, created_at: ?Carbon}>
     */
    public function apiTokensFor(User $actor, User $target): Collection
    {
        $this->ensureCanManageTarget($actor, $target);

        return $target->tokens()
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByRaw('last_used_at is null')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($token): array => [
                'id' => (string) $token->id,
                'name' => (string) $token->name,
                'abilities' => collect($token->abilities ?? [])->map(fn ($ability): string => (string) $ability)->values()->all(),
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ]);
    }

    public function revokeApiToken(User $actor, User $target, string $tokenId): int
    {
        $this->ensureCanManageTarget($actor, $target);

        $deleted = $target->tokens()
            ->whereKey($tokenId)
            ->delete();

        if ($deleted > 0) {
            ActivityLog::record(
                'User API Token Revoked',
                "Admin {$actor->email} revoked one API token for {$target->email}."
            );
        }

        return $deleted;
    }

    public function forgetSession(User $actor, User $target, string $sessionId, ?string $currentSessionId = null): int
    {
        $this->ensureCanManageTarget($actor, $target);

        if ($actor->is($target) && $currentSessionId !== null && hash_equals($sessionId, $currentSessionId)) {
            throw ValidationException::withMessages([
                'selectedUserId' => __('You cannot disconnect the current admin session from this page.'),
            ]);
        }

        if ($this->redisSessionsAvailable()) {
            $redis = Redis::connection(config('session.connection'));
            $deleted = $redis->hdel("user_sessions:{$target->getKey()}", $sessionId);
            // Also delete the actual session key from Laravel Cache/Redis
            Cache::store(config('session.store'))->forget($sessionId);
        } else {
            $deleted = DB::table($this->table())
                ->where('user_id', $target->getKey())
                ->where('id', $sessionId)
                ->delete();
        }

        if ($deleted > 0) {
            ActivityLog::record(
                'User Session Revoked',
                "Admin {$actor->email} revoked one active session for {$target->email}."
            );
        }

        return $deleted;
    }

    public function forgetAllSessions(User $actor, User $target, ?string $currentSessionId = null): int
    {
        $this->ensureCanManageTarget($actor, $target);

        if ($this->redisSessionsAvailable()) {
            $redis = Redis::connection(config('session.connection'));
            $key = "user_sessions:{$target->getKey()}";
            $sessions = $redis->hgetall($key);
            $deleted = 0;
            foreach ($sessions as $sId => $payload) {
                if ($actor->is($target) && $currentSessionId !== null && hash_equals($sId, $currentSessionId)) {
                    continue;
                }
                $redis->hdel($key, $sId);
                Cache::store(config('session.store'))->forget($sId);
                $deleted++;
            }
        } else {
            $query = DB::table($this->table())->where('user_id', $target->getKey());

            if ($actor->is($target) && $currentSessionId !== null) {
                $query->where('id', '!=', $currentSessionId);
            }

            $deleted = $query->delete();
        }

        if ($deleted > 0) {
            ActivityLog::record(
                'User Sessions Revoked',
                "Admin {$actor->email} revoked {$deleted} session(s) for {$target->email}."
            );
        }

        return $deleted;
    }

    public function ensureCanManageTarget(User $actor, User $target): void
    {
        if (! $actor->can('manageUserSessions')) {
            abort(403);
        }

        if ($target->isSuperadmin && ! $actor->isSuperadmin) {
            abort(403);
        }

        if (! $this->canUseGlobalScope($actor) && $actor->company_id !== $target->company_id) {
            abort(403);
        }
    }

    private function activeCutoff(): int
    {
        return now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp();
    }

    private function table(): string
    {
        return config('session.table', 'sessions');
    }

    private function canUseGlobalScope(User $actor): bool
    {
        return $actor->isSuperadmin || $actor->hasGlobalAdminScope() || blank($actor->company_id);
    }

    private function friendlyUserAgent(string $userAgent): string
    {
        $userAgent = trim($userAgent);

        return $userAgent !== '' ? mb_strimwidth($userAgent, 0, 110, '...') : __('Unknown device');
    }

    private function getRedisActiveSessionsFor(User $target, ?string $currentSessionId = null): Collection
    {
        $redis = Redis::connection(config('session.connection'));
        $sessions = $redis->hgetall("user_sessions:{$target->getKey()}");
        $cutoff = $this->activeCutoff();

        $active = collect();
        foreach ($sessions as $sId => $payloadStr) {
            $payload = json_decode($payloadStr, true);
            if (isset($payload['last_activity']) && $payload['last_activity'] >= $cutoff) {
                $active->push([
                    'id' => $sId,
                    'ip_address' => $payload['ip_address'] ?? null,
                    'user_agent' => $this->friendlyUserAgent((string) ($payload['user_agent'] ?? '')),
                    'last_activity' => Carbon::createFromTimestamp((int) $payload['last_activity']),
                    'is_current_device' => $currentSessionId !== null && hash_equals($sId, $currentSessionId),
                ]);
            } else {
                $redis->hdel("user_sessions:{$target->getKey()}", $sId);
            }
        }

        return $active->sortByDesc('last_activity')->values();
    }

    private function getRedisUsersWithActiveSessions(User $actor, string $search = '', int $limit = 25): Collection
    {
        $redis = Redis::connection(config('session.connection'));
        $keys = $redis->keys('user_sessions:*');
        $userIds = [];
        $activeSessionsCount = [];
        $cutoff = $this->activeCutoff();

        $prefix = config('database.redis.options.prefix') ?? '';
        foreach ($keys as $fullKey) {
            $key = str_replace($prefix, '', $fullKey);
            if (preg_match('/user_sessions:([a-zA-Z0-9_-]+)$/', $key, $matches)) {
                $uId = $matches[1];
                $cleanKey = "user_sessions:{$uId}";
                $sessions = $redis->hgetall($cleanKey);
                $count = 0;
                foreach ($sessions as $sId => $payloadStr) {
                    $payload = json_decode($payloadStr, true);
                    if (isset($payload['last_activity']) && $payload['last_activity'] >= $cutoff) {
                        $count++;
                    } else {
                        $redis->hdel($cleanKey, $sId);
                    }
                }
                if ($count > 0) {
                    $userIds[] = $uId;
                    $activeSessionsCount[$uId] = $count;
                }
            }
        }

        $query = User::query()
            ->whereIn('id', $userIds)
            ->when(! $this->canUseGlobalScope($actor), fn (Builder $query) => $query->where('company_id', $actor->company_id))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($limit);

        $users = $query->get();
        $users->each(function ($user) use ($activeSessionsCount) {
            $user->active_sessions_count = $activeSessionsCount[$user->id] ?? 0;
            $user->active_api_tokens_count = 0; // Simplified for redis tracker
        });

        return $users;
    }
}
