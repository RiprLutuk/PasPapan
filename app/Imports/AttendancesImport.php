<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Shift;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class AttendancesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public $importedCount = 0;
    public $skippedCount = 0;
    public $importErrors = [];

    public function __construct(public bool $save = true)
    {
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $shift_id = Shift::where('name', $row['shift'] ?? '')->first()?->id ?? ($row['shift_id'] ?? null);

        $nip = trim((string) ($row['nip'] ?? ''));
        $nip = trim((string) ($row['nip'] ?? ''));
        $mode = $this->save ? "[IMPORT]" : "[PREVIEW]";
        \Illuminate\Support\Facades\Log::info("{$mode} Processing NIP: '{$nip}'");

        $user = User::where('nip', $nip)->first();
        if (!$user) {
            \Illuminate\Support\Facades\Log::warning("❌ User NOT FOUND for NIP: '{$nip}'");
            $this->importErrors[] = "Row NIP '{$nip}' not found / User tidak ditemukan.";
            $this->skippedCount++;
            return null;
        }
        \Illuminate\Support\Facades\Log::info("✅ User FOUND: ID {$user->id}, Name {$user->name}");

        try {
             $date = $row['date'];
             \Illuminate\Support\Facades\Log::info("Raw Date: '{$date}'");
             if (is_numeric($date)) {
                 $date = Date::excelToDateTimeObject($date)->format('Y-m-d');
             } else {
                 $date = Carbon::parse($date)->format('Y-m-d');
             }
             \Illuminate\Support\Facades\Log::info("Parsed Date: '{$date}'");
        } catch (\Throwable $th) {
             \Illuminate\Support\Facades\Log::error("❌ Date Parse Error: " . $th->getMessage());
             $date = now()->format('Y-m-d');
        }

        if (Attendance::where('user_id', $user->id)->where('date', $date)->exists()) {
             \Illuminate\Support\Facades\Log::warning("⚠️ Duplicate Found for User {$user->id} on {$date}");
             $this->importErrors[] = "Row NIP '{$nip}' Date '{$date}' already exists / Data duplikat.";
             $this->skippedCount++;
             return null;
        }
        
        $attendance = (new Attendance)->forceFill([
            'user_id' => $user->id,
            'date' => $date,
            'time_in' => $row['time_in'],
            'time_out' => $row['time_out'],
            'shift_id' => $shift_id,
            'status' => $this->getStatus($row['status']) ?? $row['status'],
            'note' => $row['note'],
            'attachment' => $row['attachment'] ?? null,
            'created_at' => $row['created_at'] ?? now(),
            'updated_at' => $row['updated_at'] ?? now(),
        ]);
        if ($this->save) {
            try {
                \Illuminate\Support\Facades\Log::info("💾 Saving attendance to DB...");
                $attendance->save();
                \Illuminate\Support\Facades\Log::info("✅ Saved Successfully! ID: " . $attendance->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("❌ Save FAILED: " . $e->getMessage());
                $this->importErrors[] = "Save failed for NIP '{$nip}'. Please check the row data.";
                $this->skippedCount++;
                return null;
            }
            $this->importedCount++;
            return null;
        }
        
        $this->importedCount++;
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

    public function onFailure(Failure ...$failures)
    {
        $this->skippedCount += count($failures);
    }
}
