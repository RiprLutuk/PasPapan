<?php

namespace App\Support;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class UserOvertimeService
{
    public function __construct(
        protected OvertimeCalculator $overtimeCalculator,
        protected UserNotificationRecipientService $notificationRecipients,
    ) {}

    public function paginateForUser(string|int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Overtime::query()
            ->where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, overtime?: Overtime, field?: string, message?: string}
     */
    public function submit(User $user, array $data): array
    {
        [$start, $end] = $this->overtimeCalculator->resolveWindow($data['date'], $data['start_time'], $data['end_time']);
        $duration = $this->overtimeCalculator->durationInMinutes($start, $end);

        if ($duration <= 0) {
            return [
                'ok' => false,
                'field' => 'end_time',
                'message' => __('Overtime duration must be greater than zero.'),
            ];
        }

        $existingOvertimes = Overtime::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $data['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        if ($this->overtimeCalculator->hasOverlap($existingOvertimes, $start, $end)) {
            return [
                'ok' => false,
                'field' => 'start_time',
                'message' => __('This overtime request overlaps with an existing pending or approved request.'),
            ];
        }

        $overtime = Overtime::create([
            'user_id' => $user->id,
            'date' => $data['date'],
            'start_time' => $start,
            'end_time' => $end,
            'duration' => $duration,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        $overtime->loadMissing('user.jobTitle.jobLevel', 'user.division');

        if (class_exists(\App\Notifications\OvertimeRequested::class)) {
            $recipientCount = $this->notificationRecipients->notifyOvertimeRequested($overtime);

            if ($recipientCount > 0) {
                Log::info('Overtime request notifications sent.', [
                    'overtime_id' => $overtime->id,
                    'recipient_count' => $recipientCount,
                ]);
            } else {
                Log::warning('No overtime approver recipients found.', [
                    'overtime_id' => $overtime->id,
                    'user_id' => $user->id,
                ]);
            }
        }

        return [
            'ok' => true,
            'overtime' => $overtime,
        ];
    }
}
