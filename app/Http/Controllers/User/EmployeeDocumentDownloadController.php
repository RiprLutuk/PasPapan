<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocumentRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentDownloadController extends Controller
{
    public function generated(EmployeeDocumentRequest $documentRequest): StreamedResponse
    {
        $this->authorize('download', $documentRequest);

        abort_if(! $documentRequest->generated_path || ! Storage::disk('local')->exists($documentRequest->generated_path), 404);

        return Storage::disk('local')->download(
            $documentRequest->generated_path,
            'document-request-'.$documentRequest->id.'.pdf',
        );
    }

    public function uploaded(EmployeeDocumentRequest $documentRequest): StreamedResponse
    {
        $this->authorize('downloadUpload', $documentRequest);

        abort_if(! $documentRequest->uploaded_path || ! Storage::disk('local')->exists($documentRequest->uploaded_path), 404);

        return Storage::disk('local')->download(
            $documentRequest->uploaded_path,
            $documentRequest->uploaded_original_name ?: 'uploaded-document-'.$documentRequest->id,
        );
    }
}
