<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReimbursementAttachmentController extends Controller
{
    public function show(Reimbursement $reimbursement)
    {
        $user = Auth::user();

        if (! $user || ($reimbursement->user_id !== $user->id && ! $user->isAdmin)) {
            abort(403);
        }

        $path = $reimbursement->attachment;

        if (! $path || $this->hasUnsafePath($path)) {
            abort(404);
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        // Backward compatibility for files uploaded before reimbursement
        // attachments were moved behind authenticated routes.
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404);
    }

    private function hasUnsafePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_contains($path, '..')
            || preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path) === 1;
    }
}
