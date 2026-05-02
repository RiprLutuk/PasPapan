<?php

namespace App\Notifications;

use App\Models\EmployeeDocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeDocumentRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmployeeDocumentRequest $request,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->request->statusLabel();

        return (new MailMessage)
            ->subject(__('Document Request').' '.$statusLabel)
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your :type request is :status.', [
                'type' => $this->request->documentTypeLabel(),
                'status' => mb_strtolower($statusLabel),
            ]))
            ->when((bool) $this->request->purpose, fn (MailMessage $message) => $message->line(__('Purpose: :purpose', [
                'purpose' => $this->request->purpose,
            ])))
            ->when((bool) $this->request->due_date, fn (MailMessage $message) => $message->line(__('Due date: :date', [
                'date' => $this->request->due_date->format('d M Y'),
            ])))
            ->action(__('Open Document Requests'), route('document-requests'))
            ->line(__('You will also receive in-app notifications for updates to this request.'));
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
