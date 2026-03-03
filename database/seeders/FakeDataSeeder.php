<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            User::factory()->create([
                'name' => 'Head ' . $division->name,
                'email' => 'head.' . $divKey . '@example.com',
                'division_id' => $division->id,
                'job_title_id' => $jobTitles['Head']->id ?? null,
                'group' => 'user',
            ]);

            // Manager
            User::factory()->create([
                'name' => 'Manager ' . $division->name,
                'email' => 'manager.' . $divKey . '@example.com',
                'division_id' => $division->id,
                'job_title_id' => $jobTitles['Manager']->id ?? null,
                'group' => 'user',
            ]);

            // Senior
            User::factory()->create([
                'name' => 'Senior ' . $division->name,
                'email' => 'senior.' . $divKey . '@example.com',
                'division_id' => $division->id,
                'job_title_id' => $jobTitles['Senior']->id ?? null,
                'group' => 'user',
            ]);

            // Staff
            User::factory()->create([
                'name' => 'Staff ' . $division->name,
                'email' => 'staff.' . $divKey . '@example.com',
                'division_id' => $division->id,
                'job_title_id' => $jobTitles['Staff']->id ?? null,
                'group' => 'user',
            ]);
        }

        User::factory(10)->create();
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);
        (new AttendanceSeeder)->run();
    }
}
