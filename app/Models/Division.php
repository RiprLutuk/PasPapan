<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory, HasTimestamps;

    protected $fillable = [
        'name',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Division $division): void {
            User::query()
                ->where('division_id', $division->id)
                ->update(['division_id' => null]);

            JobTitle::query()
                ->where('division_id', $division->id)
                ->update(['division_id' => null]);
        });
    }
}
