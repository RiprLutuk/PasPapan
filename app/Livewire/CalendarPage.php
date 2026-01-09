<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Holiday;

class CalendarPage extends Component
{
    public $currentMonth;
    public $currentYear;

    public function mount()
    {
        $now = Carbon::now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function prevMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function getDaysProperty()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $startOfCalendar = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar = $date->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $days = [];
        $curr = $startOfCalendar->copy();

        // Fetch holidays for the range
        // We can use a simple query logic here or use the model helper if adapted
        // For efficiency, let's just get all holidays in this date range
        // Since Holiday model has `is_recurring`, we might need to rely on the helper per day 
        // OR fetch all simple holidays and filter recurring in memory.
        // Given typically low N, per-day check in loop might be acceptable for < 42 days, 
        // but eager loading is better. Let's rely on valid PHP logic for recurring.
        
        while ($curr <= $endOfCalendar) {
            $isToday = $curr->isToday();
            $isCurrentMonth = $curr->month === $this->currentMonth;
            
            // Check holiday
            $holiday = Holiday::getHolidayFor($curr);

            $days[] = [
                'date' => $curr->copy(),
                'day' => $curr->day,
                'isToday' => $isToday,
                'isCurrentMonth' => $isCurrentMonth,
                'holiday' => $holiday,
                'isWeekend' => $curr->isWeekend(),
            ];

            $curr->addDay();
        }

        return $days;
    }

    public function render()
    {
        return view('livewire.calendar-page', [
            'days' => $this->days,
            'currentDate' => Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)
        ])->layout('layouts.app');
    }
}
