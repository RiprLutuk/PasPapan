<?php

namespace App\Support;

use App\Models\Overtime;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OvertimeCalculator
{
    public function resolveWindow(string $date, string $startTime, string $endTime): array
    {
        $start = Carbon::parse("{$date} {$startTime}");
        $end = Carbon::parse("{$date} {$endTime}");

        if ($end->lt($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    public function durationInMinutes(CarbonInterface $start, CarbonInterface $end): int
    {
        return $start->diffInMinutes($end);
    }

    public function windowsOverlap(
        CarbonInterface $start,
        CarbonInterface $end,
        CarbonInterface $existingStart,
        CarbonInterface $existingEnd,
    ): bool {
        return $start->lt($existingEnd) && $end->gt($existingStart);
    }

    /**
     * @param Collection<int, Overtime> $overtimes
     */
    public function hasOverlap(Collection $overtimes, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $overtimes->contains(function (Overtime $overtime) use ($start, $end) {
            $existingStart = Carbon::parse($overtime->date->format('Y-m-d') . ' ' . $overtime->start_time->format('H:i:s'));
            $existingEnd = Carbon::parse($overtime->date->format('Y-m-d') . ' ' . $overtime->end_time->format('H:i:s'));

            if ($existingEnd->lte($existingStart)) {
                $existingEnd->addDay();
            }

            return $this->windowsOverlap($start, $end, $existingStart, $existingEnd);
        });
    }
}
