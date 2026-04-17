<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendancePhotoController extends Controller
{
    /**
     * Serve attendance photo securely.
     *
     * @param Request $request
     * @param Attendance $attendance
     * @param string $type 'in' or 'out'
     * @param int|null $index Index for multiple attachments
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function show(Request $request, Attendance $attendance, string $type, string|int|null $index = null)
    {
        // 1. Authorization Check
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Allow: Admin users or the attendance owner
        if (!$user->isAdmin && $user->id !== $attendance->user_id) {
            abort(403, 'Forbidden');
        }

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

        // 4. Locate File (Enterprise vs Community)
        // Check secure local disk first (Enterprise)
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        // Check public disk (Community)
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404, 'File not found locally');
    }
}
