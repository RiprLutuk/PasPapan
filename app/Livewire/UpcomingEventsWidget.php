<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UpcomingEventsWidget extends Component
{
    public function render()
    {
        // 1. Active Announcements (Priority > Normal)
        // 1. Upcoming Holidays (Next 14 days)
        $today = Carbon::today();
        $twoWeeksLater = $today->copy()->addDays(14);
        
        $holidays = Holiday::whereBetween('date', [$today->format('Y-m-d'), $twoWeeksLater->format('Y-m-d')])
            ->orderBy('date', 'asc')
            ->get();

        // 2. Upcoming Birthdays (Next 7 days)
        // Logic handles separate month/year issues simply by checking month/day
        $nextWeek = $today->copy()->addDays(7);
        
        $birthdays = User::get()
            ->filter(function ($user) use ($today, $nextWeek) {
                if (!$user->birth_date) return false;
                
                $birthday = Carbon::parse($user->birth_date)->year($today->year);
                if ($birthday->isPast() && !$birthday->isToday()) {
                    $birthday->addYear();
                }
                
                return $birthday->between($today, $nextWeek);
            })
            ->sortBy(function ($user) use ($today) {
                $birthday = Carbon::parse($user->birth_date)->year($today->year);
                if ($birthday->isPast() && !$birthday->isToday()) {
                    $birthday->addYear();
                }
                return $birthday->timestamp;
            })
            ->take(5);

        // Determine active tab or state
        $hasEvents = $holidays->isNotEmpty() || $birthdays->isNotEmpty();

        return view('livewire.upcoming-events-widget', [
            'holidays' => $holidays,
            'birthdays' => $birthdays,
            'hasEvents' => $hasEvents,
        ]);
    }
}
