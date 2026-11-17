<?php

namespace App\Notifications;

use App\Models\EmployeeDocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $this->request->refresh()->loadMissing('documentType');
        $statusLabel = $this->request->statusLabel();
        $attachmentPath = $this->generatedAttachmentPath();
        $mail = (new MailMessage)
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
            ])));

        if ($attachmentPath !== null) {
            return $mail
                ->line(__('The generated PDF is attached to this email.'))
                ->attachData(
                    Storage::disk('local')->get($attachmentPath),
                    $this->attachmentFileName(),
                    ['mime' => 'application/pdf'],
                )
                ->line(__('You will also receive in-app notifications for updates to this request.'));
        }

        return $mail
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

    private function generatedAttachmentPath(): ?string
    {
        $path = $this->request->generated_path;

        if (! is_string($path) || $path === '' || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return $path;
    }

    private function attachmentFileName(): string
    {
        return sprintf(
            'document-request-%s-%s.pdf',
            $this->request->id,
            Str::slug($this->request->documentTypeLabel()) ?: 'document',
        );
    }
}
