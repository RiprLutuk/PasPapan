<?php

namespace App\Policies;

use App\Helpers\Editions;
use App\Models\EmployeeDocumentRequest;
use App\Models\User;

class EmployeeDocumentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return ! Editions::documentRequestsLocked() && $user->isUser;
    }

    public function viewAdminAny(User $user): bool
    {
        return ! Editions::documentRequestsLocked() && $user->can('viewAdminDocumentRequests');
    }

    public function view(User $user, EmployeeDocumentRequest $request): bool
    {
        return ! Editions::documentRequestsLocked()
            && ($user->can('viewAdminDocumentRequests') || $request->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return ! Editions::documentRequestsLocked() && $user->isUser;
    }

    public function createForEmployee(User $user): bool
    {
        return ! Editions::documentRequestsLocked()
            && $user->allowsAdminPermission([
                'admin.document_requests.request',
                'admin.document_requests.fulfill',
            ]);
    }

    public function upload(User $user, EmployeeDocumentRequest $request): bool
    {
        return ! Editions::documentRequestsLocked()
            && $request->user_id === $user->id
            && in_array($request->status, [
                EmployeeDocumentRequest::STATUS_REQUESTED,
                EmployeeDocumentRequest::STATUS_REJECTED,
            ], true);
    }

    public function fulfill(User $user, EmployeeDocumentRequest $request): bool
    {
        return ! Editions::documentRequestsLocked()
            && $user->allowsAdminPermission('admin.document_requests.fulfill')
            && in_array($request->status, [
                EmployeeDocumentRequest::STATUS_PENDING,
                EmployeeDocumentRequest::STATUS_UPLOADED,
                EmployeeDocumentRequest::STATUS_GENERATED,
            ], true);
    }

    public function generate(User $user, EmployeeDocumentRequest $request): bool
    {
        return ! Editions::documentRequestsLocked()
            && $user->allowsAdminPermission([
                'admin.document_requests.generate',
                'admin.document_requests.fulfill',
            ])
            && in_array($request->status, [
                EmployeeDocumentRequest::STATUS_PENDING,
                EmployeeDocumentRequest::STATUS_REQUESTED,
                EmployeeDocumentRequest::STATUS_UPLOADED,
            ], true);
    }

    public function reject(User $user, EmployeeDocumentRequest $request): bool
    {
        return ! Editions::documentRequestsLocked()
            && $user->allowsAdminPermission('admin.document_requests.fulfill')
            && ! in_array($request->status, [
                EmployeeDocumentRequest::STATUS_READY,
                EmployeeDocumentRequest::STATUS_EXPIRED,
            ], true);
    }

    public function download(User $user, EmployeeDocumentRequest $request): bool
    {
        return ! Editions::documentRequestsLocked()
            && $request->generated_path !== null
            && ($request->user_id === $user->id || $user->can('viewAdminDocumentRequests'));
    }

    public function downloadUpload(User $user, EmployeeDocumentRequest $request): bool
    {
        return ! Editions::documentRequestsLocked()
            && $request->uploaded_path !== null
            && ($request->user_id === $user->id || $user->can('viewAdminDocumentRequests'));
    }
}
