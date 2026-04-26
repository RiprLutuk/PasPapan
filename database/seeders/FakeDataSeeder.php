<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Education;
use App\Models\JobTitle;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    private ?array $cachedLocationFields = null;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $divisions = Division::query()->orderBy('name')->get();
        $jobTitles = JobTitle::query()->with('jobLevel')->get()->keyBy('name');
        $staffTitle = $jobTitles->get('Staff');
        $seniorTitle = $jobTitles->get('Senior');
        $headTitle = $jobTitles->get('Head');
        $managerTitle = $jobTitles->get('Manager');

        foreach ($divisions as $division) {
            $divKey = Str::slug($division->name, '');

            $head = $this->upsertEmployee(
                email: 'head.'.$divKey.'@example.com',
                name: 'Head '.$division->name,
                division: $division,
                jobTitle: $headTitle,
                basicSalary: 15000000,
            );

            $manager = $this->upsertEmployee(
                email: 'manager.'.$divKey.'@example.com',
                name: 'Manager '.$division->name,
                division: $division,
                jobTitle: $managerTitle,
                basicSalary: 10000000,
                manager: $head,
            );

            $senior = $this->upsertEmployee(
                email: 'senior.'.$divKey.'@example.com',
                name: 'Senior '.$division->name,
                division: $division,
                jobTitle: $seniorTitle,
                basicSalary: 7500000,
                manager: $manager,
            );

            $this->upsertEmployee(
                email: 'staff.'.$divKey.'@example.com',
                name: 'Staff '.$division->name,
                division: $division,
                jobTitle: $staffTitle,
                basicSalary: 5000000,
                manager: $senior,
            );

            foreach (range(2, 3) as $index) {
                $this->upsertEmployee(
                    email: 'staff'.$index.'.'.$divKey.'@example.com',
                    name: 'Staff '.$index.' '.$division->name,
                    division: $division,
                    jobTitle: $index % 2 === 0 ? $staffTitle : $seniorTitle,
                    basicSalary: $index % 2 === 0 ? 5200000 : 7800000,
                    manager: $index % 2 === 0 ? $senior : $manager,
                    seedOffset: $index,
                );
            }
        }

        $this->normalizeUniqueLeadership($divisions, $headTitle, $managerTitle, $seniorTitle, $staffTitle);
        $this->assignDirectManagers($divisions, $headTitle, $managerTitle, $seniorTitle, $staffTitle);

        $defaultDivision = $divisions->first();
        $defaultManager = $defaultDivision
            ? User::query()
                ->where('division_id', $defaultDivision->id)
                ->where('job_title_id', $managerTitle?->id)
                ->first()
            : null;

        $this->upsertEmployee(
            email: 'user@example.com',
            name: 'Test User',
            division: $defaultDivision,
            jobTitle: $staffTitle,
            basicSalary: 5000000,
            manager: $defaultManager,
        );

        $this->upsertEmployee(
            email: 'user123@paspapan.com',
            name: 'Demo User',
            division: $defaultDivision,
            jobTitle: $staffTitle,
            basicSalary: 5000000,
            manager: $defaultManager,
            password: '12345678',
            seedOffset: 4,
        );

        $this->call([
            AttendanceSeeder::class,
            DemoAssetSeeder::class,
        ]);
    }

    private function upsertEmployee(
        string $email,
        string $name,
        ?Division $division,
        ?JobTitle $jobTitle,
        int $basicSalary,
        ?User $manager = null,
        string $password = 'password',
        int $seedOffset = 0,
    ): User {
        $payload = User::factory()->raw([
            'email' => $email,
            'name' => $name,
            'group' => 'user',
            'password' => Hash::make($password),
            'division_id' => $division?->id,
            'job_title_id' => $jobTitle?->id,
            'education_id' => $this->educationId(),
            'manager_id' => $manager?->id,
            'language' => 'id',
            'basic_salary' => $basicSalary,
            'hourly_rate' => round($basicSalary / 173),
            'payslip_password' => Hash::make('password'),
            'payslip_password_set_at' => now(),
            'employment_status' => User::EMPLOYMENT_STATUS_ACTIVE,
            ...$this->profileFields($division, $seedOffset),
            ...$this->locationFields(),
        ]);

        if (User::query()->where('email', $email)->exists()) {
            unset($payload['password']);
        }

        return User::updateOrCreate(['email' => $email], $payload);
    }

    private function profileFields(?Division $division, int $seedOffset = 0): array
    {
        $gender = fake()->randomElement(['male', 'female']);
        $divisionName = $division?->name ?: 'General';

        return [
            'nip' => fake()->unique()->numerify('#################'),
            'phone' => fake()->unique()->numerify('08##########'),
            'gender' => $gender,
            'birth_date' => now()->subYears(25 + ($seedOffset % 18))->subDays(fake()->numberBetween(0, 300))->toDateString(),
            'birth_place' => fake()->city(),
            'address' => fake()->streetAddress().', Area '.$divisionName,
            'email_verified_at' => now(),
        ];
    }

    private function educationId(): ?int
    {
        return Education::query()
            ->whereIn('name', ['S1', 'D4', 'D3', 'S2'])
            ->orderByRaw("CASE name WHEN 'S1' THEN 0 WHEN 'D4' THEN 1 WHEN 'D3' THEN 2 WHEN 'S2' THEN 3 ELSE 4 END")
            ->value('id')
            ?? Education::query()->orderBy('id')->value('id');
    }

    /**
     * @return array{provinsi_kode:?string,kabupaten_kode:?string,kecamatan_kode:?string,kelurahan_kode:?string}
     */
    private function locationFields(): array
    {
        if ($this->cachedLocationFields !== null) {
            return $this->cachedLocationFields;
        }

        if (! Schema::hasTable('wilayah')) {
            return [
                'provinsi_kode' => null,
                'kabupaten_kode' => null,
                'kecamatan_kode' => null,
                'kelurahan_kode' => null,
            ];
        }

        $this->ensureDefaultWilayahPath();

        $province = Wilayah::query()->whereRaw('LENGTH(kode) = 2')->orderBy('kode')->first();
        $regency = $province
            ? Wilayah::query()->where('kode', 'like', $province->kode.'.%')->whereRaw('LENGTH(kode) = 5')->orderBy('kode')->first()
            : null;
        $district = $regency
            ? Wilayah::query()->where('kode', 'like', $regency->kode.'.%')->whereRaw('LENGTH(kode) = 8')->orderBy('kode')->first()
            : null;
        $village = $district
            ? Wilayah::query()->where('kode', 'like', $district->kode.'.%')->whereRaw('LENGTH(kode) = 13')->orderBy('kode')->first()
            : null;

        return $this->cachedLocationFields = [
            'provinsi_kode' => $province?->kode,
            'kabupaten_kode' => $regency?->kode,
            'kecamatan_kode' => $district?->kode,
            'kelurahan_kode' => $village?->kode,
        ];
    }

    private function ensureDefaultWilayahPath(): void
    {
        if (Wilayah::query()->exists()) {
            return;
        }

        foreach ([
            '31' => 'DKI Jakarta',
            '31.71' => 'Kota Jakarta Pusat',
            '31.71.01' => 'Gambir',
            '31.71.01.1001' => 'Gambir',
        ] as $kode => $nama) {
            Wilayah::query()->firstOrCreate(['kode' => $kode], ['nama' => $nama]);
        }
    }

    private function normalizeUniqueLeadership(
        iterable $divisions,
        ?JobTitle $headTitle,
        ?JobTitle $managerTitle,
        ?JobTitle $seniorTitle,
        ?JobTitle $staffTitle,
    ): void {
        foreach ($divisions as $division) {
            $head = $this->keepOneTitlePerDivision($division, $headTitle, $seniorTitle);
            $manager = $this->keepOneTitlePerDivision($division, $managerTitle, $staffTitle);

            if ($head && $manager && $manager->manager_id !== $head->id) {
                $manager->forceFill(['manager_id' => $head->id])->save();
            }
        }
    }

    private function keepOneTitlePerDivision(Division $division, ?JobTitle $title, ?JobTitle $fallbackTitle): ?User
    {
        if (! $title) {
            return null;
        }

        $users = User::query()
            ->where('group', 'user')
            ->where('division_id', $division->id)
            ->where('job_title_id', $title->id)
            ->orderByRaw('CASE WHEN email = ? THEN 0 ELSE 1 END', [strtolower($title->name).'.'.Str::slug($division->name, '').'@example.com'])
            ->orderBy('created_at')
            ->get();

        $kept = $users->first();

        $users->skip(1)->each(function (User $user) use ($fallbackTitle): void {
            $user->forceFill([
                'job_title_id' => $fallbackTitle?->id,
            ])->save();
        });

        return $kept;
    }

    private function assignDirectManagers(
        iterable $divisions,
        ?JobTitle $headTitle,
        ?JobTitle $managerTitle,
        ?JobTitle $seniorTitle,
        ?JobTitle $staffTitle,
    ): void {
        foreach ($divisions as $division) {
            $head = $headTitle
                ? User::query()->where('division_id', $division->id)->where('job_title_id', $headTitle->id)->first()
                : null;
            $manager = $managerTitle
                ? User::query()->where('division_id', $division->id)->where('job_title_id', $managerTitle->id)->first()
                : null;
            $senior = $seniorTitle
                ? User::query()->where('division_id', $division->id)->where('job_title_id', $seniorTitle->id)->first()
                : null;

            if ($manager && $head) {
                $manager->forceFill(['manager_id' => $head->id])->save();
            }

            if ($senior && $manager) {
                $senior->forceFill(['manager_id' => $manager->id])->save();
            }

            if ($staffTitle && ($senior || $manager)) {
                User::query()
                    ->where('division_id', $division->id)
                    ->where('job_title_id', $staffTitle->id)
                    ->update(['manager_id' => ($senior ?? $manager)->id]);
            }
        }
    }
}
