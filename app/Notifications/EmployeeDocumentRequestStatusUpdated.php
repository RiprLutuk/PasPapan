<?php

namespace App\Notifications;

use App\Models\EmployeeDocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EmployeeDocumentRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmployeeDocumentRequest $request,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusLabel = $this->request->statusLabel();

        return [
            'type' => 'employee_document_request_status',
            'title' => __('Document Request').' '.$statusLabel,
            'request_id' => $this->request->id,
            'status' => $this->request->status,
            'message' => __('Your :type request is :status', [
                'type' => $this->request->documentTypeLabel(),
                'status' => mb_strtolower($statusLabel),
            ]),
            'url' => route('document-requests', absolute: false),
        ];
    }
}
