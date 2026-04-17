<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardComponent extends Component
{
    use AttendanceDetailTrait, WithPagination;

    public $showStatModal = false;
    public $selectedStatType = '';
    public $detailList = [];

    // Pending Counts
    public $pendingLeavesCount = 0;
    public $pendingReimbursementsCount = 0;
    public $pendingOvertimesCount = 0;
    public $pendingKasbonCount = 0;

    // Overview Counts
    public $missingFaceDataCount = 0;
    public $activeHolidaysCount = 0;

    // Filter Properties
    public $search = '';
    public $chartFilter = 'week_1';
    public $selectedDate;

    protected string $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->selectedDate = now()->toDateString();
    }

    public function showStatDetail($type)
    {
        $this->selectedStatType = $type;
        $this->showStatModal = true;
        $selectedDate = $this->resolvedSelectedDate()->toDateString();

        if ($type === 'absent') {
            // Users who have NO attendance record for the selected day.
            $this->detailList = User::where('group', 'user')
                ->managedBy(auth()->user())
                ->whereDoesntHave('attendances', fn($q) => $q->where('date', $selectedDate))
                ->get();
        } else {
            $query = Attendance::managedBy(auth()->user())
                ->with(['user', 'shift'])
                ->where('date', $selectedDate);

            if ($type === 'early_checkout') {
                $this->detailList = $query->get()->filter(function ($attendance) {
                    if (!$attendance->time_out || !$attendance->shift) return false;
                    return $attendance->time_out->format('H:i:s') < $attendance->shift->end_time;
                });
            } elseif ($type === 'checked_in') {
                $this->detailList = $query->whereIn('status', ['present', 'late'])->get();
            } else {
                // present, late, excused, sick
                $this->detailList = $query->where('status', $type)->get();
            }
        }
    }

    public function closeStatModal()
    {
        $this->showStatModal = false;
        $this->detailList = [];
    }



    public function updatedChartFilter()
    {
        $this->dispatch('chart-updated', $this->calculateChartData());
    }

    public function updatedSelectedDate()
    {
        $this->selectedDate = $this->resolvedSelectedDate()->toDateString();
        $this->resetPage(pageName: 'employeesPage');
        $this->resetPage(pageName: 'notLoggedInPage');
        $this->dispatch('chart-updated', $this->calculateChartData());
    }

    public function updatedSearch()
    {
        $this->resetPage(pageName: 'employeesPage');
    }

    public function resetSelectedDate()
    {
        $this->selectedDate = now()->toDateString();
        $this->resetPage(pageName: 'employeesPage');
        $this->resetPage(pageName: 'notLoggedInPage');
        $this->dispatch('chart-updated', $this->calculateChartData());
    }

    private function calculateChartData()
    {
        $chartLabels = [];
        $chartPresent = [];
        $chartLate = [];
        $chartAbsent = [];
        $selectedDate = $this->resolvedSelectedDate();
        $startDate = $selectedDate->copy()->subDays($this->resolvedChartRangeDays());
        $endDate = $selectedDate->copy();

        $period = CarbonPeriod::create($startDate, $endDate);
        $periodAttendances = Attendance::managedBy(auth()->user())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy(fn (Attendance $attendance) => Carbon::parse($attendance->date)->toDateString());

        foreach ($period as $date) {
            $dateKey = $date->toDateString();
            /** @var Collection<int, Attendance> $dayAttendances */
            $dayAttendances = $periodAttendances->get($dateKey, collect());

            $chartLabels[] = $date->format('d M');
            $chartPresent[] = $dayAttendances->where('status', 'present')->count();
            $chartLate[] = $dayAttendances->where('status', 'late')->count();
            $chartAbsent[] = $dayAttendances
                ->whereIn('status', ['sick', 'excused'])
                ->where('approval_status', 'approved')
                ->count();
        }

        return [
            'labels' => $chartLabels,
            'present' => $chartPresent,
            'late' => $chartLate,
            'other' => $chartAbsent
        ];
    }

    private function resolvedChartRangeDays(): int
    {
        return match ($this->chartFilter) {
            'week_2' => 13,
            'week_3' => 20,
            'month_1', 'month' => 29,
            'month_2' => 59,
            'month_3' => 89,
            default => 6,
        };
    }

    public function render()
    {
        $selectedDate = $this->resolvedSelectedDate();
        $selectedDateString = $selectedDate->toDateString();
        $today = now()->startOfDay();

        // Fetch Pending Counts
        $user = auth()->user();
        
        if ($user->isSuperadmin) {
            $this->pendingLeavesCount = Attendance::where('approval_status', 'pending')->count();
            $this->pendingReimbursementsCount = \App\Models\Reimbursement::where('status', 'pending')->count();
            $this->pendingOvertimesCount = \App\Models\Overtime::where('status', 'pending')->count();
            $this->pendingKasbonCount = \App\Models\CashAdvance::where('status', 'pending')->count();
        } else {
            // Determine subordinates based on role
            // Normal user (Head) -> subordinates attribute
            // Regional Admin -> managedBy scope
            $targetIds = $user->group === 'user' 
                         ? $user->subordinates->pluck('id') 
                         : User::managedBy($user)->pluck('id');

            $this->pendingLeavesCount = Attendance::where('approval_status', 'pending')
                ->whereIn('user_id', $targetIds)
                ->count();

            $this->pendingReimbursementsCount = \App\Models\Reimbursement::where('status', 'pending')
                ->whereIn('user_id', $targetIds)
                ->count();

            $this->pendingOvertimesCount = \App\Models\Overtime::where('status', 'pending')
                ->whereIn('user_id', $targetIds)
                ->count();

            $this->pendingKasbonCount = \App\Models\CashAdvance::where('status', 'pending')
                ->whereIn('user_id', $targetIds)
                ->count();
        }

        // Fetch Overview Counts
        $this->missingFaceDataCount = User::where('group', 'user')
            ->managedBy(auth()->user())
            ->whereDoesntHave('faceDescriptor')
            ->count();

        $this->activeHolidaysCount = \App\Models\Holiday::where('date', $selectedDateString)->count();

        /** @var Collection<Attendance>  */
        $attendances = Attendance::managedBy(auth()->user())
            ->with('shift')->where('date', $selectedDateString)->get();

        /** @var Collection<User>  */
        $employees = User::where('group', 'user')
            ->managedBy(auth()->user())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('nip', 'like', '%' . $this->search . '%');
                });
            })
            ->paginate(10, ['*'], 'employeesPage')
            ->through(function (User $user) use ($attendances) {
                return $user->setAttribute(
                    'attendance',
                    $attendances
                        ->where(fn(Attendance $attendance) => $attendance->user_id === $user->id)
                        ->first(),
                );
            });

        $employeesCount = User::where('group', 'user')->managedBy(auth()->user())->count();

        $presentCount = Attendance::managedBy(auth()->user())->where('date', $selectedDateString)->where('status', 'present')->count();
        $lateCount = Attendance::managedBy(auth()->user())->where('date', $selectedDateString)->where('status', 'late')->count();

        $excusedCount = Attendance::managedBy(auth()->user())->where('date', $selectedDateString)->where('status', 'excused')->where('approval_status', 'approved')->count();
        $sickCount = Attendance::managedBy(auth()->user())->where('date', $selectedDateString)->where('status', 'sick')->where('approval_status', 'approved')->count();

        $absentCount = $employeesCount - ($presentCount + $lateCount + $excusedCount + $sickCount);
        $absentCount = max(0, $absentCount);

        // Early Checkout Calculation
        $earlyCheckoutCount = $attendances->filter(function ($attendance) {
            if (!$attendance->time_out || !$attendance->shift) return false;
            // time_out is Carbon, shift->end_time is String 'H:i:s'
            return $attendance->time_out->format('H:i:s') < $attendance->shift->end_time;
        })->count();

        $managedUsersQuery = User::where('group', 'user')
            ->managedBy(auth()->user());

        $recentUserActivities = ActivityLog::with('user')
            ->whereHas('user', function ($query) {
                $query->where('group', 'user')
                    ->managedBy(auth()->user());
            })
            ->whereNotIn('action', ['Visited Page'])
            ->latest('created_at')
            ->take(6)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'user_name' => $log->user->name ?? __('System'),
                'summary' => $this->humanizeActivitySummary($log->action),
                'detail' => $this->humanizeActivityDetail($log),
                'badge' => $this->humanizeActivityBadge($log->action),
                'badge_class' => $this->humanizeActivityBadgeClass($log->action),
                'created_at' => $log->created_at,
                'ip_address' => $log->ip_address,
            ]);

        $loggedInUserIdsOnSelectedDate = ActivityLog::query()
            ->where('action', 'Login Successful')
            ->whereDate('created_at', $selectedDateString)
            ->whereHas('user', function ($query) {
                $query->where('group', 'user')
                    ->managedBy(auth()->user());
            })
            ->distinct()
            ->pluck('user_id');

        $notLoggedInUsers = (clone $managedUsersQuery)
            ->whereNotIn('id', $loggedInUserIdsOnSelectedDate)
            ->orderBy('name')
            ->paginate(10, ['id', 'name', 'nip'], 'notLoggedInPage');

        $notLoggedInUsersCount = (clone $managedUsersQuery)
            ->whereNotIn('id', $loggedInUserIdsOnSelectedDate)
            ->count();

        $loggedInUsersCount = max(0, $employeesCount - $notLoggedInUsersCount);

        $neverLoggedInCount = (clone $managedUsersQuery)
            ->whereDoesntHave('activityLogs', function ($query) {
                $query->where('action', 'Login Successful');
            })
            ->count();

        $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
        $unreadNotificationsPreview = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->take(5)
            ->get();

        $overdueUsers = Attendance::managedBy(auth()->user())
            ->with(['user', 'shift'])
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->where('date', $selectedDateString)
            ->orderByDesc('date')
            ->take(10)
            ->get()
            ->filter(function ($attendance) use ($selectedDate, $today) {
                if (! $attendance->shift) {
                    return false;
                }

                if ($selectedDate->lt($today)) {
                    return true;
                }

                if ($selectedDate->isSameDay($today)) {
                    return now()->format('H:i:s') > $attendance->shift->end_time;
                }

                return false;
            });

        $rawLeaves = Attendance::managedBy(auth()->user())
            ->with('user')
            ->whereMonth('date', $selectedDate->month)
            ->whereYear('date', $selectedDate->year)
            ->whereIn('status', ['sick', 'excused'])
            ->where('approval_status', 'approved')
            ->orderBy('user_id')
            ->orderBy('date')
            ->get();

        $calendarLeaves = collect();
        if ($rawLeaves->isNotEmpty()) {
            $grouped = $rawLeaves->groupBy(function ($item) {
                return $item->user_id . '-' . $item->status;
            });

            foreach ($grouped as $group) {
                // Determine consecutive dates
                $tempGroup = [];
                foreach ($group as $leave) {
                    if (empty($tempGroup)) {
                        $tempGroup[] = $leave;
                        continue;
                    }

                    $last = end($tempGroup);
                    // Check if consecutive (1 day difference)
                    if ($last->date->diffInDays($leave->date) == 1) {
                        $tempGroup[] = $leave;
                    } else {
                        // Push previous group
                        $calendarLeaves->push($this->formatLeaveGroup($tempGroup));
                        $tempGroup = [$leave];
                    }
                }
                // Push last group
                if (!empty($tempGroup)) {
                    $calendarLeaves->push($this->formatLeaveGroup($tempGroup));
                }
            }
        }

        return view('livewire.admin.dashboard', [
            'employees' => $employees,
            'employeesCount' => $employeesCount,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'earlyCheckoutCount' => $earlyCheckoutCount,
            'excusedCount' => $excusedCount,
            'sickCount' => $sickCount,
            'absentCount' => $absentCount,
            'recentUserActivities' => $recentUserActivities,
            'notLoggedInUsers' => $notLoggedInUsers,
            'notLoggedInUsersCount' => $notLoggedInUsersCount,
            'loggedInUsersCount' => $loggedInUsersCount,
            'neverLoggedInCount' => $neverLoggedInCount,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'unreadNotificationsPreview' => $unreadNotificationsPreview,
            'chartData' => $this->calculateChartData(),
            'overdueUsers' => $overdueUsers,
            'calendarLeaves' => $calendarLeaves,
            'pendingOvertimesCount' => $this->pendingOvertimesCount,
            'pendingKasbonCount' => $this->pendingKasbonCount,
            'missingFaceDataCount' => $this->missingFaceDataCount,
            'activeHolidaysCount' => $this->activeHolidaysCount,
        ]);
    }

    public function notifyUser($attendanceId)
    {
        $attendance = Attendance::find($attendanceId);
        if ($attendance && $attendance->user && $attendance->user->email) {
            \Illuminate\Support\Facades\Mail::to($attendance->user->email)->send(new \App\Mail\CheckoutReminderMail($attendance->user));

            // Log it
            \App\Models\ActivityLog::record('Notification Sent', 'Sent checkout reminder to ' . $attendance->user->name);
        }
    }

    private function formatLeaveGroup($leaves)
    {
        $first = $leaves[0];
        $last = end($leaves);
        $count = count($leaves);

        $dateDisplay = $first->date->format('d M');
        if ($count > 1) {
            if ($first->date->format('M') == $last->date->format('M')) {
                $dateDisplay .= ' - ' . $last->date->format('d M Y');
            } else {
                $dateDisplay .= ' - ' . $last->date->format('d M Y');
            }
            $dateDisplay .= ' (' . $count . ' days)';
        } else {
            $dateDisplay = $first->date->format('d M Y');
        }

        return [
            'title' => $first->user->name,
            'date_display' => $dateDisplay,
            'start_date' => $first->date,
            'status' => $first->status
        ];
    }

    private function humanizeActivitySummary(string $action): string
    {
        return match ($action) {
            'Login Successful' => __('Logged in successfully'),
            'Check In' => __('Checked in'),
            'Check Out' => __('Checked out'),
            'Leave Request' => __('Submitted a leave request'),
            'Notification Sent' => __('Received a reminder'),
            'Form Submission' => __('Submitted a form'),
            'Updated Data' => __('Updated data'),
            'Deleted Data' => __('Deleted data'),
            default => __($action),
        };
    }

    private function humanizeActivityBadge(string $action): string
    {
        return match ($action) {
            'Login Successful' => __('Login'),
            'Check In', 'Check Out' => __('Attendance'),
            'Leave Request' => __('Leave'),
            'Notification Sent' => __('Reminder'),
            'Form Submission', 'Updated Data' => __('Update'),
            'Deleted Data' => __('Delete'),
            default => __('Activity'),
        };
    }

    private function humanizeActivityBadgeClass(string $action): string
    {
        return match ($action) {
            'Login Successful' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
            'Check In', 'Check Out' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
            'Leave Request' => 'bg-violet-50 text-violet-700 dark:bg-violet-900/20 dark:text-violet-300',
            'Notification Sent' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
            'Deleted Data' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    private function humanizeActivityDetail(ActivityLog $log): ?string
    {
        $description = trim((string) $log->description);

        return match ($log->action) {
            'Login Successful' => $log->ip_address
                ? __('Signed in from :ip', ['ip' => $log->ip_address])
                : __('Login recorded'),
            'Check In' => $this->humanizeCheckInDetail($description),
            'Check Out' => __('Attendance checkout recorded'),
            'Leave Request' => $this->normalizeActivityDescription($description),
            'Notification Sent' => $this->normalizeActivityDescription($description),
            'Form Submission', 'Updated Data', 'Deleted Data' => $this->humanizePathDescription($description),
            default => $this->normalizeActivityDescription($description),
        };
    }

    private function humanizeCheckInDetail(string $description): string
    {
        if (Str::contains($description, 'via barcode:')) {
            return __('Via barcode :name', ['name' => trim(Str::after($description, 'via barcode:'))]);
        }

        return __('Attendance check-in recorded');
    }

    private function humanizePathDescription(string $description): ?string
    {
        if (preg_match('/^[A-Z]+ \/(.+)$/', $description, $matches) === 1) {
            $path = trim($matches[1], '/');

            if ($path === '') {
                return null;
            }

            return Str::headline(str_replace('/', ' ', $path));
        }

        return $this->normalizeActivityDescription($description);
    }

    private function normalizeActivityDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        $description = preg_replace('/^User\s+/i', '', trim($description));
        $description = preg_replace('/\s+/', ' ', $description ?? '');

        return $description !== '' ? Str::ucfirst($description) : null;
    }

    private function resolvedSelectedDate(): Carbon
    {
        try {
            return Carbon::parse($this->selectedDate ?: now()->toDateString())->startOfDay();
        } catch (\Throwable $e) {
            return now()->startOfDay();
        }
    }
}
