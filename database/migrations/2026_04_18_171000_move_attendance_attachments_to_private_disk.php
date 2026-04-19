<?php

use App\Models\Attendance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Attendance::query()
            ->whereNotNull('attachment')
            ->select(['id', 'attachment'])
            ->chunkById(100, function ($attendances) {
                foreach ($attendances as $attendance) {
                    foreach ($this->attachmentPaths($attendance->attachment) as $path) {
                        $this->movePublicFileToLocal($path);
                    }
                }
            });
    }

    public function down(): void
    {
        // Intentionally irreversible: attendance attachments should remain private.
    }

    private function attachmentPaths(?string $attachment): array
    {
        if (! $attachment) {
            return [];
        }

        $decoded = json_decode($attachment, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($path) => is_string($path)));
        }

        return [$attachment];
    }

    private function movePublicFileToLocal(string $path): void
    {
        if ($path === ''
            || str_contains($path, '://')
            || $this->hasUnsafePath($path)
            || ! Storage::disk('public')->exists($path)
        ) {
            return;
        }

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, Storage::disk('public')->get($path));
        }

        Storage::disk('public')->delete($path);
    }

    private function hasUnsafePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_contains($path, '..')
            || preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path) === 1;
    }
};
