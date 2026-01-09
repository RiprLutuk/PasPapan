<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequested extends Notification
{
    use Queueable;

    public $attendance;
    public $fromDate;
    public $toDate;
    public $totalDays;

    public function __construct($attendance, $fromDate = null, $toDate = null)
    {
        $this->attendance = $attendance;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        
        // Calculate total days
        if ($fromDate && $toDate) {
            $this->totalDays = $fromDate->diffInDays($toDate) + 1;
        } else {
            $this->totalDays = 1;
        }
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        // Add mail if admin email is configured
        $adminEmail = \App\Models\Setting::getValue('notif.admin_email');
        if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->attendance->user->name ?? 'Unknown';
        $leaveType = $this->attendance->status === 'sick' ? 'Sakit' : 'Izin';
        
        // Get app name and support contact from settings
        $appName = \App\Models\Setting::getValue('app.name', config('app.name', 'PasPapan'));
        $supportEmail = \App\Models\Setting::getValue('app.support_contact', config('mail.from.address'));
        
        // Format date range
        if ($this->fromDate && $this->toDate && $this->totalDays > 1) {
            $dateDisplay = $this->fromDate->format('d M Y') . ' s/d ' . $this->toDate->format('d M Y');
            $daysInfo = "({$this->totalDays} hari)";
        } else {
            $dateDisplay = $this->attendance->date?->format('d M Y') ?? 'Unknown';
            $daysInfo = "(1 hari)";
        }
        
        $mail = (new MailMessage)
            ->subject("[$appName] Pengajuan $leaveType Baru dari $userName")
            ->greeting("Halo Admin!")
            ->line("Ada pengajuan $leaveType baru yang perlu diproses:")
            ->line("**Karyawan:** $userName")
            ->line("**Jenis:** $leaveType")
            ->line("**Tanggal:** $dateDisplay $daysInfo")
            ->line("**Keterangan:** " . ($this->attendance->note ?? '-'))
            ->action('Lihat Pengajuan', route('admin.leaves'))
            ->line('Silakan login untuk menyetujui atau menolak pengajuan ini.');
        
        // Add reply-to if support contact is set and is a valid email
        if (!empty($supportEmail) && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($supportEmail, $appName . ' Support');
        }
        
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        // Format date range for database notification
        if ($this->fromDate && $this->toDate && $this->totalDays > 1) {
            $dateDisplay = $this->fromDate->format('d M') . ' - ' . $this->toDate->format('d M Y');
            $message = "Pengajuan {$this->attendance->status} dari {$this->attendance->user->name} ({$this->totalDays} hari)";
        } else {
            $dateDisplay = $this->attendance->date->format('Y-m-d');
            $message = "Pengajuan {$this->attendance->status} dari {$this->attendance->user->name}";
        }
        
        return [
            'type' => 'leave_request',
            'user_id' => $this->attendance->user_id,
            'user_name' => $this->attendance->user->name,
            'leave_type' => $this->attendance->status,
            'date' => $dateDisplay,
            'total_days' => $this->totalDays,
            'message' => $message,
            'url' => route('admin.leaves'),
        ];
    }
}
