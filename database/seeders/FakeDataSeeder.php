<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FakeDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $divisions = \App\Models\Division::all();
        $jobTitles = \App\Models\JobTitle::all()->keyBy('name');

        foreach ($divisions as $division) {
            $divKey = strtolower(str_replace(' ', '', $division->name));

            // Head
            User::updateOrCreate(
                ['email' => 'head.'.$divKey.'@example.com'],
                User::factory()->raw([
                    'name' => 'Head '.$division->name,
                    'division_id' => $division->id,
                    'job_title_id' => $jobTitles['Head']->id ?? null,
                    'basic_salary' => 15000000,
                    'group' => 'user',
                ])
            );

            // Manager
            User::updateOrCreate(
                ['email' => 'manager.'.$divKey.'@example.com'],
                User::factory()->raw([
                    'name' => 'Manager '.$division->name,
                    'division_id' => $division->id,
                    'job_title_id' => $jobTitles['Manager']->id ?? null,
                    'basic_salary' => 10000000,
                    'group' => 'user',
                ])
            );

            // Senior
            User::updateOrCreate(
                ['email' => 'senior.'.$divKey.'@example.com'],
                User::factory()->raw([
                    'name' => 'Senior '.$division->name,
                    'division_id' => $division->id,
                    'job_title_id' => $jobTitles['Senior']->id ?? null,
                    'basic_salary' => 7500000,
                    'group' => 'user',
                ])
            );

            // Staff
            User::updateOrCreate(
                ['email' => 'staff.'.$divKey.'@example.com'],
                User::factory()->raw([
                    'name' => 'Staff '.$division->name,
                    'division_id' => $division->id,
                    'job_title_id' => $jobTitles['Staff']->id ?? null,
                    'basic_salary' => 5000000,
                    'group' => 'user',
                ])
            );
        }

        User::factory(10)->create();
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            User::factory()->raw(['name' => 'Test User'])
        );
        $this->call([
            AttendanceSeeder::class,
            DemoAssetSeeder::class,
        ]);
    }
}
