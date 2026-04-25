<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendancesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private $month = null,
        private $year = null,
        private $division = null,
        private $jobTitle = null,
        private $education = null,
        private $startDate = null,
        private $endDate = null
    ) {}

    /**
     * @return Builder<Attendance>
     */
    public function query(): Builder
    {
        return Attendance::query()
            ->with(['user:id,name,nip', 'shift:id,name'])
            ->filter(
                month: $this->month,
                year: $this->year,
                division: $this->division,
                jobTitle: $this->jobTitle,
                education: $this->education
            )
            ->when($this->startDate && $this->endDate, function (Builder $query) {
                $query->whereBetween('date', [$this->startDate, $this->endDate]);
            })
            ->orderBy('date')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Name',
            'NIP',
            'Time In',
            'Time Out',
            'Shift',
            'Barcode Id',
            'Coordinates',
            'Status',
            'Note',
            'Attachment',
            'Created At',
            'Updated At',
            'User Id',
            'Shift Id',
            'Raw Status',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($attendance): array
    {
        return [
            ++$this->rowNumber,
            $attendance->date?->format('Y-m-d'),
            $attendance->user?->name,
            (string) ($attendance->user?->nip ?? ''),
            $attendance->time_in?->format('H:i:s'),
            $attendance->time_out?->format('H:i:s'),
            $attendance->shift?->name,
            $attendance->barcode_id,
            $this->formatCoordinates($attendance),
            __($attendance->status),
            $attendance->note,
            $this->formatAttachment($attendance->attachment),
            $attendance->created_at?->format('Y-m-d H:i:s'),
            $attendance->updated_at?->format('Y-m-d H:i:s'),
            $attendance->user_id,
            $attendance->shift_id,
            $attendance->status,
        ];
    }

    private function formatCoordinates(Attendance $attendance): ?string
    {
        $parts = [];

        if ($attendance->latitude_in !== null && $attendance->longitude_in !== null) {
            $parts[] = 'IN: '.$attendance->latitude_in.','.$attendance->longitude_in;
        }

        if ($attendance->latitude_out !== null && $attendance->longitude_out !== null) {
            $parts[] = 'OUT: '.$attendance->latitude_out.','.$attendance->longitude_out;
        }

        return empty($parts) ? null : implode(' | ', $parts);
    }

    private function formatAttachment(mixed $attachment): ?string
    {
        if (! is_string($attachment) || $attachment === '') {
            return null;
        }

        $decoded = json_decode($attachment, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return implode(' | ', array_filter([
                $decoded['in'] ?? null,
                $decoded['out'] ?? null,
            ]));
        }

        return $attachment;
    }
}
