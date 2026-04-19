<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'raw_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('raw_password');
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: plaintext password storage must not be restored.
    }
};
