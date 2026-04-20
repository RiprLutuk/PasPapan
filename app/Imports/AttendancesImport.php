<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\ImportExportRun;
use App\Models\User;
use App\Models\Shift;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class AttendancesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading, SkipsEmptyRows
{
    public int $processedRows = 0;
    public $importedCount = 0;
    public $skippedCount = 0;
    public $importErrors = [];

    /** @var array<string, User|null> */
    private array $userCache = [];

    /** @var array<string, int|null> */
    private array $shiftCache = [];

    /** @var array<string, bool> */
    private array $attendanceExistenceCache = [];

    public function __construct(
        public bool $save = true,
        private readonly ?int $progressRunId = null,
    )
    {
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->processedRows++;
        $shiftId = $this->resolveShiftId($row);

        $nip = trim((string) ($row['nip'] ?? ''));
        $user = $this->resolveUser($nip);

        if (!$user) {
            $this->importErrors[] = "Row NIP '{$nip}' not found / User tidak ditemukan.";
            $this->skippedCount++;
            $this->syncProgress();
            return null;
        }

        $date = $this->parseDate($row['date'] ?? null);
        if ($date === null) {
            $this->importErrors[] = "Row NIP '{$nip}' has an invalid date format / Format tanggal tidak valid.";
            $this->skippedCount++;
            $this->syncProgress();
            return null;
        }

        if ($this->attendanceExists($user->id, $date)) {
            $this->importErrors[] = "Row NIP '{$nip}' Date '{$date}' already exists / Data duplikat.";
            $this->skippedCount++;
            $this->syncProgress();
            return null;
        }

        $attendance = (new Attendance)->forceFill([
            'user_id' => $user->id,
            'date' => $date,
            'time_in' => $row['time_in'],
            'time_out' => $row['time_out'],
            'shift_id' => $shiftId,
            'status' => $this->getStatus($row['status']) ?? $row['status'],
            'note' => $row['note'],
            'attachment' => $row['attachment'] ?? null,
            'created_at' => $row['created_at'] ?? now(),
            'updated_at' => $row['updated_at'] ?? now(),
        ]);

        if ($this->save) {
            try {
                $attendance->save();
            } catch (\Throwable $e) {
                $this->importErrors[] = "Save failed for NIP '{$nip}'. Please check the row data.";
                $this->skippedCount++;
                $this->syncProgress(force: true);
                return null;
            }

            $this->attendanceExistenceCache[$this->attendanceCacheKey($user->id, $date)] = true;
            $this->importedCount++;
            $this->syncProgress();
            return null;
        }

        $this->importedCount++;
        $this->syncProgress();
        return $attendance;
    }

    private function getStatus($status)
    {
        switch (Str::lower($status)) {
            case 'hadir':
                return 'present';
            case 'terlambat':
                return 'late';
            case 'izin':
                return 'excused';
            case 'sakit':
                return 'sick';
            case 'tidak hadir':
                return 'absent';
            default:
                return null;
        }
    }

    public function rules(): array
    {
        return [
            'nip' => 'required|exists:users,nip',
            'date' => 'required',
            'status' => 'required',
            // 'shift' => 'nullable|exists:shifts,name',
            // 'barcode_id' => 'nullable|exists:barcodes,id',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onFailure(Failure ...$failures)
    {
        $this->processedRows += count($failures);
        $this->skippedCount += count($failures);

        foreach ($failures as $failure) {
            $this->importErrors[] = "Row {$failure->row()} {$failure->attribute()}: " . implode(', ', $failure->errors());
        }

        $this->syncProgress(force: true);
    }

    private function resolveUser(string $nip): ?User
    {
        if ($nip === '') {
            return null;
        }

        if (array_key_exists($nip, $this->userCache)) {
            return $this->userCache[$nip];
        }

        return $this->userCache[$nip] = User::where('nip', $nip)->first();
    }

    private function resolveShiftId(array $row): ?int
    {
        $shiftName = trim((string) ($row['shift'] ?? ''));

        if ($shiftName !== '') {
            if (array_key_exists($shiftName, $this->shiftCache)) {
                return $this->shiftCache[$shiftName];
            }

            return $this->shiftCache[$shiftName] = Shift::where('name', $shiftName)->value('id');
        }

        return isset($row['shift_id']) ? (int) $row['shift_id'] : null;
    }

    private function parseDate(mixed $value): ?string
    {
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            if (is_string($value) && trim($value) !== '') {
                return Carbon::parse($value)->format('Y-m-d');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function attendanceExists(string $userId, string $date): bool
    {
        $cacheKey = $this->attendanceCacheKey($userId, $date);

        if (array_key_exists($cacheKey, $this->attendanceExistenceCache)) {
            return $this->attendanceExistenceCache[$cacheKey];
        }

        return $this->attendanceExistenceCache[$cacheKey] = Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->exists();
    }

    private function attendanceCacheKey(string $userId, string $date): string
    {
        return $userId . '|' . $date;
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
