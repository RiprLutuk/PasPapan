<?php

namespace App\Services\Attendance;

use App\Contracts\AttendanceServiceInterface;
use Illuminate\Http\UploadedFile;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;

class CommunityService implements AttendanceServiceInterface
{
    public function storeAttachment(UploadedFile $file): string
    {
        // Community Edition: Store publicly
        return $file->storePublicly(
            'attachments',
            ['disk' => config('jetstream.attachment_disk', 'public')]
        );
    }

    public function getAttachmentUrl(Attendance $attendance): string|array|null
    {
        if (!$attendance->attachment) {
            return null;
        }

        $decoded = json_decode($attendance->attachment, true);
        
        // Helper
        $getUrl = function($path) {
            if (str_contains($path, 'https://') || str_contains($path, 'http://')) {
                return $path;
            }
            return Storage::disk(config('jetstream.attachment_disk', 'public'))->url($path);
        };

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $urls = [];
            foreach ($decoded as $key => $path) {
                $urls[$key] = $getUrl($path);
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
        // Community: Public Storage (Insecure)
        $path = 'attendance_photos/' . date('Y/m/d');
        
        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }

        $image = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,', ' '], ['', '', '+'], $base64Data);
        Storage::disk('public')->put($path . '/' . $filename, base64_decode($image));

        return $path . '/' . $filename;
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
