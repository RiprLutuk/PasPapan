<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    /**
     * @param  array<string>  $groups
     */
    public function __construct(private array $groups = ['user']) {}

    public function query(): Builder
    {
        return User::query()
            ->with(['division:id,name', 'jobTitle:id,name', 'education:id,name'])
            ->whereIn('group', $this->groups)
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            '#',
            'NIP',
            'Name',
            'Email',
            'Group',
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
            'Created At',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($user): array
    {
        return [
            ++$this->rowNumber,
            (string) $user->nip,
            $user->name,
            $user->email,
            $user->group,
            (string) $user->phone,
            $user->gender,
            $user->basic_salary,
            $user->hourly_rate,
            $user->division?->name,
            $user->jobTitle?->name,
            $user->education?->name,
            $user->birth_date?->format('Y-m-d'),
            $user->birth_place,
            $user->address,
            $user->getAttribute('city'),
            $user->created_at?->format('Y-m-d H:i'),
        ];
    }
}
