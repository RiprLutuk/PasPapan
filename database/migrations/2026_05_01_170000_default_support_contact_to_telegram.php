<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'app.support_contact')
            ->whereIn('value', ['', 'example@gmail.com', 'support@example.com'])
            ->update([
                'value' => 'https://t.me/RiprLutuk',
                'description' => 'Support Telegram',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'app.support_contact')
            ->where('value', 'https://t.me/RiprLutuk')
            ->update([
                'value' => 'example@gmail.com',
                'description' => 'Support Email/Phone',
                'updated_at' => now(),
            ]);
    }
};
