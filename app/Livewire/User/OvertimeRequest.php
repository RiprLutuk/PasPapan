<?php

namespace App\Livewire\User;

use App\Models\Overtime;
use App\Support\OvertimeCalculator;
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

    protected OvertimeCalculator $overtimeCalculator;

    protected $rules = [
        'date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i', // Removed after:start_time to allow crossing midnight
        'reason' => 'required|string|min:5',
    ];

    public function boot(OvertimeCalculator $overtimeCalculator): void
    {
        $this->overtimeCalculator = $overtimeCalculator;
    }

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

        [$start, $end] = $this->overtimeCalculator->resolveWindow($this->date, $this->start_time, $this->end_time);
        $duration = $this->overtimeCalculator->durationInMinutes($start, $end);

        if ($duration <= 0) {
            $this->addError('end_time', __('Overtime duration must be greater than zero.'));
            return;
        }

        $existingOvertimes = Overtime::query()
            ->where('user_id', Auth::id())
            ->whereDate('date', $this->date)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        $hasOverlap = $this->overtimeCalculator->hasOverlap($existingOvertimes, $start, $end);

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
