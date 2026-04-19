<?php

use App\Models\Reimbursement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reimbursements')) {
            return;
        }

        Reimbursement::query()
            ->whereNotNull('attachment')
            ->select(['id', 'attachment'])
            ->chunkById(100, function ($claims) {
                foreach ($claims as $claim) {
                    $path = (string) $claim->attachment;

                    if ($path === '' || $this->hasUnsafePath($path)) {
                        continue;
                    }

                    if (! Storage::disk('public')->exists($path)) {
                        continue;
                    }

                    if (! Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                    }

                    Storage::disk('public')->delete($path);
                }
            });
    }

    public function down(): void
    {
        // Intentionally irreversible: reimbursement attachments should remain private.
    }

    private function hasUnsafePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_contains($path, '..')
            || preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path) === 1;
    }
};
