<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function scan()
    {
        return view('attendances.scan');
    }

    public function applyLeave()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', date('Y-m-d'))
            ->first();

        // Only annual leave uses quota.
        $annualQuota = (int) \App\Models\Setting::getValue('leave.annual_quota', 12);
        $requireAttachment = \App\Models\Setting::getValue('leave.require_attachment', '1') === '1';

        // Calculate used annual leave this year.
        $currentYear = now()->year;
        $usedExcused = Attendance::where('user_id', $user->id)
            ->whereYear('date', $currentYear)
            ->where('status', 'excused')
            ->whereIn('approval_status', ['approved', 'pending'])
            ->count();

        return view('attendances.apply-leave', [
            'attendance' => $attendance,
            'annualQuota' => $annualQuota,
            'usedExcused' => $usedExcused,
            'remainingExcused' => max(0, $annualQuota - $usedExcused),
            'requireAttachment' => $requireAttachment,
        ]);
    }

    public function storeLeaveRequest(Request $request)
    {
        // Check if attachment is required from settings
        $requireAttachment = \App\Models\Setting::getValue('leave.require_attachment', '1') === '1';
        
        $request->validate([
            'status' => ['required', 'in:excused,sick'],
            'note' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'attachment' => [$requireAttachment ? 'required' : 'nullable', 'file', 'max:3072'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        try {
            $fromDate = Carbon::parse($request->from);
            $toDate = Carbon::parse($request->to ?? $fromDate);
            $requestedDays = $fromDate->diffInDays($toDate) + 1;

            $currentYear = $fromDate->year;
            $annualQuota = (int) \App\Models\Setting::getValue('leave.annual_quota', 12);
            $usedExcused = Attendance::where('user_id', Auth::id())
                ->whereYear('date', $currentYear)
                ->where('status', 'excused')
                ->whereIn('approval_status', [Attendance::STATUS_PENDING, Attendance::STATUS_APPROVED])
                ->count();

            if (
                $request->status === 'excused'
                && ($usedExcused + $requestedDays) > $annualQuota
            ) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Not enough remaining annual leave quota for this request.'));
            }

            // Check if user has already clocked in/out on any of the requested dates
            $existingClockRecords = Attendance::where('user_id', Auth::user()->id)
                ->whereBetween('date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
                ->where(function ($query) {
                    $query->whereNotNull('time_in')
                        ->orWhereNotNull('time_out');
                })
                ->get();

            if ($existingClockRecords->isNotEmpty()) {
                $blockedDates = $existingClockRecords->pluck('date')
                    ->map(fn($date) => Carbon::parse($date)->format('d M Y'))
                    ->join(', ');

                return redirect()->back()
                    ->withInput()
                    ->with('error', "Tidak dapat mengajukan izin. Anda sudah melakukan absensi (clock in/out) pada tanggal: {$blockedDates}");
            }

            // Check if user has already pending or approved leave requests on any of the requested dates
            $existingLeaveRequests = Attendance::where('user_id', Auth::user()->id)
                ->whereBetween('date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
                ->whereIn('approval_status', [Attendance::STATUS_PENDING, Attendance::STATUS_APPROVED])
                ->get();

            if ($existingLeaveRequests->isNotEmpty()) {
                $blockedDates = $existingLeaveRequests->pluck('date')
                    ->map(fn($date) => Carbon::parse($date)->format('d M Y'))
                    ->join(', ');

                return redirect()->back()
                    ->withInput()
                    ->with('error', "Tidak dapat mengajukan izin. Anda sudah memiliki pengajuan izin (Pending/Disetujui) pada tanggal: {$blockedDates}");
            }

            // Save new attachment file
            $newAttachment = null;
            if ($request->file('attachment')) {
                // Use Service (Enterprise/Community logic handled by Provider)
                $service = app(\App\Contracts\AttendanceServiceInterface::class);
                $newAttachment = $service->storeAttachment($request->file('attachment'));
            }

            $fromDate->range($toDate)
                ->forEach(function (Carbon $date) use ($request, $newAttachment) {
                    $existing = Attendance::where('user_id', Auth::user()->id)
                        ->where('date', $date->format('Y-m-d'))
                        ->first();

                    if ($existing) {
                        // Only update if no clock in/out exists (double check)
                        if (is_null($existing->time_in) && is_null($existing->time_out)) {
                            $existing->update([
                                'status' => $request->status,
                                'note' => $request->note,
                                'attachment' => $newAttachment ?? $existing->attachment,
                                'latitude_in' => $request->lat ? doubleval($request->lat) : $existing->latitude_in,
                                'longitude_in' => $request->lng ? doubleval($request->lng) : $existing->longitude_in,
                                'approval_status' => Attendance::STATUS_PENDING,
                            ]);
                        }
                    } else {
                        Attendance::create([
                            'user_id' => Auth::user()->id,
                            'status' => $request->status,
                            'date' => $date->format('Y-m-d'),
                            'note' => $request->note,
                            'attachment' => $newAttachment ?? null,
                            'latitude_in' => $request->lat ? doubleval($request->lat) : null,
                            'longitude_in' => $request->lng ? doubleval($request->lng) : null,
                            'approval_status' => Attendance::STATUS_PENDING,
                        ]);
                    }
                });

            // Clear cache for affected months
            Attendance::clearUserAttendanceCache(Auth::user(), $fromDate);
            if (!$fromDate->isSameMonth($toDate)) {
                Attendance::clearUserAttendanceCache(Auth::user(), $toDate);
            }

            \App\Models\ActivityLog::record('Leave Request', "User submitted {$request->status} request from {$fromDate->format('Y-m-d')} to {$toDate->format('Y-m-d')}");

            // Notify Supervisor AND Admins (Broad Visibility)
            $supervisor = Auth::user()->supervisor;
            $admins = \App\Models\User::whereIn('group', ['admin', 'superadmin'])->get();
            
            $notifiable = $admins;
            if ($supervisor) {
                $notifiable = $notifiable->push($supervisor)->unique('id');
            }
            
            $latestAttendance = \App\Models\Attendance::where('user_id', Auth::id())->latest('date')->latest('created_at')->first();
            
            if (class_exists(\Illuminate\Support\Facades\Notification::class) && $latestAttendance && $notifiable->count() > 0) {
                // Pass date range to notification for summary
                // 1. Bell Notification (Sync)
                \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\LeaveRequested($latestAttendance, $fromDate, $toDate));

                // 2. Email Notification (Queued) - Send to Supervisor/Admins
                \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\LeaveRequestedEmail($latestAttendance, $fromDate, $toDate));
                
                // 3. Global Admin Email (Queued)
                $adminEmail = \App\Models\Setting::getValue('notif.admin_email');
                if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    try {
                        \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
                            ->notify(new \App\Notifications\LeaveRequestedEmail($latestAttendance, $fromDate, $toDate));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to send admin email notification: ' . $e->getMessage());
                    }
                }
            }

            return redirect(route('home'))
                ->with('success', __('Pengajuan izin berhasil dibuat.'));
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }

    public function downloadAttachment(Attendance $attendance)
    {
        // Authorization: User owns it OR User is Admin
        if ($attendance->user_id !== Auth::id() && !Auth::user()->isAdmin) {
            abort(403);
        }

        if (!$attendance->attachment) {
            abort(404);
        }

        // If stored in public disk (legacy), redirect
        if (Storage::disk('public')->exists($attendance->attachment)) {
            return redirect(Storage::disk('public')->url($attendance->attachment));
        }
        
        // Serve from local disk
        if (Storage::disk('local')->exists($attendance->attachment)) {
            return Storage::disk('local')->download($attendance->attachment);
        }

        abort(404, 'File not found');
    }

    public function history()
    {
        return view('attendances.history');
    }
}
