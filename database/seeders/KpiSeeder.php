<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KpiTemplate;

class KpiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kpis = [
            [
                'name' => 'Pencapaian Target / Productivity',
                'weight' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Tanggung Jawab / Responsibility',
                'weight' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Kerja Sama / Teamwork',
                'weight' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Komunikasi / Communication',
                'weight' => 15,
                'is_active' => true,
            ]
        ];

        foreach ($kpis as $kpi) {
            KpiTemplate::firstOrCreate(
                ['name' => $kpi['name']],
                $kpi
            );
        }
    }
}
