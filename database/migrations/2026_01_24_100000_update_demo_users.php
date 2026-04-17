<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete old demo user
        \Illuminate\Support\Facades\DB::table('users')->where('email', 'admin.demo@pandanteknik.com')->delete();

        // Create new Demo Admin
        \Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
            ['email' => 'admin123@paspapan.com'],
            [
                'id' => (string) str(\Illuminate\Support\Str::ulid())->lower(),
                'nip' => '0000000000000001',
                'name' => 'Demo Admin',
                'password' => Hash::make('12345678'),
                'group' => 'admin',
                'email_verified_at' => now(),
                'phone' => '081234567801',
                'gender' => 'male',
                'address' => 'Demo Address Admin',
                'city' => 'Jakarta',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create new Demo User
        \Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
            ['email' => 'user123@paspapan.com'],
            [
                'id' => (string) str(\Illuminate\Support\Str::ulid())->lower(),
                'nip' => '0000000000000002',
                'name' => 'Demo User',
                'password' => Hash::make('12345678'),
                'group' => 'user',
                'email_verified_at' => now(),
                'phone' => '081234567802',
                'gender' => 'male',
                'address' => 'Demo Address User',
                'city' => 'Jakarta',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('users')->whereIn('email', ['admin123@paspapan.com', 'user123@paspapan.com'])->delete();
    }
};
