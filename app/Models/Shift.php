<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory, HasTimestamps;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    public function getFormattedStartTimeAttribute(): ?string
    {
        return $this->formatTimeValue($this->start_time);
    }

    public function getFormattedEndTimeAttribute(): ?string
    {
        return $this->formatTimeValue($this->end_time);
    }

    public function getIsOvernightAttribute(): bool
    {
        if (!$this->start_time || !$this->end_time) {
            return false;
        }

        return $this->parseTimeValue($this->end_time)->lessThanOrEqualTo($this->parseTimeValue($this->start_time));
    }

    public function getShiftTypeAttribute(): string
    {
        if (!$this->end_time) {
            return 'open-ended';
        }

        return $this->is_overnight ? 'overnight' : 'daytime';
    }

    public function getDurationInMinutesAttribute(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $start = $this->parseTimeValue($this->start_time);
        $end = $this->parseTimeValue($this->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return $start->diffInMinutes($end);
    }

    public function getDurationLabelAttribute(): ?string
    {
        if ($this->duration_in_minutes === null) {
            return null;
        }

        $hours = intdiv($this->duration_in_minutes, 60);
        $minutes = $this->duration_in_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        if ($hours > 0) {
            return sprintf('%dh', $hours);
        }

        return sprintf('%dm', $minutes);
    }

    protected function formatTimeValue(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        return \App\Helpers::format_time($time);
    }

    protected function parseTimeValue(string $time): Carbon
    {
        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $time);
            } catch (\Throwable $exception) {
                continue;
            }
        }

        return Carbon::parse($time);
    }
}
