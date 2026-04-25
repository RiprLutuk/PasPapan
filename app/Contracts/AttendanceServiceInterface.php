<?php

namespace App\Contracts;

use App\Models\Attendance;
use Illuminate\Http\UploadedFile;

interface AttendanceServiceInterface
{
    /**
     * Store the attachment file.
     *
     * @return string The stored file path
     */
    public function storeAttachment(UploadedFile $file): string;

    /**
     * Get the URL for the attachment.
     */
    public function getAttachmentUrl(Attendance $attendance): string|array|null;

    /**
     * Check if Face Enrollment should be enforced.
     */
    public function shouldEnforceFaceEnrollment(): bool;

    /**
     * Store attendance photo securely.
     */
    public function storeAttendancePhoto(string $base64Data, string $filename): string;

    /**
     * Register a face descriptor for the user.
     */
    public function registerFace(\App\Models\User $user, array $descriptor): void;

    /**
     * Remove the user's face registration.
     */
    public function removeFace(\App\Models\User $user): void;
}
