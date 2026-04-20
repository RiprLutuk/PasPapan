<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;
    use HasTimestamps;

    protected $table = 'educations';

    protected $fillable = [
        'name'
    ];

    protected static function booted(): void
    {
        static::deleting(function (Education $education): void {
            User::query()
                ->where('education_id', $education->id)
                ->update(['education_id' => null]);
        });
    }
}
