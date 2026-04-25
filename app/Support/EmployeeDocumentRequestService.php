<?php

namespace App\Support;

use App\Models\EmployeeDocumentRequest;
use App\Models\User;
use App\Notifications\EmployeeDocumentRequestStatusUpdated;
use Illuminate\Database\Eloquent\Builder;

class EmployeeDocumentRequestService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(User $user, array $payload): EmployeeDocumentRequest
    {
        return EmployeeDocumentRequest::create([
            'user_id' => $user->id,
            'document_type' => $payload['document_type'],
            'purpose' => $payload['purpose'],
            'details' => $payload['details'] ?: null,
            'status' => EmployeeDocumentRequest::STATUS_PENDING,
        ]);
    }

    public function markReady(EmployeeDocumentRequest $request, User $actor, ?string $note = null): string
    {
        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_READY,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'fulfillment_note' => $note,
            'rejection_note' => null,
        ]);

        $this->notifyStatusUpdated($request);

        return __('Document request marked as ready.');
    }

    public function reject(EmployeeDocumentRequest $request, User $actor, ?string $note = null): string
    {
        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_note' => $note,
        ]);

        $this->notifyStatusUpdated($request);

        return __('Document request rejected.');
    }

    public function managementQuery(string $statusFilter = 'pending', string $typeFilter = 'all', string $search = ''): Builder
    {
        return EmployeeDocumentRequest::query()
            ->with(['user.division', 'user.jobTitle', 'reviewer'])
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn (Builder $query) => $query->where('document_type', $typeFilter))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('purpose', 'like', '%'.$search.'%')
                        ->orWhere('details', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest();
    }

    private function notifyStatusUpdated(EmployeeDocumentRequest $request): void
    {
        $request->refresh()->loadMissing('user');
        $request->user?->notify(new EmployeeDocumentRequestStatusUpdated($request));
    }
}
