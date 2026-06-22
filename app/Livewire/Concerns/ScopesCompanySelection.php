<?php

namespace App\Livewire\Concerns;

use App\Models\Company;
use App\Models\User;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Support\Collection;

/**
 * Shared company-scope helpers for admin workspace Livewire components.
 *
 * The host component must expose its scoping service via companyScopeService()
 * and declare the `TABS` and `DEFAULT_TAB` constants.
 */
trait ScopesCompanySelection
{
    abstract protected function companyScopeService(): ScopesCompanies;

    protected function defaultCompanyId(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $companyId = $this->companyScopeService()
            ->scopeCompanies(Company::query(), $user)
            ->orderBy('name')
            ->value('id');

        return $companyId === null ? null : (string) $companyId;
    }

    /**
     * Company ids the actor may access.
     *
     * @return list<int>
     */
    protected function scopedCompanyIds(User $user): array
    {
        return $this->companyScopeService()
            ->scopeCompanies(Company::query(), $user)
            ->pluck('id')
            ->all();
    }

    /**
     * Company select options for the given ids.
     *
     * @param  list<int|string>  $companyIds
     * @return Collection<int, Company>
     */
    protected function companyOptions(array $companyIds): Collection
    {
        return Company::query()
            ->whereIn('id', $companyIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Validate a selected company id against the accessible set.
     *
     * @param  list<int|string>  $companyIds
     */
    protected function scopedCompanyId(array $companyIds, string $companyId): ?int
    {
        if ($companyId === '') {
            return null;
        }

        $companyId = (int) $companyId;

        return in_array($companyId, array_map('intval', $companyIds), true) ? $companyId : null;
    }

    protected function normalizeActiveTab(): void
    {
        if (! in_array($this->activeTab, static::TABS, true)) {
            $this->activeTab = static::DEFAULT_TAB;
        }
    }
}
