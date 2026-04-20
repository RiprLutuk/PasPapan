<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminDashboardPresenter
{
    /**
     * @param  array<string, mixed>  $dashboard
     * @param  array<string, array<int, int|string>>  $chartData
     * @return array<string, mixed>
     */
    public function buildViewData(array $dashboard, Carbon $selectedDate, array $chartData): array
    {
        $attendances = $dashboard['attendances'];
        $employeesCount = $dashboard['employeesCount'];
        $presentCount = $dashboard['presentCount'];
        $lateCount = $dashboard['lateCount'];
        $excusedCount = $dashboard['excusedCount'];
        $sickCount = $dashboard['sickCount'];

        $absentCount = $employeesCount - ($presentCount + $lateCount + $excusedCount + $sickCount);
        $absentCount = max(0, $absentCount);

        $earlyCheckoutCount = $attendances->filter(function (Attendance $attendance) {
            if (! $attendance->time_out || ! $attendance->shift) {
                return false;
            }

            return $attendance->time_out->format('H:i:s') < $attendance->shift->end_time;
        })->count();

        return [
            'employees' => $dashboard['employees'],
            'employeesCount' => $employeesCount,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'earlyCheckoutCount' => $earlyCheckoutCount,
            'excusedCount' => $excusedCount,
            'sickCount' => $sickCount,
            'absentCount' => $absentCount,
            'recentUserActivities' => $this->formatRecentActivities(collect($dashboard['recentUserActivities'])),
            'notLoggedInUsers' => $dashboard['notLoggedInUsers'],
            'notLoggedInUsersCount' => $dashboard['notLoggedInUsersCount'],
            'loggedInUsersCount' => $dashboard['loggedInUsersCount'],
            'neverLoggedInCount' => $dashboard['neverLoggedInCount'],
            'unreadNotificationsCount' => $dashboard['unreadNotificationsCount'],
            'unreadNotificationsPreview' => $dashboard['unreadNotificationsPreview'],
            'chartData' => $chartData,
            'overdueUsers' => $dashboard['overdueUsers'],
            'calendarLeaves' => $dashboard['calendarLeaves'],
            'pendingLeavesCount' => $dashboard['pendingLeavesCount'],
            'pendingReimbursementsCount' => $dashboard['pendingReimbursementsCount'],
            'pendingOvertimesCount' => $dashboard['pendingOvertimesCount'],
            'pendingKasbonCount' => $dashboard['pendingKasbonCount'],
            'missingFaceDataCount' => $dashboard['missingFaceDataCount'],
            'activeHolidaysCount' => $dashboard['activeHolidaysCount'],
            'selectedDate' => $selectedDate,
        ];
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @return Collection<int, array<string, mixed>>
     */
    public function formatRecentActivities(Collection $logs): Collection
    {
        return $logs->map(fn (ActivityLog $log) => [
            'user_name' => $log->user->name ?? __('System'),
            'summary' => $this->humanizeActivitySummary($log->action),
            'detail' => $this->humanizeActivityDetail($log),
            'badge' => $this->humanizeActivityBadge($log->action),
            'badge_class' => $this->humanizeActivityBadgeClass($log->action),
            'created_at' => $log->created_at,
            'ip_address' => $log->ip_address,
        ]);
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
}
