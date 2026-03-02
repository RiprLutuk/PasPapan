<?php

namespace App\Livewire\Admin;

use Livewire\Component;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class AnalyticsDashboard extends Component
{
    public $month;
    public $year;

    public function mount()
    {
        if (\App\Helpers\Editions::reportingLocked()) {
            session()->flash('show-feature-lock', ['title' => 'Analytics Locked', 'message' => 'Advanced Analytics is an Enterprise Feature 🔒. Please Upgrade.']);
            return redirect()->route('admin.dashboard');
        }

        $this->month = date('m');
        $this->year = date('Y');
    }

    public function updated($property)
    {
        if (in_array($property, ['month', 'year'])) {
            $this->dispatch(
                'chart-update',
                trend: $this->attendanceTrend,
                metrics: $this->attendanceMetrics,
                divisionStats: $this->divisionStats,
                lateBuckets: $this->lateBuckets,
                absentStats: $this->absentStats
            );
        }
    }

    public function getAttendanceMetricsProperty()
    {
        return Attendance::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function getAttendanceTrendProperty()
    {
        $startDate = Carbon::createFromDate($this->year, $this->month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $data = DB::table('attendances')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select('date', 'status', DB::raw('count(*) as total'))
            ->groupBy('date', 'status')
            ->get();

        // Remap to [date => [status => count]] for robust lookup
        $lookup = [];
        foreach ($data as $row) {
            // Ensure date is Y-m-d string (truncate time if present)
            $d = substr((string)$row->date, 0, 10);
            $lookup[$d][$row->status] = $row->total;
        }

        $trend = [];
        $current = $startDate->copy();

        // Loop through every day of the month
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');

            $trend['labels'][] = $current->format('d M');
            $trend['present'][] = $lookup[$dateStr]['present'] ?? 0;
            $trend['late'][] = $lookup[$dateStr]['late'] ?? 0;

            // Sum absent types
            $absentCount = ($lookup[$dateStr]['sick'] ?? 0) +
                ($lookup[$dateStr]['excused'] ?? 0) +
                ($lookup[$dateStr]['alpha'] ?? 0) +
                ($lookup[$dateStr]['absent'] ?? 0);

            $trend['absent'][] = $absentCount;

            $current->addDay();
        }

        return $trend;
    }

    public function getTopDiligentEmployeesProperty()
    {
        // Logic: Lowest average check-in time (for 'present' status only)
        return User::join('attendances', 'users.id', '=', 'attendances.user_id')
            ->whereMonth('attendances.date', $this->month)
            ->whereYear('attendances.date', $this->year)
            ->where('attendances.status', 'present')
            ->whereNotNull('attendances.time_in')
            ->select('users.id', 'users.name', 'users.profile_photo_path', DB::raw('AVG(TIME_TO_SEC(attendances.time_in)) as avg_check_in'))
            ->groupBy('users.id', 'users.name', 'users.profile_photo_path')
            ->orderBy('avg_check_in', 'asc')
            ->limit(5)
            ->get();
    }

    public function getTopLateEmployeesProperty()
    {
        // Logic: Highest count of 'late' status
        return User::join('attendances', 'users.id', '=', 'attendances.user_id')
            ->whereMonth('attendances.date', $this->month)
            ->whereYear('attendances.date', $this->year)
            ->where('attendances.status', 'late')
            ->select('users.id', 'users.name', 'users.profile_photo_path', DB::raw('count(*) as late_count'))
            ->groupBy('users.id', 'users.name', 'users.profile_photo_path')
            ->orderByDesc('late_count')
            ->limit(5)
            ->get();
    }

    public function getTopEarlyLeaversProperty()
    {
        // Logic: Count of check-outs before shift end time
        return User::join('attendances', 'users.id', '=', 'attendances.user_id')
            ->join('shifts', 'attendances.shift_id', '=', 'shifts.id')
            ->whereMonth('attendances.date', $this->month)
            ->whereYear('attendances.date', $this->year)
            ->whereIn('attendances.status', ['present', 'late'])
            ->whereNotNull('attendances.time_out')
            ->whereRaw('attendances.time_out < shifts.end_time')
            ->select('users.id', 'users.name', 'users.profile_photo_path', DB::raw('count(*) as early_leave_count'))
            ->groupBy('users.id', 'users.name', 'users.profile_photo_path')
            ->orderByDesc('early_leave_count')
            ->limit(5)
            ->get();
    }

    public function getDivisionStatsProperty()
    {
        // Get total users per division (active only)
        $divisionUsers = User::where('group', 'user')
            ->select('division_id', DB::raw('count(*) as total_users'))
            ->whereNotNull('division_id')
            ->groupBy('division_id')
            ->pluck('total_users', 'division_id');

        // Get present count per division for selected month/year
        $attendanceCounts = Attendance::join('users', 'attendances.user_id', '=', 'users.id')
            ->whereMonth('attendances.date', $this->month)
            ->whereYear('attendances.date', $this->year)
            ->where('attendances.status', 'present')
            ->select('users.division_id', DB::raw('count(*) as present_count'))
            ->whereNotNull('users.division_id')
            ->groupBy('users.division_id')
            ->pluck('present_count', 'users.division_id');

        $divisions = \App\Models\Division::all();

        $labels = [];
        $data = [];

        foreach ($divisions as $div) {
            $labels[] = $div->name;
            $totalPossible = ($divisionUsers[$div->id] ?? 0) * 20; // Approx 20 working days
            $present = $attendanceCounts[$div->id] ?? 0;
            // Avoid division by zero, just raw count for now might be safer or relative percentage
            // Let's return raw "Present" count for now as "Performance" volume
            $data[] = $present;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getLateBucketsProperty()
    {
        // Optimized: Calculate late buckets without loading thousands of models into memory
        // We use raw SQL to calculate the diff if possible, or chunking.
        $lates = Attendance::join('shifts', 'attendances.shift_id', '=', 'shifts.id')
            ->whereMonth('attendances.date', $this->month)
            ->whereYear('attendances.date', $this->year)
            ->where('attendances.status', 'late')
            ->whereNotNull('attendances.time_in')
            ->select(
                'attendances.time_in',
                'shifts.start_time',
                'attendances.date'
            )
            ->get();

        $buckets = [
            '1-15m' => 0,
            '16-30m' => 0,
            '31-60m' => 0,
            '> 60m' => 0,
        ];

        foreach ($lates as $att) {
            $shiftStart = Carbon::parse($att->date . ' ' . $att->start_time);

            $checkInStr = is_string($att->time_in) ? $att->time_in : $att->time_in->format('H:i:s');
            $checkIn = Carbon::parse($att->date . ' ' . $checkInStr);

            $diffInMinutes = $shiftStart->diffInMinutes($checkIn, false);

            if ($diffInMinutes <= 0) continue;

            if ($diffInMinutes <= 15) $buckets['1-15m']++;
            elseif ($diffInMinutes <= 30) $buckets['16-30m']++;
            elseif ($diffInMinutes <= 60) $buckets['31-60m']++;
            else $buckets['> 60m']++;
        }

        return $buckets;
    }

    public function getAbsentStatsProperty()
    {
        return Attendance::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->whereIn('status', ['sick', 'excused', 'alpha'])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function getSummaryStatsProperty()
    {
        $totalEmployees = User::where('group', 'user')->count();
        $totalWorkDays = $this->getWorkDaysInMonth();
        $expectedTotalAttendance = $totalEmployees * $totalWorkDays;

        $presentCount = Attendance::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->where('status', 'present')
            ->count();

        $lateCount = Attendance::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->where('status', 'late')
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'attendance_rate' => $expectedTotalAttendance > 0 ? round(($presentCount + $lateCount) / $expectedTotalAttendance * 100, 1) : 0,
            'late_rate' => ($presentCount + $lateCount) > 0 ? round($lateCount / ($presentCount + $lateCount) * 100, 1) : 0,
            'avg_daily_attendance' => $totalWorkDays > 0 ? round(($presentCount + $lateCount) / $totalWorkDays) : 0,
        ];
    }

    private function getWorkDaysInMonth()
    {
        $start = Carbon::createFromDate($this->year, $this->month, 1);
        $end = $start->copy()->endOfMonth();

        // Simple calculation: Weekdays only
        // Ideally should subtract holidays
        return $start->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $end) + 1; // inclusive
    }

    public function render()
    {
        return view('livewire.admin.analytics-dashboard', [
            'metrics' => $this->attendanceMetrics,
            'trend' => $this->attendanceTrend,
            'divisionStats' => $this->divisionStats,
            'lateBuckets' => $this->lateBuckets,
            'absentStats' => $this->absentStats,
            'topDiligent' => $this->topDiligentEmployees,
            'topLate' => $this->topLateEmployees,
            'topEarlyLeavers' => $this->topEarlyLeavers,
            'workHoursPerDay' => (int) \App\Models\Setting::getValue('attendance.work_hours_per_day', 8),
            'summary' => $this->summaryStats,
        ]);
    }
}
