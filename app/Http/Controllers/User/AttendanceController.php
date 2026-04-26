<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveType;
use App\Services\Attendance\LeaveRequestService;
use App\Support\FileAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService,
        protected FileAccessService $fileAccessService,
    ) {}

    public function scan()
    {
        $this->authorize('create', Attendance::class);

        return view('attendances.scan');
    }

    public function applyLeave()
    {
        $this->authorize('create', Attendance::class);

        return view('attendances.apply-leave', $this->leaveRequestService->getApplyLeaveData(Auth::user()));
    }

    public function storeLeaveRequest(Request $request)
    {
        $this->authorize('create', Attendance::class);

        $leaveType = null;

        if ($request->filled('leave_type_id')) {
            $leaveType = LeaveType::query()
                ->active()
                ->findOrFail($request->integer('leave_type_id'));
        }

        $requireAttachment = \App\Models\Setting::getValue('leave.require_attachment', '1') === '1';
        $attachmentRequired = $requireAttachment || (bool) $leaveType?->requires_attachment;

        $request->validate([
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'status' => ['required_without:leave_type_id', 'nullable', 'in:excused,sick'],
            'note' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'attachment' => [$attachmentRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:3072'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        try {
            $fromDate = Carbon::parse($request->string('from'));
            $toDate = Carbon::parse($request->input('to', $fromDate->toDateString()));

            $result = $this->leaveRequestService->submitLeaveRequest(
                user: Auth::user(),
                status: $request->string('status', 'excused')->toString(),
                note: $request->string('note')->toString(),
                fromDate: $fromDate,
                toDate: $toDate,
                attachment: $request->file('attachment'),
                lat: $request->filled('lat') ? (float) $request->input('lat') : null,
                lng: $request->filled('lng') ? (float) $request->input('lng') : null,
                leaveType: $leaveType,
            );

            if (! $result->ok) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result->error);
            }

            return redirect(route('home'))
                ->with('success', __('Pengajuan izin berhasil dibuat.'));
        } catch (\Throwable $th) {
            Log::error('Failed to submit leave request.', [
                'user_id' => Auth::id(),
                'exception' => $th->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', __('Terjadi kesalahan saat membuat pengajuan izin. Silakan coba lagi.'));
        }
    }

    public function downloadAttachment(Attendance $attendance)
    {
        $this->authorize('view', $attendance);

        if (! $attendance->attachment) {
            abort(404);
        }

        return $this->fileAccessService->downloadRelativePath(
            $attendance->attachment,
            'Attendance Attachment Downloaded',
            'Downloaded attendance attachment'
        );
    }

    public function history()
    {
        $this->authorize('viewAny', Attendance::class);

        return view('attendances.history');
    }
}
