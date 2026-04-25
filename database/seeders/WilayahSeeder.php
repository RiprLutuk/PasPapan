<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/wilayah.sql.gz');

        if (! File::exists($path)) {
            $this->command->error("Wilayah data file not found at {$path}");

            return;
        }

        $this->command->info('Extracting and executing wilayah.sql...');

        $sql = gzdecode(File::get($path));

        if ($sql === false) {
            $this->command->error('Failed to extract gzip file.');

            return;
        }

        DB::unprepared($sql);

        $this->command->info('Wilayah table seeded successfully!');
    }
}
