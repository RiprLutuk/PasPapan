<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
            ['email' => 'admin.demo@pandanteknik.com'],
            [
                'id' => (string) str(\Illuminate\Support\Str::ulid())->lower(),
                'nip' => '0000000000000001',
                'name' => 'Demo Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'group' => 'admin',
                'email_verified_at' => now(),
                'phone' => '081234567890',
                'gender' => 'male',
                'address' => 'Demo Address, Jakarta',
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
        \Illuminate\Support\Facades\DB::table('users')->where('email', 'admin.demo@pandanteknik.com')->delete();
    }
};
