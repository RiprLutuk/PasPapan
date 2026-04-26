<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersTemplateExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect([
            [
                '',
                '0000000000001001',
                'Budi Santoso',
                'budi.santoso@example.com',
                'user',
                'password',
                '081234560001',
                'male',
                'active',
                'id',
                '5000000',
                '28902',
                'Operations',
                'Staff',
                'S1',
                '',
                'manager.operations@example.com',
                '1996-04-20',
                'Jakarta',
                'Jl. Merdeka No. 1',
                '31',
                '31.71',
                '31.71.01',
                '31.71.01.1001',
                '',
                '',
                '',
            ],
            [
                '',
                '0000000000001002',
                'Siti Aminah',
                'siti.aminah@example.com',
                'user',
                'password',
                '081234560002',
                'female',
                'active',
                'id',
                '7500000',
                '43353',
                'Operations',
                'Senior',
                'S1',
                '',
                'manager.operations@example.com',
                '1992-08-15',
                'Bandung',
                'Jl. Asia Afrika No. 2',
                '31',
                '31.71',
                '31.71.01',
                '31.71.01.1001',
                '',
                '',
                '',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'NIP',
            'Name',
            'Email',
            'Group',
            'Password',
            'Phone',
            'Gender',
            'Employment Status',
            'Language',
            'Basic Salary',
            'Hourly Rate',
            'Division',
            'Job Title',
            'Education',
            'Manager NIP',
            'Manager Email',
            'Birth Date',
            'Birth Place',
            'Address',
            'Provinsi Kode',
            'Kabupaten Kode',
            'Kecamatan Kode',
            'Kelurahan Kode',
            'City',
            'Email Verified At',
            'Created At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:AA1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF1F2937');
        $sheet->getStyle('A1:AA1')->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');
        $sheet->getStyle('A:AA')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $comments = [
            'A1' => 'Optional. Fill existing user ID to update a specific user. Leave blank to create or match by email/NIP.',
            'B1' => 'Required. Unique employee NIP. Existing NIP updates that user.',
            'C1' => 'Required. Full name.',
            'D1' => 'Required. Unique email. Existing email updates that user.',
            'E1' => 'Optional. Allowed: user, admin, superadmin. Default user.',
            'F1' => 'Optional for updates. Required for new users if you do not want default password.',
            'G1' => 'Optional, but must be unique when filled.',
            'H1' => 'Required. Allowed: male, female.',
            'I1' => 'Optional. Allowed: active, inactive, resigned, deletion_requested, deleted. Default active.',
            'J1' => 'Optional. Default id.',
            'K1' => 'Optional numeric salary, e.g. 5000000.',
            'L1' => 'Optional numeric hourly rate, e.g. 28902.',
            'M1' => 'Optional. Must match division name; new division is created during real import if missing.',
            'N1' => 'Optional. Must match job title name; new job title is created during real import if missing.',
            'O1' => 'Optional. Must match education name; new education is created during real import if missing.',
            'P1' => 'Optional. Direct manager NIP.',
            'Q1' => 'Optional. Direct manager email. Used if Manager NIP is blank.',
            'R1' => 'Optional. Use YYYY-MM-DD.',
            'S1' => 'Optional birth place.',
            'T1' => 'Required for user accounts in the admin form; import accepts the same address field.',
            'U1' => 'Optional wilayah province code.',
            'V1' => 'Optional wilayah regency/city code.',
            'W1' => 'Optional wilayah district code.',
            'X1' => 'Optional wilayah village code.',
            'Y1' => 'Legacy only. Current schema may not have city column.',
            'Z1' => 'Optional. Use YYYY-MM-DD HH:mm. Blank means verified at import time.',
            'AA1' => 'Optional. Use YYYY-MM-DD HH:mm.',
        ];

        foreach ($comments as $cell => $text) {
            $sheet->getComment($cell)->getText()->createTextRun($text);
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
