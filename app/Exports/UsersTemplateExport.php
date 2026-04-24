<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersTemplateExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Return one example row
        return collect([
            [
                '12345678',             // NIP
                'John Doe',             // Name
                'john@example.com',     // Email
                'user',                 // Group (user, admin)
                'password123',          // Password
                '08123456789',          // Phone
                'Male',                 // Gender
                '5000000',              // Basic Salary
                '25000',                // Hourly Rate
                'IT',                   // Division
                'Senior Developer',     // Job Title
                'Bachelor',             // Education
                '1990-01-01',           // Birth Date
                'Jakarta',              // Birth Place
                'Jl. Sudirman No. 1',   // Address
                'Jakarta',               // City
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Name',
            'Email',
            'Group',
            'Password',
            'Phone',
            'Gender',
            'Basic Salary',
            'Hourly Rate',
            'Division',
            'Job Title',
            'Education',
            'Birth Date',
            'Birth Place',
            'Address',
            'City',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
