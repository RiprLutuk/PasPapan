<?php

namespace Database\Seeders;

use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;

class PayrollComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = [
            // ============================================
            // ALLOWANCES (Tunjangan)
            // ============================================
            [
                'name' => 'Uang Makan',
                'type' => 'allowance',
                'calculation_type' => 'daily_presence',
                'amount' => 50000,
                'is_taxable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Uang Transport',
                'type' => 'allowance',
                'calculation_type' => 'daily_presence',
                'amount' => 25000,
                'is_taxable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Tunjangan Kesehatan',
                'type' => 'allowance',
                'calculation_type' => 'fixed',
                'amount' => 150000,
                'is_taxable' => true,
                'is_active' => true,
            ],

            // ============================================
            // DEDUCTIONS – BPJS (Porsi Karyawan / Employee Share)
            // Ref: PP No. 84/2013, Perpres No. 82/2018
            // ============================================
            [
                'name' => 'BPJS Kesehatan (1%)',
                'type' => 'deduction',
                'calculation_type' => 'percentage_basic',
                'percentage' => 1.0,    // 1% dari gaji pokok (employee share), cap Rp12jt
                'is_taxable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JHT (2%)',
                'type' => 'deduction',
                'calculation_type' => 'percentage_basic',
                'percentage' => 2.0,    // 2% dari gaji pokok (employee share)
                'is_taxable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JP (1%)',
                'type' => 'deduction',
                'calculation_type' => 'percentage_basic',
                'percentage' => 1.0,    // 1% dari gaji pokok (employee share), cap ~Rp10jt
                'is_taxable' => false,
                'is_active' => true,
            ],

            // ============================================
            // NOTE: PPh 21 tidak disimpan sebagai komponen tetap.
            // PPh 21 dihitung secara dinamis oleh TaxCalculatorService
            // menggunakan metode TER (Tarif Efektif Rata-rata)
            // sesuai PP 58/2023 dan PMK 168/2023.
            // ============================================
        ];

        foreach ($components as $comp) {
            PayrollComponent::updateOrCreate(
                ['name' => $comp['name']],
                $comp
            );
        }
    }
}
