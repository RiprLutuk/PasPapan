<?php

namespace App\Support;

class MoneyInput
{
    /**
     * Normalize an x-mask decimal string ("1.000,50") into a plain decimal ("1000.50").
     * Returns "0" for empty input.
     */
    public static function normalizeDecimal(?string $value): string
    {
        if ($value === '' || $value === null) {
            return '0';
        }

        return str_replace(',', '.', str_replace('.', '', $value));
    }

    /**
     * Strip all non-digits from a masked amount ("Rp 1.000" -> "1000").
     * Returns null for empty / zero-digit input.
     */
    public static function digitsOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }
}
