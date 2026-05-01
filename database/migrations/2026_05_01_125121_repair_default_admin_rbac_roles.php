<?php

use App\Support\RbacRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_user') || ! Schema::hasTable('users')) {
            return;
        }

        $now = now();
        $roleIds = [];

        foreach (RbacRegistry::presets() as $preset) {
            $role = DB::table('roles')->where('slug', $preset['slug'])->first();
            $permissions = $preset['is_super_admin']
                ? RbacRegistry::permissionKeys()
                : $this->mergePermissions($role?->permissions, $preset['permissions']);

            if ($role === null) {
                $roleId = (string) Str::ulid();
                $roleIds[$preset['slug']] = $roleId;

                DB::table('roles')->insert([
                    'id' => $roleId,
                    'name' => $preset['name'],
                    'slug' => $preset['slug'],
                    'description' => $preset['description'],
                    'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                    'is_system' => $preset['is_system'],
                    'is_super_admin' => $preset['is_super_admin'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $roleIds[$preset['slug']] = $role->id;

            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'name' => $preset['name'],
                    'description' => $preset['description'],
                    'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                    'is_system' => $preset['is_system'],
                    'is_super_admin' => $preset['is_super_admin'],
                    'updated_at' => $now,
                ]);
        }

        if (isset($roleIds['super_admin'])) {
            DB::table('users')
                ->where('group', 'superadmin')
                ->orderBy('id')
                ->pluck('id')
                ->each(fn (string $userId) => DB::table('role_user')->updateOrInsert(
                    ['role_id' => $roleIds['super_admin'], 'user_id' => $userId],
                    ['created_at' => $now, 'updated_at' => $now],
                ));
        }

        if (isset($roleIds['admin'])) {
            DB::table('users')
                ->where('group', 'admin')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('role_user')
                        ->whereColumn('role_user.user_id', 'users.id');
                })
                ->orderBy('id')
                ->pluck('id')
                ->each(fn (string $userId) => DB::table('role_user')->updateOrInsert(
                    ['role_id' => $roleIds['admin'], 'user_id' => $userId],
                    ['created_at' => $now, 'updated_at' => $now],
                ));
        }
    }

    public function down(): void
    {
        // Repair migration: keep restored roles and assignments in place.
    }

    private function mergePermissions(?string $encodedPermissions, array $presetPermissions): array
    {
        $existingPermissions = [];

        if ($encodedPermissions !== null && $encodedPermissions !== '') {
            $decoded = json_decode($encodedPermissions, true);
            $existingPermissions = is_array($decoded) ? $decoded : [];
        }

        return array_values(array_unique(array_filter(
            [...$existingPermissions, ...$presetPermissions],
            fn ($permission) => is_string($permission) && $permission !== '',
        )));
    }
};
