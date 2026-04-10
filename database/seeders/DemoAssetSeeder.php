<?php

namespace Database\Seeders;

use App\Models\CompanyAsset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the demo user
        $demoUser = User::where('email', 'user123@paspapan.com')->first();

        if (!$demoUser) {
            $this->command->warn('Demo user (user123@paspapan.com) not found. Skipping DemoAssetSeeder.');
            return;
        }

        $now = Carbon::now();

        $assets = [
            [
                'name' => 'MacBook Pro 14" M2',
                'serial_number' => 'C02XV1P8M2L',
                'type' => 'electronics',
                'user_id' => $demoUser->id,
                'date_assigned' => $now->copy()->subMonths(6)->format('Y-m-d'),
                'return_date' => null,
                'status' => 'assigned',
                'notes' => 'RAM 16GB, Storage 512GB SSD',
            ],
            [
                'name' => 'iPhone 13 Pro',
                'serial_number' => 'FFJ5QX8Z0X',
                'type' => 'electronics',
                'user_id' => $demoUser->id,
                'date_assigned' => $now->copy()->subMonths(3)->format('Y-m-d'),
                'return_date' => null,
                'status' => 'assigned',
                'notes' => '128GB, Graphite - For testing purposes',
            ],
            [
                'name' => 'Honda Vario 160 (Operational)',
                'serial_number' => 'B 3144 XYZ',
                'type' => 'vehicle',
                'user_id' => $demoUser->id,
                'date_assigned' => $now->copy()->subMonth()->format('Y-m-d'),
                'return_date' => $now->copy()->addMonths(5)->format('Y-m-d'),
                'status' => 'assigned',
                'notes' => 'Operational vehicle for client visits',
            ],
        ];

        foreach ($assets as $asset) {
            CompanyAsset::updateOrCreate(
                ['serial_number' => $asset['serial_number']],
                $asset
            );
        }

        $this->command->info('Assets seeded for Demo User!');
    }
}
