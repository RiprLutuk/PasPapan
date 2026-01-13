<?php

namespace App\Livewire;

use App\ExtendedCarbon;
use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Ballen\Distical\Calculator as DistanceCalculator;
use Ballen\Distical\Entities\LatLong;
use Illuminate\Support\Carbon;

class ScanComponent extends Component
{
    public ?Attendance $attendance = null;
    public $shift_id = null;
    public $shifts = null;
    public ?array $currentLiveCoords = null;
    public string $successMsg = '';
    public bool $isAbsence = false;

    // Settings Cache
    public $gracePeriod = 0;
    public $photo = null;
    public $timeSettings = [];

    public function validateBarcode(string $barcode, ?float $lat = null, ?float $lng = null)
    {
        // Update coordinates if provided
        if ($lat !== null && $lng !== null) {
            $this->currentLiveCoords = [$lat, $lng];
        }

        if (is_null($this->currentLiveCoords)) {
            return __('Invalid location');
        } else if (is_null($this->shift_id)) {
            return __('Invalid shift');
        }

        /** @var Attendance */
        $attendanceForDay = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        if ($attendanceForDay && 
            in_array($attendanceForDay->status, ['sick', 'excused']) && 
            $attendanceForDay->approval_status === Attendance::STATUS_APPROVED 
        ) {
            return __('Anda tidak dapat melakukan absensi karena sedang Cuti/Izin/Sakit.');
        }

        /** @var Barcode */
        $barcodeModel = Barcode::firstWhere('value', $barcode);
        if (!Auth::check() || !$barcodeModel) {
            return __('Invalid barcode');
        }

        $barcodeLocation = new LatLong($barcodeModel->latLng['lat'], $barcodeModel->latLng['lng']);
        $userLocation = new LatLong($this->currentLiveCoords[0], $this->currentLiveCoords[1]);

        // Check Distance to Barcode (Local Radius)
        if (($distance = $this->calculateDistance($userLocation, $barcodeLocation)) > $barcodeModel->radius) {
            return __('Location out of range') . ": $distance" . "m. Max: $barcodeModel->radius" . "m";
        }

        return true;
    }



