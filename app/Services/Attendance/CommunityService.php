<?php

namespace App\Services\Attendance;

use App\Contracts\AttendanceServiceInterface;
use App\Models\Attendance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CommunityService implements AttendanceServiceInterface
{
    public function storeAttachment(UploadedFile $file): string
    {
        return $file->store(
            'attachments',
            ['disk' => 'local']
        );
    }

    public function getAttachmentUrl(Attendance $attendance): string|array|null
    {
        if (! $attendance->attachment) {
            return null;
        }

        $decoded = json_decode($attendance->attachment, true);

        // Helper
        $getUrl = function ($path, $type = null) use ($attendance) {
            if (str_contains($path, 'https://') || str_contains($path, 'http://')) {
                return $path;
            }

            if ($type !== null) {
                return route('attendance.photo', [
                    'attendance' => $attendance->id,
                    'type' => $type,
                ]);
            }

            return route('attendance.attachment.download', ['attendance' => $attendance->id]);
        };

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $urls = [];
            foreach ($decoded as $key => $path) {
                $urls[$key] = $getUrl($path, $key);
            }

            return $urls;
        }

        return $getUrl($attendance->attachment);
    }

    public function shouldEnforceFaceEnrollment(): bool
    {
        return filter_var(
            \App\Models\Setting::getValue('attendance.require_face_enrollment', false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function storeAttendancePhoto(string $base64Data, string $filename): string
    {
        $path = 'attendance_photos/'.date('Y/m/d');

        $image = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,', ' '], ['', '', '+'], $base64Data);
        Storage::disk('local')->put($path.'/'.$filename, base64_decode($image));

        return $path.'/'.$filename;
    }

    public function registerFace(\App\Models\User $user, array $descriptor): void
    {
        // Community Edition: Face ID Unlocked
        \App\Models\FaceDescriptor::updateOrCreate(
            ['user_id' => $user->id],
            ['descriptor' => $descriptor]
        );
    }

    public function removeFace(\App\Models\User $user): void
    {
        // Community Edition: Face ID Unlocked
        $user->faceDescriptor()->delete();
    }
}
