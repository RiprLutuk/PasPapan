<?php

namespace Database\Seeders;

use App\Models\CompanyAsset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the demo user
        $demoUser = User::where('email', 'user123@paspapan.com')->first();

        if (! $demoUser) {
            $this->command->warn('Demo user (user123@paspapan.com) not found. Skipping DemoAssetSeeder.');

            return;
        }

        $now = Carbon::now();

        $assets = [
            [
                'name' => 'MacBook Pro 14" M2',
                'serial_number' => 'C02XV1P8M2L',
                'type' => 'electronics',
                'purchase_date' => $now->copy()->subMonths(6)->format('Y-m-d'),
                'purchase_cost' => 32000000,
                'expiration_date' => $now->copy()->addMonths(6)->format('Y-m-d'), // Valid warranty
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
                'purchase_date' => $now->copy()->subMonths(14)->format('Y-m-d'),
                'purchase_cost' => 15000000,
                'expiration_date' => $now->copy()->subMonths(2)->format('Y-m-d'), // Expired warranty
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
                'purchase_date' => $now->copy()->subYears(2)->format('Y-m-d'),
                'purchase_cost' => 28000000,
                'expiration_date' => $now->copy()->addDays(15)->format('Y-m-d'), // Expiring soon STNK
                'user_id' => $demoUser->id,
                'date_assigned' => $now->copy()->subMonth()->format('Y-m-d'),
                'return_date' => $now->copy()->addMonths(5)->format('Y-m-d'),
                'status' => 'assigned',
                'notes' => 'Operational vehicle for client visits',
            ],
        ];

        foreach ($assets as $assetData) {
            $asset = CompanyAsset::updateOrCreate(
                ['serial_number' => $assetData['serial_number']],
                $assetData
            );

            // Seed a history event if not exists
            \App\Models\CompanyAssetHistory::firstOrCreate(
                [
                    'company_asset_id' => $asset->id,
                    'action' => 'created',
                ],
                [
                    'user_id' => null,
                    'notes' => 'Asset registered via Auto-Seeder',
                    'date' => $assetData['purchase_date'],
                ]
            );

            \App\Models\CompanyAssetHistory::firstOrCreate(
                [
                    'company_asset_id' => $asset->id,
                    'action' => 'assigned',
                    'user_id' => $asset->user_id,
                ],
                [
                    'notes' => 'Assigned via Auto-Seeder',
                    'date' => $assetData['date_assigned'],
                ]
            );
        }

        $this->command->info('Assets seeded for Demo User!');
    }
}
