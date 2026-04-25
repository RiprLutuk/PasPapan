<?php

namespace App\Policies;

use App\Models\EmployeeDocumentRequest;
use App\Models\User;

class EmployeeDocumentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isUser;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('viewAdminDocumentRequests');
    }

    public function view(User $user, EmployeeDocumentRequest $request): bool
    {
        return $user->can('viewAdminDocumentRequests') || $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function fulfill(User $user, EmployeeDocumentRequest $request): bool
    {
        return $user->allowsAdminPermission('admin.document_requests.fulfill')
            && $request->status === EmployeeDocumentRequest::STATUS_PENDING;
    }

    public function reject(User $user, EmployeeDocumentRequest $request): bool
    {
        return $this->fulfill($user, $request);
    }
}
