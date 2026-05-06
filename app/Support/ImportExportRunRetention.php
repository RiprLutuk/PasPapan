<?php

namespace App\Support;

use App\Models\ImportExportRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ImportExportRunRetention
{
    public function filterVisible(iterable $runs, int $hours = 12): array
    {
        $runItems = collect($runs)->values();
        $ids = $runItems->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return $runItems->all();
        }

        $expiredIds = $this->expiredQuery($hours)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($expiredIds === []) {
            return $runItems->all();
        }

        return $runItems
            ->reject(fn (array $run) => in_array((int) ($run['id'] ?? 0), $expiredIds, true))
            ->values()
            ->all();
    }

    public function pruneExpired(int $hours = 12): int
    {
        $deleted = 0;

        $this->expiredQuery($hours)
            ->orderBy('id')
            ->chunkById(100, function (Collection $runs) use (&$deleted): void {
                foreach ($runs as $run) {
                    $this->deleteRunFiles($run);
                    $run->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    private function expiredQuery(int $hours): Builder
    {
        $cutoff = now()->subHours(max(1, $hours));

        return ImportExportRun::query()
            ->whereIn('status', ['completed', 'failed'])
            ->where(function (Builder $query) use ($cutoff): void {
                $query
                    ->where(fn (Builder $completed) => $completed
                        ->whereNotNull('completed_at')
                        ->where('completed_at', '<=', $cutoff))
                    ->orWhere(fn (Builder $failed) => $failed
                        ->whereNotNull('failed_at')
                        ->where('failed_at', '<=', $cutoff))
                    ->orWhere(fn (Builder $legacy) => $legacy
                        ->whereNull('completed_at')
                        ->whereNull('failed_at')
                        ->where('updated_at', '<=', $cutoff));
            });
    }

    private function deleteRunFiles(ImportExportRun $run): void
    {
        foreach ([
            [$run->file_disk, $run->file_path],
            [$run->source_disk, $run->source_path],
        ] as [$disk, $path]) {
            if (! $disk || ! $path) {
                continue;
            }

            try {
                Storage::disk($disk)->delete($path);
            } catch (\Throwable) {
                // A missing or unavailable file should not block pruning the expired run record.
            }
        }
    }
}
