<?php

use App\Support\RbacRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignUlid('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'user_id']);
        });

        $now = now();
        $rolesBySlug = [];

        foreach (RbacRegistry::presets() as $preset) {
            $roleId = (string) Str::ulid();
            $rolesBySlug[$preset['slug']] = $roleId;

            DB::table('roles')->insert([
                'id' => $roleId,
                'name' => $preset['name'],
                'slug' => $preset['slug'],
                'description' => $preset['description'],
                'permissions' => json_encode($preset['permissions'], JSON_THROW_ON_ERROR),
                'is_system' => $preset['is_system'],
                'is_super_admin' => $preset['is_super_admin'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (isset($rolesBySlug['super_admin'])) {
            $superadmins = DB::table('users')->where('group', 'superadmin')->pluck('id');

            foreach ($superadmins as $userId) {
                DB::table('role_user')->updateOrInsert(
                    ['role_id' => $rolesBySlug['super_admin'], 'user_id' => $userId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }

        if (isset($rolesBySlug['admin'])) {
            $admins = DB::table('users')->where('group', 'admin')->pluck('id');

            foreach ($admins as $userId) {
                DB::table('role_user')->updateOrInsert(
                    ['role_id' => $rolesBySlug['admin'], 'user_id' => $userId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
