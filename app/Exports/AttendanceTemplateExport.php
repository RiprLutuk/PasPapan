<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function headings(): array
    {
        return [
            'nip',
            'date',
            'time_in',
            'time_out',
            'status',
            'shift',
            'note',
            'attachment',
        ];
    }

    public function title(): string
    {
        return 'Attendance Template';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function array(): array
    {
        return [
            [
                '12345678',      // nip
                '2024-01-01',    // date
                '08:00',         // time_in
                '17:00',         // time_out
                'present',       // status
                'Shift 1', // shift
                'Sample Note',   // note
                'https://example.com/file.jpg', // attachment
            ],
        ];
    }
}
