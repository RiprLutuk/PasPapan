<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UserAttendanceController extends Controller
{
    public function applyLeave()
    {
        $attendance = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        return view('attendances.apply-leave', ['attendance' => $attendance]);
    }

    public function storeLeaveRequest(Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:excused,sick'],
            'note' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'attachment' => ['nullable', 'file', 'max:3072'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        try {
            $fromDate = Carbon::parse($request->from);
            $toDate = Carbon::parse($request->to ?? $fromDate);

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
                    ->with('flash.banner', "Tidak dapat mengajukan izin. Anda sudah melakukan absensi (clock in/out) pada tanggal: {$blockedDates}")
                    ->with('flash.bannerStyle', 'danger');
            }

            // Save new attachment file
            $newAttachment = null;
            if ($request->file('attachment')) {
                $newAttachment = $request->file('attachment')->storePublicly(
                    'attachments',
                    ['disk' => config('jetstream.attachment_disk', 'public')]
                );
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

            return redirect(route('home'))
                ->with('flash.banner', __('Pengajuan izin berhasil dibuat.'));
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withInput()
                ->with('flash.banner', 'Terjadi kesalahan: ' . $th->getMessage())
                ->with('flash.bannerStyle', 'danger');
        }
    }

    public function history()
    {
        return view('attendances.history');
    }
}
