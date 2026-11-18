<?php

namespace App\Services\Reporting;

use App\Contracts\ReportingServiceInterface;
use App\Exports\ActivityLogsExport;
use App\Exports\AttendancesExport;
use App\Exports\UsersExport;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Education;
use App\Models\JobTitle;
use App\Services\Enterprise\LicenseGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class EnterpriseReportingService implements ReportingServiceInterface
{
    public function __construct()
    {
        LicenseGuard::check();
    }

    public function exportUsers(array $groups)
    {
        return Excel::download(
            new UsersExport($groups),
            'users.xlsx'
        );
    }

    public function exportAttendances($month, $year, $division, $jobTitle, $education)
    {
        $divName = $division ? Division::find($division)?->name : null;
        $jobName = $jobTitle ? JobTitle::find($jobTitle)?->name : null;
        $eduName = $education ? Education::find($education)?->name : null;

        $filename = 'attendances'
            .($month ? '_'.Carbon::parse($month)->format('F-Y') : '')
            .($year && ! $month ? '_'.$year : '')
            .($divName ? '_'.Str::slug($divName) : '')
            .($jobName ? '_'.Str::slug($jobName) : '')
            .($eduName ? '_'.Str::slug($eduName) : '')
            .'.xlsx';

        return Excel::download(new AttendancesExport(
            $month,
            $year,
            $division,
            $jobTitle,
            $education
        ), $filename);
    }

    public function exportActivityLogs()
    {
        return Excel::download(new ActivityLogsExport, 'activity-logs-'.now()->format('Y-m-d').'.xlsx');
    }

    public function exportMonthlyReportPdf($month, $year)
    {
        $date = Carbon::createFromDate($year, $month, 1);

        $attendances = Attendance::with('user', 'shift')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get()
            ->groupBy('user_id');

        $pdf = Pdf::loadView('admin.reports.monthly_pdf', [
            'attendances' => $attendances,
            'month' => $date->format('F'),
            'year' => $year,
            'date' => $date,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('monthly-report-'.$date->format('F-Y').'.pdf');
    }
}
