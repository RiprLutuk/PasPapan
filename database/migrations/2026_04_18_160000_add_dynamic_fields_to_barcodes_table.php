<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->string('secret_key', 64)->nullable()->after('value');
            $table->boolean('dynamic_enabled')->default(false)->after('radius');
            $table->unsignedSmallInteger('dynamic_ttl_seconds')->default(60)->after('dynamic_enabled');
        });

        DB::table('barcodes')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function ($barcode) {
                DB::table('barcodes')
                    ->where('id', $barcode->id)
                    ->update(['secret_key' => Str::random(64)]);
            });
    }

    public function down(): void
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropColumn(['secret_key', 'dynamic_enabled', 'dynamic_ttl_seconds']);
        });
    }
};
