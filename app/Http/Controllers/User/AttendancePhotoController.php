<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Support\FileAccessService;

class AttendancePhotoController extends Controller
{
    public function __construct(
        protected FileAccessService $fileAccessService,
    ) {
    }

    /**
     * Serve attendance photo securely.
     *
     * @param Request $request
     * @param Attendance $attendance
     * @param string $type 'in' or 'out'
     * @param int|null $index Index for multiple attachments
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function show(Attendance $attendance, string $type, string|int|null $index = null)
    {
        $this->authorize('view', $attendance);

        // 2. Get Attachment Data
        $attachmentData = $attendance->attachment;

        if (empty($attachmentData)) {
            abort(404, 'No attachment found');
        }

        // Decode if string
        if (is_string($attachmentData)) {
            $decoded = json_decode($attachmentData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $attachmentData = $decoded;
            }
        }

        // 3. Resolve Path
        $path = null;

        if (is_array($attachmentData)) {
            // Check for type key first
            if (isset($attachmentData[$type])) {
                $path = $attachmentData[$type];
            } 
            // Fallback: check index if provided
            elseif ($index !== null && isset($attachmentData[$index])) {
                $path = $attachmentData[$index];
            } 
            // Fallback: if requesting 'general' but we have specific keys like 'in' or 'out'
            elseif ($type === 'general') {
                 $path = reset($attachmentData);
            }
        } else {
            // String path
            $path = $attachmentData;
        }

        if (!$path) {
            abort(404, 'Photo not found');
        }

        return $this->fileAccessService->streamRelativePath(
            $path,
            'Attendance Photo Viewed',
            'Viewed attendance photo type `' . $type . '`'
        );
    }
}
