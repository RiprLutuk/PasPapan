<?php

namespace App\Services\Attendance;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Support\DynamicBarcodeTokenService;
use Ballen\Distical\Calculator as DistanceCalculator;
use Ballen\Distical\Entities\LatLong;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class DeviceAttendanceService
{
    public function __construct(
        protected DynamicBarcodeTokenService $dynamicBarcodeTokenService,
    ) {}

    public function saveBarcodeScan(int|string $userId, string $barcodePayload, float $latitude, float $longitude, ?string $timestamp = null): array
    {
        $scanContext = $this->dynamicBarcodeTokenService->resolveScannedBarcodeWithSource($barcodePayload);
        $barcode = $scanContext['barcode'];
        $scanSource = $scanContext['source'] ?? 'static';

        if (! $barcode) {
            return [
                'ok' => false,
                'message' => 'Invalid barcode',
                'status' => 422,
            ];
        }

        $distance = $this->calculateDistance(
            new LatLong($latitude, $longitude),
            new LatLong((float) $barcode->latLng['lat'], (float) $barcode->latLng['lng'])
        );

        if ($distance > $barcode->radius) {
            return [
                'ok' => false,
                'message' => "Location out of range: {$distance}m. Max: {$barcode->radius}m",
                'status' => 422,
            ];
        }

        $attendance = Attendance::firstOrNew([
            'user_id' => $userId,
            'date' => now()->toDateString(),
        ]);

        if (
            $attendance->exists &&
            in_array($attendance->status, ['sick', 'excused', 'permission', 'leave'], true) &&
            $attendance->approval_status === Attendance::STATUS_APPROVED
        ) {
            return [
                'ok' => false,
                'message' => 'Attendance is blocked because the user is on approved leave.',
                'status' => 422,
            ];
        }

        $attendanceTime = $timestamp
            ? Carbon::createFromFormat('Y-m-d H:i:s', $timestamp)
            : now();

        if (is_null($attendance->time_in)) {
            $attendance->fill([
                'barcode_id' => $barcode->id,
                'time_in' => $attendanceTime,
                'latitude_in' => $latitude,
                'longitude_in' => $longitude,
                'status' => $attendance->status === 'absent' ? 'present' : ($attendance->status ?: 'present'),
            ]);
            $action = 'check_in';
        } elseif (is_null($attendance->time_out)) {
            if ((int) $attendance->barcode_id !== (int) $barcode->id) {
                return [
                    'ok' => false,
                    'message' => __('Please scan the same checkpoint used for check in.'),
                    'status' => 422,
                ];
            }

            $attendance->fill([
                'barcode_id' => $attendance->barcode_id,
                'time_out' => $attendanceTime,
                'latitude_out' => $latitude,
                'longitude_out' => $longitude,
            ]);
            $action = 'check_out';
        } else {
            return [
                'ok' => false,
                'message' => 'Attendance for today is already complete.',
                'status' => 409,
            ];
        }

        $attendance->save();

        if ($scanSource === 'dynamic') {
            $this->dynamicBarcodeTokenService->consumeScannedToken($barcode, $barcodePayload);
        }

        Attendance::clearUserAttendanceCache($attendance->user, Carbon::parse($attendance->date));
        ActivityLog::record(
            $scanSource === 'dynamic'
                ? ($action === 'check_in' ? 'Dynamic Check In' : 'Dynamic Check Out')
                : ($action === 'check_in' ? 'Check In' : 'Check Out'),
            ($action === 'check_in' ? 'User checked in via ' : 'User checked out via ')
                .($scanSource === 'dynamic' ? 'dynamic barcode: ' : 'barcode: ')
                .$barcode->name
        );

        return [
            'ok' => true,
            'attendance' => $attendance,
            'action' => $action,
        ];
    }

    public function uploadPhoto(int|string $userId, UploadedFile $photo, ?float $latitude = null, ?float $longitude = null): array
    {
        $path = $photo->store(
            'attendance_photos/'.now()->format('Y/m/d'),
            ['disk' => 'local']
        );

        $attendance = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('date', now()->toDateString())
            ->first();

        $attachments = $this->decodeAttachmentPayload($attendance?->attachment);
        $slot = $this->resolvePhotoSlot($attachments);

        if ($attendance) {
            $attendance->update([
                'attachment' => json_encode(array_merge($attachments, [$slot => $path])),
                'latitude_in' => $slot === 'in' ? ($latitude ?? $attendance->latitude_in) : $attendance->latitude_in,
                'longitude_in' => $slot === 'in' ? ($longitude ?? $attendance->longitude_in) : $attendance->longitude_in,
                'latitude_out' => $slot === 'out' ? ($latitude ?? $attendance->latitude_out) : $attendance->latitude_out,
                'longitude_out' => $slot === 'out' ? ($longitude ?? $attendance->longitude_out) : $attendance->longitude_out,
            ]);
        } else {
            $attachments[$slot] = $path;
            $attendance = Attendance::create([
                'user_id' => $userId,
                'date' => now()->toDateString(),
                'attachment' => json_encode($attachments),
                'latitude_in' => $latitude,
                'longitude_in' => $longitude,
                'status' => 'present',
            ]);
        }

        Attendance::clearUserAttendanceCache($attendance->user, Carbon::parse($attendance->date));

        return [
            'attendance' => $attendance,
            'slot' => $slot,
        ];
    }

    protected function calculateDistance(LatLong $a, LatLong $b): int
    {
        return (int) floor((new DistanceCalculator($a, $b))->get()->asKilometres() * 1000);
    }

    protected function decodeAttachmentPayload(?string $attachment): array
    {
        if (! $attachment) {
            return [];
        }

        $decoded = json_decode($attachment, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return ['in' => $attachment];
    }

    protected function resolvePhotoSlot(array $attachments): string
    {
        if (! isset($attachments['in'])) {
            return 'in';
        }

        if (! isset($attachments['out'])) {
            return 'out';
        }

        return 'out';
    }
}
