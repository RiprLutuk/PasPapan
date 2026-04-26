<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
            'created_at',
            'updated_at',
        ];
    }

    public function title(): string
    {
        return 'Attendance Template';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF1F2937');
        $sheet->getStyle('A1:J1')->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');
        $sheet->getStyle('A:J')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $sheet->getComment('A1')->getText()->createTextRun('Required. Must match an employee NIP.');
        $sheet->getComment('B1')->getText()->createTextRun('Required. Use YYYY-MM-DD.');
        $sheet->getComment('C1')->getText()->createTextRun('Use YYYY-MM-DD HH:mm. Leave blank for absent/excused/sick if no punch time exists.');
        $sheet->getComment('D1')->getText()->createTextRun('Use YYYY-MM-DD HH:mm. For overnight shift, use the next date.');
        $sheet->getComment('E1')->getText()->createTextRun('Required. Allowed: present, late, excused, sick, absent. Indonesian labels are also accepted: hadir, terlambat, izin, sakit, tidak hadir.');
        $sheet->getComment('F1')->getText()->createTextRun('Optional. Must match shift name, e.g. Shift Pagi, Shift Sore, Shift Malam.');
        $sheet->getComment('G1')->getText()->createTextRun('Optional note.');
        $sheet->getComment('H1')->getText()->createTextRun('Optional attachment URL/path.');
        $sheet->getComment('I1')->getText()->createTextRun('Optional. Use YYYY-MM-DD HH:mm; leave blank to use import time.');
        $sheet->getComment('J1')->getText()->createTextRun('Optional. Use YYYY-MM-DD HH:mm; leave blank to use import time.');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function array(): array
    {
        return [
            [
                '0000000000000001',
                '2026-04-20',
                '2026-04-20 07:02',
                '2026-04-20 15:04',
                'present',
                'Shift Pagi',
                'Check in/out normal',
                '',
                '',
                '',
            ],
            [
                '0000000000000002',
                '2026-04-20',
                '2026-04-20 15:08',
                '2026-04-20 23:05',
                'late',
                'Shift Sore',
                'Late arrival',
                '',
                '',
                '',
            ],
            [
                '0000000000000003',
                '2026-04-20',
                '2026-04-20 23:00',
                '2026-04-21 07:03',
                'present',
                'Shift Malam',
                'Overnight shift uses next date for time_out',
                '',
                '',
                '',
            ],
            [
                '0000000000000004',
                '2026-04-20',
                '',
                '',
                'sick',
                '',
                'Medical leave',
                'https://example.com/medical-letter.pdf',
                '',
                '',
            ],
        ];
    }
}
