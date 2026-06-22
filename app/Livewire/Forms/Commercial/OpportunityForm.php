<?php

namespace App\Livewire\Forms\Commercial;

use App\Livewire\Concerns\ValidatesCompanyId;
use App\Models\SalesOpportunity;
use Illuminate\Validation\Rule;
use Livewire\Form;

class OpportunityForm extends Form
{
    use ValidatesCompanyId;

    public string $companyId = '';

    public string $clientId = '';

    public string $projectId = '';

    public string $title = '';

    public string $stage = SalesOpportunity::STAGE_LEAD;

    public string $expectedValue = '0';

    public string $expectedCloseAt = '';

    public string $nextFollowUpAt = '';

    public string $source = '';

    public string $notes = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(array $stages): array
    {
        return [
            'companyId' => $this->companyIdRules(),
            'clientId' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where('company_id', (int) $this->companyId),
            ],
            'projectId' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('company_id', (int) $this->companyId),
            ],
            'title' => ['required', 'string', 'max:180'],
            'stage' => ['required', Rule::in($stages)],
            'expectedValue' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'expectedCloseAt' => ['nullable', 'date'],
            'nextFollowUpAt' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function resetForm(): void
    {
        $this->reset([
            'clientId',
            'projectId',
            'title',
            'expectedValue',
            'expectedCloseAt',
            'nextFollowUpAt',
            'source',
            'notes',
        ]);
        $this->stage = SalesOpportunity::STAGE_LEAD;
        $this->expectedValue = '0';
    }
}
