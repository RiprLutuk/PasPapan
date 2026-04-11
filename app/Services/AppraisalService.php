<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\KpiTemplate;
use App\Models\AppraisalEvaluation;
use App\Models\User;

class AppraisalService
{
    /**
     * Calculate attendance score for a user in a given period.
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
     * Initialize Draft Appraisal for Employee's self assessment
     */
    public function initAppraisal(User $user, int $month, int $year): Appraisal
    {
        $appraisal = Appraisal::firstOrCreate([
            'user_id' => $user->id,
            'period_month' => $month,
            'period_year' => $year,
        ], [
            'status' => 'self_assessment',
            'attendance_score' => $this->calculateAttendanceScore($user, $month, $year),
        ]);

        $activeKpis = KpiTemplate::where('is_active', true)->get();
        foreach ($activeKpis as $kpi) {
            AppraisalEvaluation::firstOrCreate([
                'appraisal_id' => $appraisal->id,
                'kpi_template_id' => $kpi->id,
            ]);
        }

        return $appraisal;
    }

    /**
     * Finalize Appraisal after Manager Reviews and 1 on 1
     */
    public function finalizeAppraisal(Appraisal $appraisal, array $managerScores, array $evalComments, string $notes, string $status, ?string $meetingDate = null, ?string $meetingLink = null): Appraisal
    {
        $totalWeight = 0;
        $weightedKpiScore = 0;

        foreach ($appraisal->evaluations as $eval) {
            $score = $managerScores[$eval->id] ?? 0;
            $comment = $evalComments[$eval->id] ?? null;

            $eval->update([
                'manager_score' => $score,
                'comments' => $comment
            ]);

            $weight = $eval->kpiTemplate->weight;
            $totalWeight += $weight;
            $weightedKpiScore += ($score * ($weight / 100));
        }

        // Final score logic: Configured Attendance Weight + KPI Weight (remaining)
        $attendanceWeightPercent = (float) \App\Models\Setting::getValue('appraisal.attendance_weight', 30);
        $attendanceWeight = $attendanceWeightPercent / 100;
        $kpiWeight = 1.0 - $attendanceWeight;
        
        // If 0 KPIs, default back to attendance alone.
        if ($totalWeight == 0) {
            $finalScore = $appraisal->attendance_score;
        } else {
            // Normalize KPI score if total weight != 100% just in case
            $normalizedKpi = ($weightedKpiScore / ($totalWeight / 100));
            $finalScore = ($appraisal->attendance_score * $attendanceWeight) + ($normalizedKpi * $kpiWeight);
        }

        $appraisal->update([
            'evaluator_id' => auth()->id(),
            'status' => $status,
            'subjective_score' => $normalizedKpi ?? 0,
            'final_score' => round($finalScore, 2),
            'notes' => $notes,
            'meeting_date' => $meetingDate,
            'meeting_link' => $meetingLink,
        ]);

        return $appraisal;
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
