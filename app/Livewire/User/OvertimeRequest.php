<?php

namespace App\Livewire\User;

use App\Models\Overtime;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class OvertimeRequest extends Component
{
    use WithPagination;

    public $date;
    public $start_time;
    public $end_time;
    public $reason;
    public $showModal = false;

    protected $rules = [
        'date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i', // Removed after:start_time to allow crossing midnight
        'reason' => 'required|string|min:5',
    ];

    public function render()
    {
        $overtimes = Overtime::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        return view('livewire.user.overtime-request', [
            'overtimes' => $overtimes
        ])->layout('layouts.app');
    }

    public function create()
    {
        $this->reset(['date', 'start_time', 'end_time', 'reason']);
        $this->showModal = true;
    }

    public function store()
    {
        $this->validate();

        // Calculate duration in minutes
        $start = \Carbon\Carbon::parse($this->date . ' ' . $this->start_time);
        $end = \Carbon\Carbon::parse($this->date . ' ' . $this->end_time);

        // Handle cross-day overtime (e.g. 23:00 to 02:00)
        if ($end->lt($start)) {
            $end->addDay();
        }

        $duration = $start->diffInMinutes($end);

        if ($duration <= 0) {
            $this->addError('end_time', __('Overtime duration must be greater than zero.'));
            return;
        }

        $hasOverlap = Overtime::query()
            ->where('user_id', Auth::id())
            ->whereDate('date', $this->date)
            ->whereIn('status', ['pending', 'approved'])
            ->get()
            ->contains(function (Overtime $overtime) use ($start, $end) {
                $existingStart = \Carbon\Carbon::parse($overtime->date->format('Y-m-d') . ' ' . $overtime->start_time->format('H:i:s'));
                $existingEnd = \Carbon\Carbon::parse($overtime->date->format('Y-m-d') . ' ' . $overtime->end_time->format('H:i:s'));

                if ($existingEnd->lte($existingStart)) {
                    $existingEnd->addDay();
                }

                return $start->lt($existingEnd) && $end->gt($existingStart);
            });

        if ($hasOverlap) {
            $this->addError('start_time', __('This overtime request overlaps with an existing pending or approved request.'));
            return;
        }

        $overtime = Overtime::create([
            'user_id' => Auth::id(),
            'date' => $this->date,
            'start_time' => $start,
            'end_time' => $end,
            'duration' => $duration,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        // Verify Notification class exists before sending (safety)
        if (class_exists(\App\Notifications\OvertimeRequested::class)) {
            // 1. Notify Supervisor AND Admins (Broad Visibility)
            $supervisor = Auth::user()->supervisor;
            $admins = \App\Models\User::whereIn('group', ['admin', 'superadmin'])->get();
            
            // Merge supervisor into admins collection to ensure unique recipients
            $notifiable = $admins;
            if ($supervisor) {
                $notifiable = $notifiable->push($supervisor)->unique('id');
            }
            
            \Illuminate\Support\Facades\Log::info('Notifiable count: ' . $notifiable->count());

            if ($notifiable->count() > 0) {
                 // Bell Notification (Sync - Instant)
                 \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\OvertimeRequested($overtime));
                 \Illuminate\Support\Facades\Log::info('Notification sent to DB/Bell (Sync).');

                 // Email Notification (Queued)
                 \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\OvertimeRequestedEmail($overtime));
                 \Illuminate\Support\Facades\Log::info('Notification sent to Mail (Queued).');
                 
                 // Force UI Refresh
                 $this->dispatch('refresh-notifications');
            } else {
                 \Illuminate\Support\Facades\Log::warning('No admins or supervisor found to notify.');
            }

            // 2. Send to Configured Admin Email (Mail Channel Explicit)
            $adminEmail = \App\Models\Setting::getValue('notif.admin_email');
            if (!empty($adminEmail)) {
                try {
                    \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
                        ->notify(new \App\Notifications\OvertimeRequestedEmail($overtime));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send overtime email: ' . $e->getMessage());
                }
            }
        }

        $this->showModal = false;
        $this->reset(['date', 'start_time', 'end_time', 'reason']);
        session()->flash('success', 'Overtime request submitted successfully.');
    }

    public function close()
    {
        $this->showModal = false;
    }
}
