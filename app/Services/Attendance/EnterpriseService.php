<?php

namespace App\Services\Attendance;

use App\Contracts\AttendanceServiceInterface;
use App\Models\Attendance;
use App\Models\FaceDescriptor;
use App\Models\Setting;
use App\Models\User;
use App\Services\Enterprise\LicenseGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EnterpriseService implements AttendanceServiceInterface
{
    public function __construct()
    {
        LicenseGuard::check();
    }

    public function storeAttachment(UploadedFile $file): string
    {
        // Enterprise: Store Securely
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
        $getUrl = function ($path) use ($attendance) {
            if (str_contains($path, 'https://') || str_contains($path, 'http://')) {
                return $path;
            }

            // Secure Route
            return route('attendance.attachment.download', ['attendance' => $attendance->id]);
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
        // Enterprise: Check Configuration
        return filter_var(Setting::getValue('feature.require_photo', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function storeAttendancePhoto(string $base64Data, string $filename): string
    {
        // Enterprise: Secure Local Storage 🔒
        // Stored outside public folder, accessible only via secure routes
        $path = 'attendance_photos/'.date('Y/m/d');

        $image = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,', ' '], ['', '', '+'], $base64Data);
        Storage::disk('local')->put($path.'/'.$filename, base64_decode($image));

        return $path.'/'.$filename;
    }

    public function registerFace(User $user, array $descriptor): void
    {
        // Enterprise: Feature Unlocked 🔓
        FaceDescriptor::updateOrCreate(
            ['user_id' => $user->id],
            ['descriptor' => $descriptor]
        );
    }

    public function removeFace(User $user): void
    {
        // Enterprise: Feature Unlocked 🔓
        $user->faceDescriptor()->delete();
    }
}