    public function scan(string $barcode, ?float $lat = null, ?float $lng = null, ?string $photo = null, ?string $note = null)
    {
        $this->photo = $photo;
        
        // Update coordinates if provided
        if ($lat !== null && $lng !== null) {
            $this->currentLiveCoords = [$lat, $lng];
        }

        if (is_null($this->currentLiveCoords)) {
            return __('Invalid location');
        } else if (is_null($this->shift_id)) {
            return __('Invalid shift');
        }

        /** @var Attendance */
        $attendanceForDay = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        if ($attendanceForDay && 
            in_array($attendanceForDay->status, ['sick', 'excused']) && 
            $attendanceForDay->approval_status === Attendance::STATUS_APPROVED // Only block if explicitly Approved
        ) {
            return __('Anda tidak dapat melakukan absensi karena sedang Cuti/Izin/Sakit.');
        }

        /** @var Barcode */
        $barcode = Barcode::firstWhere('value', $barcode);
        if (!Auth::check() || !$barcode) {
            return 'Invalid barcode';
        }

        if ((\App\Models\Setting::getValue('feature.require_photo', 1) == 1) && empty($this->photo)) {
             return 'Photo required';
        }

        $barcodeLocation = new LatLong($barcode->latLng['lat'], $barcode->latLng['lng']);
        $userLocation = new LatLong($this->currentLiveCoords[0], $this->currentLiveCoords[1]);

        // 1. Check Distance to Barcode (Local Radius)
        if (($distance = $this->calculateDistance($userLocation, $barcodeLocation)) > $barcode->radius) {
            return __('Location out of range') . ": $distance" . "m. Max: $barcode->radius" . "m";
        }



        /** @var Attendance */
        $existingAttendance = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))
            ->where('barcode_id', $barcode->id)
            ->first();

        if (!$existingAttendance) {
            // Check In
            $attendance = $this->createAttendance($barcode, $this->photo);
            $this->successMsg = __('Attendance In Successful');
            \App\Models\ActivityLog::record('Check In', 'User checked in via barcode: ' . $barcode->name);
        } else {
            // Check Out
            // Handle legacy string vs new JSON array
            $attendance = $existingAttendance;
            $existingAttachment = $existingAttendance->attachment;
            $attachments = [];

            if ($existingAttachment) {
                $decoded = json_decode($existingAttachment, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                     $attachments = $decoded;
                } else {
                     // Legacy string
                     $attachments = ['in' => $existingAttachment];
                }
            }

            if ($this->photo) {
                $attachments['out'] = $this->savePhoto($this->photo);
            }

            $updateData = [
                'time_out' => date('H:i:s'),
                'latitude_out' => doubleval($this->currentLiveCoords[0]),
                'longitude_out' => doubleval($this->currentLiveCoords[1]),
                'attachment' => json_encode($attachments),
            ];

            // If note is provided (e.g. early checkout reasoning), save it
            if ($note) {
                $updateData['note'] = $note;
            }

            $attendance->update($updateData);
            $this->successMsg = __('Attendance Out Successful');
            \App\Models\ActivityLog::record('Check Out', 'User checked out.');
        }

        if ($attendance) {
            $this->setAttendance($attendance->fresh());
            Attendance::clearUserAttendanceCache(Auth::user(), Carbon::parse($attendance->date));
            $this->dispatch('attendance-recorded'); // Trigger update for other components
            
            // Redirect to home as requested
            return redirect()->route('home');
        }
    }

    public function calculateDistance(LatLong $a, LatLong $b)
    {
        $distanceCalculator = new DistanceCalculator($a, $b);
        $distanceInMeter = floor($distanceCalculator->get()->asKilometres() * 1000); // convert to meters
        return $distanceInMeter;
    }

    /** @return Attendance */
    public function createAttendance(Barcode $barcode, ?string $photoParam = null)
    {
        $now = Carbon::now();
        $date = $now->format('Y-m-d');
        $timeIn = $now->format('H:i:s');

        /** @var Shift */
        /** @var Shift */
        $shift = Shift::find($this->shift_id);
        
        $shiftStart = Carbon::parse($shift->start_time); // Assumes generic date, correct with time
        $shiftStart->setDate($now->year, $now->month, $now->day);
        
        // Apply Grace Period
        $lateThreshold = $shiftStart->copy()->addMinutes($this->gracePeriod);
        
        $status = $now->gt($lateThreshold) ? 'late' : 'present';

    
        $attachmentPath = $this->savePhoto($photoParam);

        return $this->saveAttendanceRequest($barcode, $date, $timeIn, $status, $attachmentPath, $shift);
    }

    private function savePhoto(?string $photoParam): ?string
    {
        if (!$photoParam) return null;
        
        $image = str_replace('data:image/jpeg;base64,', '', $photoParam);
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = Auth::user()->id . '_' . time() . '.jpg';
        $path = 'attendance_photos/' . date('Y/m/d');
        
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($path);
        }
        
        \Illuminate\Support\Facades\Storage::disk('public')->put($path . '/' . $imageName, base64_decode($image));
        return $path . '/' . $imageName;
    }

    private function saveAttendanceRequest($barcode, $date, $timeIn, $status, $attachmentPath, $shift) {
        // Check if there is an existing record to override (Rejected, Absent, or Pending Sick/Excused)
        $overrideable = Attendance::where('user_id', Auth::user()->id)
            ->where('date', $date)
            ->where(function($q) {
                $q->whereIn('status', ['rejected', 'absent', 'sick', 'excused'])
                  ->orWhere('approval_status', Attendance::STATUS_REJECTED);
            })
            ->first();

        if ($overrideable) {
            $overrideable->update([
                'barcode_id' => $barcode->id,
                'time_in' => $timeIn,
                'time_out' => null,
                'shift_id' => $shift->id,
                'latitude_in' => doubleval($this->currentLiveCoords[0]),
                'longitude_in' => doubleval($this->currentLiveCoords[1]),
                // Legacy fields
                'latitude' => doubleval($this->currentLiveCoords[0]),
                'longitude' => doubleval($this->currentLiveCoords[1]),

                'status' => $status,
                'note' => null, 
                'attachment' => $attachmentPath ? json_encode(['in' => $attachmentPath]) : null,
                'rejection_note' => null,
                'approval_status' => Attendance::STATUS_APPROVED, // Auto-approve presence
            ]);
            return $overrideable;
        }

        return Attendance::create([
            'user_id' => Auth::user()->id,
            'barcode_id' => $barcode->id,
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => null,
            'shift_id' => $shift->id,

            // New: Separate location for check in
            'latitude_in' => doubleval($this->currentLiveCoords[0]),
            'longitude_in' => doubleval($this->currentLiveCoords[1]),

            // Legacy: Keep for backward compatibility (optional)
            'latitude' => doubleval($this->currentLiveCoords[0]),
            'longitude' => doubleval($this->currentLiveCoords[1]),

            'status' => $status,
            'note' => null,
            'attachment' => $attachmentPath ? json_encode(['in' => $attachmentPath]) : null,
        ]);
    }

    protected function setAttendance(Attendance $attendance)
    {
        $this->attendance = $attendance;
        $this->shift_id = $attendance->shift_id;
        $this->isAbsence = in_array($attendance->status, ['sick', 'excused']) && $attendance->approval_status === Attendance::STATUS_APPROVED;
    }

    public function getAttendance()
    {
        if (is_null($this->attendance)) {
            return null;
        }
        return [
            'time_in' => $this->attendance?->time_in,
            'time_out' => $this->attendance?->time_out,
            'latitude_in' => $this->attendance?->latitude_in,
            'longitude_in' => $this->attendance?->longitude_in,
            'latitude_out' => $this->attendance?->latitude_out,
            'longitude_out' => $this->attendance?->longitude_out,
            'shift_end_time' => $this->attendance?->shift?->end_time,
        ];
    }

    public function mount()
    {
        $this->shifts = Shift::all();

        /** @var Attendance */
        $attendance = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))->first();

        if ($attendance) {
            $this->setAttendance($attendance);
        } 
        
        // Fallback: If no shift_id (e.g. from rejected leave), try auto-detect
        if (is_null($this->shift_id)) {
            // Priority 1: Check Manual Schedule
            /** @var \App\Models\Schedule */
            $schedule = \App\Models\Schedule::where('user_id', Auth::user()->id)
                ->where('date', date('Y-m-d'))
                ->first();

            if ($schedule && $schedule->shift_id) {
                // Use Scheduled Shift
                $this->shift_id = $schedule->shift_id;
            } else {
                // Priority 2: Auto-detect closest shift (Fallback)
                // get closest shift from current time
                // get closest shift from current time
                $shiftTimes = $this->shifts->pluck('start_time')->toArray();
                if (empty($shiftTimes)) {
                     // No shifts available
                     $this->shift_id = null;
                } else {
                    $closest = ExtendedCarbon::now()->closestFromDateArray($shiftTimes);
                    
                    if ($closest) {
                         $matched = $this->shifts
                            ->where(fn(Shift $shift) => $shift->start_time == $closest->format('H:i:s'))
                            ->first();
                         $this->shift_id = $matched ? $matched->id : null;
                    }
                }
            }
        }

        // Load Settings
        $this->gracePeriod = (int) \App\Models\Setting::getValue('attendance.grace_period', 0);
        
        $this->timeSettings = [
            'format' => \App\Models\Setting::getValue('app.time_format', '24'),
            'show_seconds' => (bool) \App\Models\Setting::getValue('app.show_seconds', false),
        ];
    }

    public function render()
    {
        return view('livewire.scan');
    }
}
