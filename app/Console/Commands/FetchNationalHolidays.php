<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class FetchNationalHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:fetch {--year= : The year to fetch holidays for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Indonesian national holidays and collective leave data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $yearOption = $this->option('year');
        $currentYear = (int) date('Y');
        $years = $yearOption !== null
            ? [(int) $yearOption]
            : [$currentYear, $currentYear + 1];

        foreach ($years as $y) {
            $this->info("Fetching holidays for {$y}...");

            $holidays = $this->fetchHolidaysForYear($y);

            if ($holidays === []) {
                $this->warn("No holiday data available for {$y}.");
                continue;
            }

            $count = 0;
            foreach ($holidays as $h) {
                if (! isset($h['date'], $h['name'])) {
                    continue;
                }

                Holiday::updateOrCreate(
                    [
                        'date' => $h['date'],
                    ],
                    [
                        'name' => $h['name'],
                        'description' => $h['description'],
                        'is_recurring' => false,
                    ]
                );

                $count++;
            }

            $this->info("Imported {$count} holidays for {$y}.");
        }

        $this->info("Done.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{date: string, name: string, description: string}>
     */
    protected function fetchHolidaysForYear(int $year): array
    {
        foreach (config('holidays.sources', []) as $source) {
            try {
                $response = Http::acceptJson()
                    ->timeout((int) config('holidays.timeout', 15))
                    ->get($source, ['year' => $year]);

                if ($response->failed()) {
                    $this->warn("Holiday API failed for {$year}: {$source}");
                    continue;
                }

                $holidays = $this->normalizeHolidayPayload($response->json());

                if ($holidays !== []) {
                    return $holidays;
                }

                $this->warn("Holiday API returned an unsupported payload for {$year}: {$source}");
            } catch (ConnectionException $e) {
                $this->warn("Holiday API connection failed for {$year}: {$source}");
            }
        }

        $fallback = $this->fallbackHolidaysForYear($year);

        if ($fallback !== []) {
            $this->warn("Using local fallback holiday data for {$year}.");
        }

        return $fallback;
    }

    /**
     * @param  mixed  $payload
     * @return array<int, array{date: string, name: string, description: string}>
     */
    protected function normalizeHolidayPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $rows = isset($payload['data']) && is_array($payload['data'])
            ? $payload['data']
            : $payload;

        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $date = $row['date'] ?? $row['tanggal'] ?? null;
            $name = $row['name'] ?? $row['description'] ?? $row['keterangan'] ?? null;

            if (! is_string($date) || ! is_string($name) || trim($date) === '' || trim($name) === '') {
                continue;
            }

            $isCollectiveLeave = isset($row['is_cuti'])
                ? (bool) $row['is_cuti']
                : str_contains(mb_strtolower($name), 'cuti bersama');

            $normalized[] = [
                'date' => $date,
                'name' => $name,
                'description' => $isCollectiveLeave ? 'Cuti Bersama' : 'National Holiday',
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{date: string, name: string, description: string}>
     */
    protected function fallbackHolidaysForYear(int $year): array
    {
        $fallbackPath = database_path('data/indonesian_holidays.php');

        if (! is_file($fallbackPath)) {
            return [];
        }

        $holidaysByYear = require $fallbackPath;

        if (! is_array($holidaysByYear)) {
            return [];
        }

        $holidays = $holidaysByYear[$year] ?? [];

        return is_array($holidays) ? $holidays : [];
    }
}
