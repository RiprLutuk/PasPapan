<?php

use App\Support\RbacRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $permission = 'admin.leave_types.manage';

    public function up(): void
    {
        $roles = DB::table('roles')
            ->whereIn('slug', ['super_admin', 'admin', 'hr'])
            ->get(['id', 'slug', 'permissions']);

        foreach ($roles as $role) {
            $permissions = $role->slug === 'super_admin'
                ? RbacRegistry::permissionKeys()
                : $this->appendPermission($role->permissions);

            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $roles = DB::table('roles')
            ->whereIn('slug', ['super_admin', 'admin', 'hr'])
            ->get(['id', 'permissions']);

        foreach ($roles as $role) {
            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'permissions' => json_encode($this->removePermission($role->permissions), JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }

    private function appendPermission(?string $encodedPermissions): array
    {
        $permissions = $this->decodePermissions($encodedPermissions);
        $permissions[] = $this->permission;

        return array_values(array_unique($permissions));
    }

    private function removePermission(?string $encodedPermissions): array
    {
        return array_values(array_filter(
            $this->decodePermissions($encodedPermissions),
            fn (string $permission) => $permission !== $this->permission,
        ));
    }

    private function decodePermissions(?string $encodedPermissions): array
    {
        if (! $encodedPermissions) {
            return [];
        }

        $permissions = json_decode($encodedPermissions, true);

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_filter(
            $permissions,
            fn ($permission) => is_string($permission) && $permission !== '',
        ));
    }
};
