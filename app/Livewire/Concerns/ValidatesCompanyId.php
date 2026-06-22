<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesCompanyId
{
    /**
     * Validation rules for a required company id that must exist.
     *
     * @return array<int, mixed>
     */
    protected function companyIdRules(): array
    {
        return ['required', 'integer', Rule::exists('companies', 'id')];
    }
}
