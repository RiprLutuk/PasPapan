<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTitle extends Model
{
    use HasFactory;
    use HasTimestamps;

    protected $fillable = [
        'name',
        'level', // Deprecated, use job_level_id
        'job_level_id',
        'division_id',
    ];

    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (JobTitle $jobTitle): void {
            User::query()
                ->where('job_title_id', $jobTitle->id)
                ->update(['job_title_id' => null]);
        });
    }
}
