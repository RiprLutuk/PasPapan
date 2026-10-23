<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocumentRequest;
use App\Support\EmployeeDocumentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class E2eDocumentUploadController extends Controller
{
    public function __invoke(Request $request, EmployeeDocumentRequestService $documentRequests): JsonResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $expectedToken = (string) config('services.e2e.login_token', '');
        $providedToken = (string) $request->input('token', '');

        if ($expectedToken === '') {
            abort(404);
        }

        if (! hash_equals($expectedToken, $providedToken)) {
            abort(403);
        }

        $validated = $request->validate([
            'request_id' => ['required', 'integer', 'exists:employee_document_requests,id'],
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $documentRequest = EmployeeDocumentRequest::query()->findOrFail($validated['request_id']);
        abort_unless($request->user()?->can('upload', $documentRequest), 403);

        $message = $documentRequests->upload($documentRequest, $request->user(), $validated['attachment']);

        return response()->json([
            'ok' => true,
            'request_id' => $documentRequest->id,
            'message' => $message,
        ]);
    }
}
