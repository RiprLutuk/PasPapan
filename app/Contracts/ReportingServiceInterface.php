<?php

namespace App\Contracts;

interface ReportingServiceInterface
{
    /**
     * Export users to Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     */
    public function exportUsers(array $groups);

    /**
     * Export attendances to Excel with filters.
     *
     * @param  int|null  $month
     * @param  int|null  $year
     * @param  string|null  $division
     * @param  string|null  $jobTitle
     * @param  string|null  $education
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     */
    public function exportAttendances($month, $year, $division, $jobTitle, $education);

    /**
     * Export activity logs to Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     */
    public function exportActivityLogs();

    /**
     * Export monthly report to PDF.
     *
     * @param  int  $month
     * @param  int  $year
     * @return \Illuminate\Http\Response|null
     */
    public function exportMonthlyReportPdf($month, $year);
}
