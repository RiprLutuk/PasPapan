<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobLevelSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Job Levels
        $levels = [
            1 => 'Head',
            2 => 'Manager',
            3 => 'Senior',
            4 => 'Staff',
        ];

        foreach ($levels as $rank => $name) {
            \App\Models\JobLevel::firstOrCreate(
                ['rank' => $rank],
                ['name' => $name]
            );
        }

        // 2. Migrate Existing Job Titles (Link to Job Level)
        $jobTitles = JobTitle::all();
        foreach ($jobTitles as $title) {
            if ($title->level) {
                $jobLevel = \App\Models\JobLevel::where('rank', $title->level)->first();
                if ($jobLevel) {
                    $title->update(['job_level_id' => $jobLevel->id]);
                }
            }
        }
    }
}
