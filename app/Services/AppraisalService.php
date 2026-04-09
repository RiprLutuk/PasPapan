<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\User;

class AppraisalService
{
    /**
     * Calculate attendance score for a user in a given period.
     * Algorithm: 100 base score, -5 per late, -10 per absent, -2 per excused/sick.
     */
    public function calculateAttendanceScore(User $user, int $month, int $year): int
    {
        $lates = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('status', 'late')
            ->count();

        $absents = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereIn('status', ['absent', 'alpha'])
            ->count();

        $excused = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereIn('status', ['excused', 'sick'])
            ->count();

        $score = 100 - ($lates * 5) - ($absents * 10) - ($excused * 2);
        return max(0, min(100, $score));
    }

    /**
     * Save or update an appraisal record.
     */
    public function saveAppraisal(User $user, int $month, int $year, int $attendanceScore, float $subjectiveScore, ?string $notes = null): Appraisal
    {
        $finalScore = ($attendanceScore * 0.4) + ($subjectiveScore * 0.6);

        return Appraisal::updateOrCreate([
            'user_id' => $user->id,
            'period_month' => $month,
            'period_year' => $year,
        ], [
            'evaluator_id' => auth()->id(),
            'attendance_score' => $attendanceScore,
            'subjective_score' => $subjectiveScore,
            'final_score' => round($finalScore, 2),
            'notes' => $notes,
        ]);
    }

    /**
     * Get appraisals keyed by user_id for a set of users.
     */
    public function getAppraisalsForUsers(array $userIds, int $month, int $year)
    {
        return Appraisal::whereIn('user_id', $userIds)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->get()
            ->keyBy('user_id');
    }
}
