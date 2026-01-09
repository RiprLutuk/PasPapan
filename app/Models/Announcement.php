<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'priority',
        'publish_date',
        'expire_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'expire_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to creator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active/visible announcements.
     */
    public function scopeVisible($query)
    {
        $today = Carbon::today();
        
        return $query->where('is_active', true)
            ->where('publish_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('expire_date')
                  ->orWhere('expire_date', '>=', $today);
            })
            ->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('publish_date', 'desc');
    }

    /**
     * Users who dismissed this announcement.
     */
    public function dismissedByUsers()
    {
        return $this->belongsToMany(User::class, 'announcement_user_dismissals')
            ->withPivot('dismissed_at');
    }

    /**
     * Scope for announcements visible to a specific user (excluding dismissed).
     */
    public function scopeVisibleForUser($query, $userId)
    {
        $today = Carbon::today();
        
        return $query->where('is_active', true)
            ->where('publish_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('expire_date')
                  ->orWhere('expire_date', '>=', $today);
            })
            ->whereDoesntHave('dismissedByUsers', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('publish_date', 'desc');
    }

    /**
     * Get priority badge color.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'high' => 'red',
            'normal' => 'blue',
            'low' => 'gray',
            default => 'gray',
        };
    }
}
