<?php

namespace App\Support\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface ScopesCompanies
{
    /**
     * Restrict a company query to the actor's visibility.
     */
    public function scopeCompanies(Builder $query, User $actor): Builder;
}
