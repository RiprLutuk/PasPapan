<?php

namespace App\Imports;

use App\Models\Division;
use App\Models\Education;
use App\Models\ImportExportRun;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class UsersImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $processedRows = 0;

    public int $successfulRows = 0;

    public int $skippedRows = 0;

    /** @var array<int, array<string, mixed>> */
    public array $importErrors = [];

    /** @var array<string, int|null> */
    private array $referenceCache = [];

    private ?bool $hasCityColumn = null;

    public function __construct(
        public bool $save = true,
        private readonly ?int $progressRunId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row)
    {
        $this->processedRows++;
        $now = now();
        $existingUser = $this->resolveExistingUser($row);

        if (! $this->rowIsUniqueForUser($row, $existingUser)) {
            $this->skippedRows++;
            $this->syncProgress(force: true);

            return null;
        }

        $divisionId = $this->resolveReferenceId(Division::class, $row['division'] ?? null);
        $jobTitleId = $this->resolveReferenceId(JobTitle::class, $row['job_title'] ?? null);
        $educationId = $this->resolveReferenceId(Education::class, $row['education'] ?? null);
        $managerId = $this->resolveManagerId($row);
        $password = trim((string) ($row['password'] ?? ''));

        $attributes = [
            'id' => $existingUser?->id ?? ($row['id'] ?? null),
            'nip' => (string) $row['nip'],
            'name' => $row['name'],
            'email' => $row['email'],
            'group' => $row['group'] ?? 'user',
            'phone' => (string) $row['phone'],
            'gender' => $this->normalizeGender($row['gender']),
            'basic_salary' => $row['basic_salary'] ?? 0,
            'hourly_rate' => $row['hourly_rate'] ?? 0,
            'birth_date' => $row['birth_date'] ?? null,
            'birth_place' => $row['birth_place'] ?? null,
            'address' => $row['address'],
            'education_id' => $educationId,
            'division_id' => $divisionId,
            'job_title_id' => $jobTitleId,
            'manager_id' => $managerId,
            'language' => $row['language'] ?? $existingUser?->language ?? 'id',
            'employment_status' => $row['employment_status'] ?? $existingUser?->employment_status ?? User::EMPLOYMENT_STATUS_ACTIVE,
            'email_verified_at' => $row['email_verified_at'] ?? $existingUser?->email_verified_at ?? $now,
            'created_at' => $existingUser?->created_at ?? ($row['created_at'] ?? $now),
            'updated_at' => $now,
        ];

        if ($password !== '') {
            $attributes['password'] = Hash::make($password);
        } elseif (! $existingUser) {
            $attributes['password'] = Hash::make('password');
        }

        if ($this->hasUserCityColumn()) {
            $attributes['city'] = $row['city'] ?? null;
        }

        foreach (['provinsi_kode', 'kabupaten_kode', 'kecamatan_kode', 'kelurahan_kode'] as $field) {
            if (Schema::hasColumn('users', $field)) {
                $attributes[$field] = $row[$field] ?? $existingUser?->{$field};
            }
        }

        $user = ($existingUser ?? new User)->forceFill($attributes);

        if ($this->save) {
            try {
                $user->save();
            } catch (\Throwable $e) {
                $this->importErrors[] = [
                    'row' => $this->processedRows + 1,
                    'attribute' => 'save',
                    'errors' => [__('User row could not be saved. Check duplicate values and required fields.')],
                    'values' => $row,
                ];
                $this->skippedRows++;
                $this->syncProgress(force: true);

                return null;
            }
        }

        $this->successfulRows++;
        $this->syncProgress();

        return $user;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'string'],
            'nip' => ['required', 'string'],
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'gender' => ['required', Rule::in(['male', 'female', 'Male', 'Female', 'MALE', 'FEMALE'])],
            'group' => ['nullable', Rule::in(User::$groups)],
            'password' => ['nullable', 'string'],
            'address' => ['required', 'string'],
            'employment_status' => ['nullable', Rule::in(array_keys(User::employmentStatuses()))],
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onFailure(Failure ...$failures): void
    {
        $failureCount = count($failures);

        $this->processedRows += $failureCount;
        $this->skippedRows += $failureCount;

        foreach ($failures as $failure) {
            $this->importErrors[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }

        $this->syncProgress(force: true);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function resolveReferenceId(string $modelClass, ?string $name): ?int
    {
        $name = is_string($name) ? trim($name) : null;

        if ($name === null || $name === '') {
            return null;
        }

        $cacheKey = $modelClass.':'.mb_strtolower($name);

        if (array_key_exists($cacheKey, $this->referenceCache)) {
            return $this->referenceCache[$cacheKey];
        }

        $existing = $modelClass::where('name', $name)->first();

        if ($existing !== null) {
            return $this->referenceCache[$cacheKey] = $existing->id;
        }

        if (! $this->save) {
            return $this->referenceCache[$cacheKey] = null;
        }

        return $this->referenceCache[$cacheKey] = $modelClass::create(['name' => $name])->id;
    }

    private function resolveExistingUser(array $row): ?User
    {
        $id = trim((string) ($row['id'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $nip = trim((string) ($row['nip'] ?? ''));

        return User::query()
            ->when($id !== '', fn (Builder $query) => $query->orWhere('id', $id))
            ->when($email !== '', fn (Builder $query) => $query->orWhere('email', $email))
            ->when($nip !== '', fn (Builder $query) => $query->orWhere('nip', $nip))
            ->first();
    }

    private function rowIsUniqueForUser(array $row, ?User $user): bool
    {
        foreach (['nip', 'email', 'phone'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));

            if ($value === '') {
                continue;
            }

            $conflict = User::query()
                ->where($field, $value)
                ->when($user, fn (Builder $query) => $query->whereKeyNot($user->id))
                ->exists();

            if ($conflict) {
                $this->importErrors[] = [
                    'row' => $this->processedRows + 1,
                    'attribute' => $field,
                    'errors' => [__('The :field value is already used by another user.', ['field' => $field])],
                    'values' => $row,
                ];

                return false;
            }
        }

        return true;
    }

    private function resolveManagerId(array $row): ?string
    {
        $managerId = trim((string) ($row['manager_id'] ?? ''));
        $managerNip = trim((string) ($row['manager_nip'] ?? ''));
        $managerEmail = trim((string) ($row['manager_email'] ?? ''));

        if ($managerId !== '') {
            return User::query()->whereKey($managerId)->value('id');
        }

        if ($managerNip !== '') {
            return User::query()->where('nip', $managerNip)->value('id');
        }

        if ($managerEmail !== '') {
            return User::query()->where('email', $managerEmail)->value('id');
        }

        return null;
    }

    private function normalizeGender(string $gender): string
    {
        return mb_strtolower(trim($gender)) === 'female' ? 'female' : 'male';
    }

    private function hasUserCityColumn(): bool
    {
        return $this->hasCityColumn ??= Schema::hasColumn('users', 'city');
    }

    private function syncProgress(bool $force = false): void
    {
        if ($this->progressRunId === null) {
            return;
        }

        if (! $force && $this->processedRows > 1 && $this->processedRows % 100 !== 0) {
            return;
        }

        $run = ImportExportRun::query()->find($this->progressRunId);

        if (! $run) {
            return;
        }

        $totalRows = max((int) ($run->total_rows ?? 0), $this->processedRows);
        $progress = $totalRows > 0
            ? max(5, min(95, (int) floor(($this->processedRows / $totalRows) * 100)))
            : 5;

        $run->forceFill([
            'processed_rows' => $this->processedRows,
            'total_rows' => $totalRows,
            'progress_percentage' => $progress,
            'meta' => array_merge($run->meta ?? [], [
                'successful_rows' => $this->successfulRows,
                'skipped_rows' => $this->skippedRows,
                'errors' => $this->importErrors,
            ]),
        ])->save();
    }
}
