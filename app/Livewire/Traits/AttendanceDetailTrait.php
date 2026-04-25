<?php

namespace App\Livewire\Traits;

use App\Models\Attendance;

trait AttendanceDetailTrait
{
    public bool $showDetail = false;

    public $currentAttendance = [];

    public function show($attendanceId)
    {
        /** @var Attendance */
        $attendance = Attendance::with(['user', 'barcode', 'shift'])->find($attendanceId);

        if ($attendance) {
            $this->showDetail = true;
            $this->currentAttendance = $attendance->getAttributes();

            // User Info
            $this->currentAttendance['name'] = $attendance->user->name;
            $this->currentAttendance['nip'] = $attendance->user->nip;
            $this->currentAttendance['address'] = $attendance->user->address;

            // Location Data - Check In
            $this->currentAttendance['latitude_in'] = $attendance->latitude_in;
            $this->currentAttendance['longitude_in'] = $attendance->longitude_in;

            // Location Data - Check Out
            $this->currentAttendance['latitude_out'] = $attendance->latitude_out;
            $this->currentAttendance['longitude_out'] = $attendance->longitude_out;

            // Legacy Support (untuk backward compatibility)
            $this->currentAttendance['latitude'] = $attendance->latitude_in ?? $attendance->latitude;
            $this->currentAttendance['longitude'] = $attendance->longitude_in ?? $attendance->longitude;

            // Attachment
            if ($attendance->attachment) {
                $this->currentAttendance['attachment'] = $attendance->attachment_url;
            }

            // Barcode
            if ($attendance->barcode_id && $attendance->barcode) {
                $this->currentAttendance['barcode'] = [
                    'id' => $attendance->barcode->id,
                    'name' => $attendance->barcode->name,
                    'value' => $attendance->barcode->value ?? null,
                ];
            }

            // Shift
            if ($attendance->shift_id && $attendance->shift) {
                $this->currentAttendance['shift'] = [
                    'id' => $attendance->shift->id,
                    'name' => $attendance->shift->name,
                    'start_time' => $attendance->shift->start_time ?? null,
                    'end_time' => $attendance->shift->end_time ?? null,
                ];
            }

            // Dispatch event untuk initialize maps di modal
            $this->dispatch(
                'attendance-detail-loaded',
                latIn: $attendance->latitude_in,
                lngIn: $attendance->longitude_in,
                latOut: $attendance->latitude_out,
                lngOut: $attendance->longitude_out
            );
        }
    }

    public function closeDetail()
    {
        $this->showDetail = false;
        $this->currentAttendance = [];

        // Dispatch event untuk cleanup maps
        $this->dispatch('attendance-detail-closed');
    }
}
