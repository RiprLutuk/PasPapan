<?php

namespace App\Services\Payroll;

class TaxCalculatorService
{
    /**
     * Calculate BPJS Kesehatan Deduction (Employee Share = 1%, Employer Share = 4%)
     * Max cap is usually 12,000,000 for calculation.
     */
    public static function calculateBPJSKesehatan($grossSalary, $isEmployeeShare = true)
    {
        $cap = 12000000;
        $base = min($grossSalary, $cap);
        $rate = $isEmployeeShare ? 0.01 : 0.04;

        return round($base * $rate, 2);
    }

    /**
     * Calculate BPJS Ketenagakerjaan Jaminan Hari Tua (JHT)
     * Employee Share = 2%, Employer Share = 3.7%
     */
    public static function calculateBPJSKetenagakerjaanJHT($grossSalary, $isEmployeeShare = true)
    {
        $rate = $isEmployeeShare ? 0.02 : 0.037;

        return round($grossSalary * $rate, 2);
    }

    /**
     * Calculate BPJS Ketenagakerjaan Jaminan Pensiun (JP)
     * Employee Share = 1%, Employer Share = 2%
     * Max cap is updated yearly (assume 10,042,300 for 2024/2026)
     */
    public static function calculateBPJSKetenagakerjaanJP($grossSalary, $isEmployeeShare = true)
    {
        $cap = 10042300;
        $base = min($grossSalary, $cap);
        $rate = $isEmployeeShare ? 0.01 : 0.02;

        return round($base * $rate, 2);
    }

    /**
     * Calculate PPh 21 using Tarif Efektif Rata-rata (TER)
     * Requires: Gross Income for the month, and PTKP Status (e.g., TK/0, K/1, etc.)
     * This is a simplified TER engine table setup.
     */
    public static function calculatePPh21TER($grossSalary, $ptkpStatus = 'TK/0')
    {
        // Define Categories based on PTKP Status
        $categoryA = ['TK/0', 'TK/1', 'K/0'];
        $categoryB = ['TK/2', 'TK/3', 'K/1', 'K/2'];
        $categoryC = ['K/3'];

        $category = 'A';
        if (in_array(strtoupper($ptkpStatus), $categoryB)) {
            $category = 'B';
        }
        if (in_array(strtoupper($ptkpStatus), $categoryC)) {
            $category = 'C';
        }

        $rate = self::getTERRate($grossSalary, $category);

        return round($grossSalary * $rate, 2);
    }

    /**
     * Basic mock of the Indonesian TER Table representation for PPh21 (Effective Rates)
     * Returns a decimal rate (e.g., 0.05 for 5%)
     */
    private static function getTERRate($gross, $category)
    {
        // Simplified matrix for demonstration
        if ($category === 'A') {
            if ($gross <= 5400000) {
                return 0;
            }
            if ($gross <= 5650000) {
                return 0.0025;
            }
            if ($gross <= 5950000) {
                return 0.005;
            }
            if ($gross <= 6300000) {
                return 0.0075;
            }
            if ($gross <= 6750000) {
                return 0.01;
            }
            if ($gross <= 7500000) {
                return 0.0125;
            }
            if ($gross <= 8550000) {
                return 0.015;
            }
            if ($gross <= 9650000) {
                return 0.0175;
            }

            return 0.02; // Cap flat for mock
        } elseif ($category === 'B') {
            if ($gross <= 6200000) {
                return 0;
            }
            if ($gross <= 6500000) {
                return 0.0025;
            }
            if ($gross <= 6850000) {
                return 0.005;
            }
            if ($gross <= 7300000) {
                return 0.0075;
            }
            if ($gross <= 8100000) {
                return 0.01;
            }

            return 0.015;
        } else {
            if ($gross <= 6600000) {
                return 0;
            }
            if ($gross <= 6950000) {
                return 0.0025;
            }
            if ($gross <= 7350000) {
                return 0.005;
            }
            if ($gross <= 7800000) {
                return 0.0075;
            }
            if ($gross <= 8850000) {
                return 0.01;
            }

            return 0.0125;
        }
    }
}
