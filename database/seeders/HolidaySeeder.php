<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
  0 => 
  [
    'date' => '2026-01-15',
    'name' => 'Nyepi',
    'description' => '',
    'is_recurring' => 0,
  ],
  1 => 
  [
    'date' => '2026-01-01',
    'name' => 'Tahun Baru 2026 Masehi',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  2 => 
  [
    'date' => '2026-01-16',
    'name' => 'Isra Mikraj Nabi Muhammad S.A.W.',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  3 => 
  [
    'date' => '2026-02-16',
    'name' => 'Tahun Baru Imlek 2577 Kongzili',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  4 => 
  [
    'date' => '2026-02-17',
    'name' => 'Tahun Baru Imlek 2577 Kongzili',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  5 => 
  [
    'date' => '2026-03-18',
    'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948]',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  6 => 
  [
    'date' => '2026-03-19',
    'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948]',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  7 => 
  [
    'date' => '2026-03-20',
    'name' => 'Idul Fitri 1447 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  8 => 
  [
    'date' => '2026-03-21',
    'name' => 'Idul Fitri 1447 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  9 => 
  [
    'date' => '2026-03-22',
    'name' => 'Idul Fitri 1447 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  10 => 
  [
    'date' => '2026-03-23',
    'name' => 'Idul Fitri 1447 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  11 => 
  [
    'date' => '2026-03-24',
    'name' => 'Idul Fitri 1447 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  12 => 
  [
    'date' => '2026-04-03',
    'name' => 'Wafat Yesus Kristus',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  13 => 
  [
    'date' => '2026-04-05',
    'name' => 'Kebangkitan Yesus Kristus (Paskah]',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  14 => 
  [
    'date' => '2026-05-01',
    'name' => 'Hari Buruh Internasional',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  15 => 
  [
    'date' => '2026-05-14',
    'name' => 'Kenaikan Yesus Kristus',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  16 => 
  [
    'date' => '2026-05-15',
    'name' => 'Kenaikan Yesus Kristus',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  17 => 
  [
    'date' => '2026-05-27',
    'name' => 'Idul Adha 1447 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  18 => 
  [
    'date' => '2026-05-28',
    'name' => 'Idul Adha 1447 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  19 => 
  [
    'date' => '2026-05-31',
    'name' => 'Hari Raya Waisak 2570 BE',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  20 => 
  [
    'date' => '2026-06-01',
    'name' => 'Hari Lahir Pancasila',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  21 => 
  [
    'date' => '2026-06-16',
    'name' => '1 Muharam Tahun Baru Islam 1448 Hijriah',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  22 => 
  [
    'date' => '2026-08-17',
    'name' => 'Proklamasi Kemerdekaan',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  23 => 
  [
    'date' => '2026-08-25',
    'name' => 'Maulid Nabi Muhammad S.A.W.',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  24 => 
  [
    'date' => '2026-12-25',
    'name' => 'Kelahiran Yesus Kristus',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  25 => 
  [
    'date' => '2025-01-01',
    'name' => 'Tahun Baru 2025 Masehi',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  26 => 
  [
    'date' => '2025-01-27',
    'name' => 'Isra\' Mi\'raj Nabi Muhammad SAW',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  27 => 
  [
    'date' => '2025-01-28',
    'name' => 'Cuti Bersama Tahun Baru Imlek 2576 Kongzili',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  28 => 
  [
    'date' => '2025-01-29',
    'name' => 'Tahun Baru Imlek 2576 Kongzili',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  29 => 
  [
    'date' => '2025-03-28',
    'name' => 'Cuti Bersama Hari Raya Nyepi Tahun Baru Saka 1947',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  30 => 
  [
    'date' => '2025-03-29',
    'name' => 'Hari Raya Nyepi Tahun Baru Saka 1947',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  31 => 
  [
    'date' => '2025-03-31',
    'name' => 'Hari Raya Idul Fitri 1446H',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  32 => 
  [
    'date' => '2025-04-01',
    'name' => 'Hari Raya Idul Fitri 1446H',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  33 => 
  [
    'date' => '2025-04-02',
    'name' => 'Cuti Bersama Hari Raya Idul Fitri 1446H',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  34 => 
  [
    'date' => '2025-04-03',
    'name' => 'Cuti Bersama Hari Raya Idul Fitri 1446H',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  35 => 
  [
    'date' => '2025-04-04',
    'name' => 'Cuti Bersama Hari Raya Idul Fitri 1446H',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  36 => 
  [
    'date' => '2025-04-07',
    'name' => 'Cuti Bersama Hari Raya Idul Fitri 1446H',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  37 => 
  [
    'date' => '2025-04-18',
    'name' => 'Wafat Yesus Kristus',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  38 => 
  [
    'date' => '2025-04-20',
    'name' => 'Kebangkitan Yesus Kristus',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  39 => 
  [
    'date' => '2025-05-01',
    'name' => 'Hari Buruh Internasional',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  40 => 
  [
    'date' => '2025-05-12',
    'name' => 'Hari Raya Waisak 2569 BE',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  41 => 
  [
    'date' => '2025-05-13',
    'name' => 'Cuti Bersama Hari Raya Waisak 2569 BE',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  42 => 
  [
    'date' => '2025-05-29',
    'name' => 'Kenaikan Yesus Kristus',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  43 => 
  [
    'date' => '2025-05-30',
    'name' => 'Cuti Bersama Kenaikan Yesus Kristus',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  44 => 
  [
    'date' => '2025-06-01',
    'name' => 'Hari Lahir Pancasila',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  45 => 
  [
    'date' => '2025-06-06',
    'name' => 'Hari Raya Idul Adha 1446H',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  46 => 
  [
    'date' => '2025-06-09',
    'name' => 'Cuti Bersama Hari Raya Idul Adha 1446H',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
  47 => 
  [
    'date' => '2025-06-27',
    'name' => 'Tahun Baru Islam 1 Muharram 1447H',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  48 => 
  [
    'date' => '2025-08-17',
    'name' => 'Hari Kemerdekaan Republik Indonesia ke 80',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  49 => 
  [
    'date' => '2025-08-18',
    'name' => 'Libur Nasional Kemerdekaan Republik Indonesia',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  50 => 
  [
    'date' => '2025-09-05',
    'name' => 'Maulid Nabi Muhammad SAW',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  51 => 
  [
    'date' => '2025-12-25',
    'name' => 'Hari Raya Natal',
    'description' => 'National Holiday',
    'is_recurring' => 0,
  ],
  52 => 
  [
    'date' => '2025-12-26',
    'name' => 'Cuti Bersama Hari Raya Natal',
    'description' => 'Cuti Bersama',
    'is_recurring' => 0,
  ],
];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                $holiday
            );
        }
    }
}
