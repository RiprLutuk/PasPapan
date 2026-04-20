<?php

namespace App\Imports;

use App\Models\Division;
use App\Models\Education;
use App\Models\ImportExportRun;
use App\Models\JobTitle;
use App\Models\User;
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

class UsersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading, SkipsEmptyRows
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
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row)
    {
        $this->processedRows++;
        $now = now();
        $divisionId = $this->resolveReferenceId(Division::class, $row['division'] ?? null);
        $jobTitleId = $this->resolveReferenceId(JobTitle::class, $row['job_title'] ?? null);
        $educationId = $this->resolveReferenceId(Education::class, $row['education'] ?? null);

        $attributes = [
            'id' => isset($row['id']) ? $row['id'] : null,
            'nip' => (string) $row['nip'],
            'name' => $row['name'],
            'email' => $row['email'],
            'group' => $row['group'] ?? 'user',
            'phone' => (string) $row['phone'],
            'gender' => $row['gender'],
            'basic_salary' => $row['basic_salary'] ?? 0,
            'hourly_rate' => $row['hourly_rate'] ?? 0,
            'birth_date' => $row['birth_date'],
            'birth_place' => $row['birth_place'],
            'address' => $row['address'],
            'education_id' => $educationId,
            'division_id' => $divisionId,
            'job_title_id' => $jobTitleId,
            'password' => Hash::make($row['password']),
            'created_at' => isset($row['created_at']) ? $row['created_at'] : $now,
            'updated_at' => $now,
        ];

        if ($this->hasUserCityColumn()) {
            $attributes['city'] = $row['city'] ?? null;
        }

        $user = (new User)->forceFill($attributes);

        if ($this->save) {
            $user->save();
        }

        $this->successfulRows++;
        $this->syncProgress();

        return $user;
    }

    public function rules(): array
    {
        return [
            'nip' => ['required', Rule::unique('users', 'nip')],
            'name' => ['required', 'string'],
            'email' => ['required', 'string', Rule::unique('users', 'email')],
            'phone' => ['nullable', Rule::unique('users', 'phone')],
            'gender' => ['required', 'string'],
            'password' => ['required', 'string'],
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

        $cacheKey = $modelClass . ':' . mb_strtolower($name);

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
        ])->save();
    }
}
