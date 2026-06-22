<?php

namespace App\Support\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesCompaniesByActor
{
    /**
     * Restrict a company query to the actor's visibility:
     * superadmins see every company, everyone else only their own.
     */
    public function scopeCompanies(Builder $query, User $actor): Builder
    {
        if ($actor->isSuperadmin) {
            return $query;
        }

        return $query->whereKey($actor->company_id);
    }
}
