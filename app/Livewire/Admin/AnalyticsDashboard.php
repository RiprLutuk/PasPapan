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

    private $provinceCoordinates = [
        '11' => ['lat' => 4.695135, 'lng' => 96.749399],
        '12' => ['lat' => 2.115355, 'lng' => 99.545097],
        '13' => ['lat' => -0.739940, 'lng' => 100.800005],
        '14' => ['lat' => 0.293347, 'lng' => 101.706829],
        '15' => ['lat' => -1.610123, 'lng' => 103.613120],
        '16' => ['lat' => -3.319437, 'lng' => 103.914399],
        '17' => ['lat' => -3.792845, 'lng' => 102.260764],
        '18' => ['lat' => -4.558585, 'lng' => 105.406808],
        '19' => ['lat' => -2.741051, 'lng' => 106.440587],
        '21' => ['lat' => 3.945651, 'lng' => 108.142867],
        '31' => ['lat' => -6.208763, 'lng' => 106.845599],
        '32' => ['lat' => -6.914744, 'lng' => 107.609810],
        '33' => ['lat' => -7.150975, 'lng' => 110.140259],
        '34' => ['lat' => -7.795580, 'lng' => 110.369490],
        '35' => ['lat' => -7.536064, 'lng' => 112.238402],
        '36' => ['lat' => -6.405817, 'lng' => 106.064018],
        '51' => ['lat' => -8.409518, 'lng' => 115.188916],
        '52' => ['lat' => -8.652933, 'lng' => 117.361648],
        '53' => ['lat' => -8.657382, 'lng' => 121.079370],
        '61' => ['lat' => -0.278781, 'lng' => 111.475285],
        '62' => ['lat' => -1.681488, 'lng' => 113.382355],
        '63' => ['lat' => -3.092642, 'lng' => 115.283759],
        '64' => ['lat' => 0.538659, 'lng' => 116.419389],
        '65' => ['lat' => 3.073091, 'lng' => 116.041397],
        '71' => ['lat' => 0.624693, 'lng' => 123.975002],
        '72' => ['lat' => -1.430025, 'lng' => 121.445618],
        '73' => ['lat' => -3.668799, 'lng' => 119.974053],
        '74' => ['lat' => -4.144910, 'lng' => 122.174605],
        '75' => ['lat' => 0.699937, 'lng' => 122.446724],
        '76' => ['lat' => -2.844137, 'lng' => 119.232078],
        '81' => ['lat' => -3.238462, 'lng' => 130.145273],
        '82' => ['lat' => 1.570999, 'lng' => 127.808769],
        '91' => ['lat' => -4.269928, 'lng' => 138.080353],
        '92' => ['lat' => -1.336115, 'lng' => 133.174716],
        '93' => ['lat' => -7.218873, 'lng' => 139.737033],
        '94' => ['lat' => -4.150024, 'lng' => 136.215573],
        '95' => ['lat' => -4.246875, 'lng' => 139.207802],
        '96' => ['lat' => -1.300588, 'lng' => 131.905634],
    ];

    public function mount()
    {
        if (\App\Helpers\Editions::reportingLocked()) {
            session()->flash('show-feature-lock', ['title' => 'Analytics Locked', 'message' => 'Advanced Analytics is an Enterprise Feature 🔒. Please Upgrade.']);
            return redirect()->route('admin.dashboard');
        }

        $this->month = (int) date('m');
        $this->year = (int) date('Y');
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
                absentStats: $this->absentStats,
                regionDistribution: $this->employeeRegionDistribution
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
        $startDate = Carbon::createFromDate((int) $this->year, (int) $this->month, 1);
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
            $dateStr = Carbon::parse($att->date)->format('Y-m-d');
            $shiftStartStr = is_string($att->start_time) ? $att->start_time : $att->start_time->format('H:i:s');
            $shiftStartTime = strlen($shiftStartStr) > 8 ? substr($shiftStartStr, -8) : $shiftStartStr;

            $shiftStart = Carbon::parse($dateStr . ' ' . $shiftStartTime);

            $checkInStr = is_string($att->time_in) ? $att->time_in : $att->time_in->format('Y-m-d H:i:s');
            if (strlen($checkInStr) > 8) {
                $checkIn = Carbon::parse($checkInStr);
            } else {
                $checkIn = Carbon::parse($dateStr . ' ' . $checkInStr);
            }

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

    public function getEmployeeRegionDistributionProperty()
    {
        // Join users with wilayah to get the province name. 
        // We use leftJoin in case some users don't have a province set.
        $regions = User::where('group', 'user') // Count only regular users/employees
            ->leftJoin('wilayah', function ($join) {
                // adding COLLATE utf8mb4_unicode_ci to resolve Illegal mix of collations error
                $join->on('users.provinsi_kode', '=', DB::raw('wilayah.kode COLLATE utf8mb4_unicode_ci'));
            })
            ->select(
                'users.id',
                'users.name',
                'users.provinsi_kode',
                DB::raw('COALESCE(wilayah.nama, "Unknown") as region_name')
            )
            ->get();

        $mapData = [];

        foreach ($regions as $user) {
            $kode = $user->provinsi_kode;
            $coords = $this->provinceCoordinates[$kode] ?? null;

            if ($coords) {
                // Randomize coordinates within ~50km radius of province center for realistic clustering spread
                $latOffset = (mt_rand(-500, 500) / 1000);
                $lngOffset = (mt_rand(-500, 500) / 1000);

                $mapData[] = [
                    'id' => $user->id,
                    'name' => "User " . $user->name,
                    'region' => $user->region_name,
                    'lat' => $coords['lat'] + $latOffset,
                    'lng' => $coords['lng'] + $lngOffset
                ];
            }
        }

        return $mapData;
    }

    public function getGenderDemographicsProperty()
    {
        return User::where('group', 'user')
            ->select('gender', DB::raw('count(*) as total'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->toArray();
    }

    public function getHeadcountStatsProperty()
    {
        $divisionUsers = User::where('group', 'user')
            ->select('division_id', DB::raw('count(*) as total_users'))
            ->whereNotNull('division_id')
            ->groupBy('division_id')
            ->pluck('total_users', 'division_id');

        $divisions = \App\Models\Division::all();

        $labels = [];
        $data = [];

        foreach ($divisions as $div) {
            $labels[] = $div->name;
            $data[] = $divisionUsers[$div->id] ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getEstimatedPayrollProperty()
    {
        return User::where('group', 'user')->sum('basic_salary') ?? 0;
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
        $start = Carbon::createFromDate((int) $this->year, (int) $this->month, 1);
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
            'regionDistribution' => $this->employeeRegionDistribution,
            'topDiligent' => $this->topDiligentEmployees,
            'topLate' => $this->topLateEmployees,
            'topEarlyLeavers' => $this->topEarlyLeavers,
            'workHoursPerDay' => (int) \App\Models\Setting::getValue('attendance.work_hours_per_day', 8),
            'summary' => $this->summaryStats,
            'genderDemographics' => $this->genderDemographics,
            'headcountStats' => $this->headcountStats,
            'estimatedPayroll' => $this->estimatedPayroll,
        ]);
    }
}
