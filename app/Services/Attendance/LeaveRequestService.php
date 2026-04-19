<?php

namespace App\Services\Attendance;

use App\Contracts\AttendanceServiceInterface;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveRequestedEmail;
use App\Support\LeaveCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class LeaveRequestService
{
    public function __construct(
        protected AttendanceServiceInterface $attendanceService,
        protected LeaveCalculator $leaveCalculator,
    ) {
    }

    public function getApplyLeaveData(User $user): array
    {
        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $annualQuota = (int) Setting::getValue('leave.annual_quota', 12);
        $requireAttachment = Setting::getValue('leave.require_attachment', '1') === '1';
        $usedExcused = $this->countAnnualLeaveDays($user, now()->year);

        return [
            'attendance' => $attendance,
            'annualQuota' => $annualQuota,
            'usedExcused' => $usedExcused,
            'remainingExcused' => $this->leaveCalculator->remainingAnnualQuota($annualQuota, $usedExcused),
            'requireAttachment' => $requireAttachment,
        ];
    }

    public function submitLeaveRequest(
        User $user,
        string $status,
        string $note,
        Carbon $fromDate,
        Carbon $toDate,
        ?UploadedFile $attachment = null,
        ?float $lat = null,
        ?float $lng = null,
    ): LeaveRequestResult {
        $requestedDays = $fromDate->diffInDays($toDate) + 1;
        $annualQuota = (int) Setting::getValue('leave.annual_quota', 12);
        $usedExcused = $this->countAnnualLeaveDays($user, $fromDate->year);

        if ($this->leaveCalculator->wouldExceedAnnualQuota($status, $annualQuota, $usedExcused, $requestedDays)) {
            return LeaveRequestResult::error(__('Not enough remaining annual leave quota for this request.'));
        }

        $existingClockRecords = $this->existingClockRecords($user, $fromDate, $toDate);
        if ($existingClockRecords->isNotEmpty()) {
            $blockedDates = $this->formatDates($existingClockRecords);

            return LeaveRequestResult::error(
                "Tidak dapat mengajukan izin. Anda sudah melakukan absensi (clock in/out) pada tanggal: {$blockedDates}"
            );
        }

        $existingLeaveRequests = $this->existingLeaveRequests($user, $fromDate, $toDate);
        if ($existingLeaveRequests->isNotEmpty()) {
            $blockedDates = $this->formatDates($existingLeaveRequests);

            return LeaveRequestResult::error(
                "Tidak dapat mengajukan izin. Anda sudah memiliki pengajuan izin (Pending/Disetujui) pada tanggal: {$blockedDates}"
            );
        }

        $storedAttachment = $attachment ? $this->attendanceService->storeAttachment($attachment) : null;

        $fromDate->copy()->range($toDate)->forEach(function (Carbon $date) use ($user, $status, $note, $storedAttachment, $lat, $lng) {
            $existing = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $date->toDateString())
                ->first();

            $payload = [
                'status' => $status,
                'note' => $note,
                'attachment' => $storedAttachment ?? $existing?->attachment,
                'latitude_in' => $lat ?? $existing?->latitude_in,
                'longitude_in' => $lng ?? $existing?->longitude_in,
                'approval_status' => Attendance::STATUS_PENDING,
            ];

            if ($existing) {
                if (is_null($existing->time_in) && is_null($existing->time_out)) {
                    $existing->update($payload);
                }

                return;
            }

            Attendance::create($payload + [
                'user_id' => $user->id,
                'date' => $date->toDateString(),
            ]);
        });

        Attendance::clearUserAttendanceCache($user, $fromDate);
        if (! $fromDate->isSameMonth($toDate)) {
            Attendance::clearUserAttendanceCache($user, $toDate);
        }

        ActivityLog::record('Leave Request', "User submitted {$status} request from {$fromDate->format('Y-m-d')} to {$toDate->format('Y-m-d')}");

        $latestAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->latest('date')
            ->latest('created_at')
            ->first();

        if ($latestAttendance) {
            $this->notifyLeaveRequest($user, $latestAttendance, $fromDate, $toDate);
        }

        return LeaveRequestResult::success();
    }

    protected function countAnnualLeaveDays(User $user, int $year): int
    {
        return Attendance::query()
            ->where('user_id', $user->id)
            ->whereYear('date', $year)
            ->where('status', 'excused')
            ->whereIn('approval_status', [Attendance::STATUS_PENDING, Attendance::STATUS_APPROVED])
            ->count();
    }

    protected function existingClockRecords(User $user, Carbon $fromDate, Carbon $toDate): Collection
    {
        return Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where(function ($query) {
                $query->whereNotNull('time_in')
                    ->orWhereNotNull('time_out');
            })
            ->get();
    }

    protected function existingLeaveRequests(User $user, Carbon $fromDate, Carbon $toDate): Collection
    {
        return Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->whereIn('approval_status', [Attendance::STATUS_PENDING, Attendance::STATUS_APPROVED])
            ->get();
    }

    protected function formatDates(Collection $records): string
    {
        return $records->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('d M Y'))
            ->join(', ');
    }

    protected function notifyLeaveRequest(User $user, Attendance $attendance, Carbon $fromDate, Carbon $toDate): void
    {
        $supervisor = $user->supervisor;
        $admins = User::query()->whereIn('group', ['admin', 'superadmin'])->get();

        $notifiable = $supervisor
            ? $admins->push($supervisor)->unique('id')
            : $admins;

        if ($notifiable->isNotEmpty()) {
            Notification::send($notifiable, new LeaveRequested($attendance, $fromDate, $toDate));
            Notification::send($notifiable, new LeaveRequestedEmail($attendance, $fromDate, $toDate));
        }

        $adminEmail = Setting::getValue('notif.admin_email');
        if (! empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $adminEmail)
                    ->notify(new LeaveRequestedEmail($attendance, $fromDate, $toDate));
            } catch (\Throwable $e) {
                Log::warning('Failed to send admin email notification: ' . $e->getMessage());
            }
        }
    }
}
