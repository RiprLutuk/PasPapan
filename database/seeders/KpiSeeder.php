<?php

namespace Database\Seeders;

use App\Models\KpiGroup;
use App\Models\KpiTemplate;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    public function run(): void
    {
        $group = KpiGroup::firstOrCreate([
            'name' => 'Indikator Kinerja Utama',
        ], [
            'weight' => 50,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Pencapaian Target / Productivity',
        ], [
            'indicator_description' => '',
            'weight' => 40,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Tanggung Jawab / Responsibility',
        ], [
            'indicator_description' => '',
            'weight' => 30,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Kerja Sama / Teamwork',
        ], [
            'indicator_description' => '',
            'weight' => 15,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Komunikasi / Communication',
        ], [
            'indicator_description' => '',
            'weight' => 15,
            'is_active' => true,
        ]);

        $group = KpiGroup::firstOrCreate([
            'name' => 'NIlai Utama Kepegawaian',
        ], [
            'weight' => 50,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Kemampuan Komunikasi',
        ], [
            'indicator_description' => '- Menyampaikan pesan			
- Menerima pesan',
            'weight' => 25,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Orientasi Hasil',
        ], [
            'indicator_description' => '- Memiliki keinginan untuk mencapai target			
- Bertanggung jawab untuk mencapai hasil			',
            'weight' => 25,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Pengembangan diri',
        ], [
            'indicator_description' => '- Kesadaran dan motivasi			
- Mengembangkan diri			',
            'weight' => 25,
            'is_active' => true,
        ]);

        KpiTemplate::firstOrCreate([
            'kpi_group_id' => $group->id,
            'name' => 'Kerjasama Tim',
        ], [
            'indicator_description' => '- Mendorong terciptanya kerja sama			
- Sinergi Tim			',
            'weight' => 25,
            'is_active' => true,
        ]);

    }
}
