<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Holiday;
use Carbon\Carbon;

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
    protected $description = 'Fetch Indonesian National Holidays from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?? date('Y');
        $years = [$year, $year + 1]; // Fetch this year and next year by default

        foreach ($years as $y) {
            $this->info("Fetching holidays for {$y}...");
            
            $response = Http::get("https://dayoffapi.vercel.app/api?year={$y}");

            if ($response->failed()) {
                $this->error("Failed to fetch data for {$y}");
                continue;
            }

            $holidays = $response->json();

            if (!is_array($holidays)) {
                 $this->error("Invalid data format for {$y}");
                 continue;
            }

            $count = 0;
            foreach ($holidays as $h) {
                // "tanggal": "2025-01-01", "keterangan": "Tahun Baru..."
                if (isset($h['tanggal']) && isset($h['keterangan'])) {
                    
                    // Prevent duplicates
                    Holiday::updateOrCreate(
                        [
                            'date' => $h['tanggal'],
                        ],
                        [
                            'name' => $h['keterangan'],
                            'description' => $h['is_cuti'] ? 'Cuti Bersama' : 'National Holiday',
                            'is_recurring' => false, // API gives specific dates
                        ]
                    );
                    $count++;
                }
            }

            $this->info("Imported {$count} holidays for {$y}.");
        }

        $this->info("Done.");
    }
}
