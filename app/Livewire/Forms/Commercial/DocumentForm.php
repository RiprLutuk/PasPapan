<?php

namespace App\Livewire\Forms\Commercial;

use App\Livewire\Concerns\ValidatesCompanyId;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DocumentForm extends Form
{
    use ValidatesCompanyId;

    public string $companyId = '';

    public string $clientId = '';

    public string $projectId = '';

    public string $productId = '';

    public string $description = '';

    public string $quantity = '1';

    public string $unitPrice = '0';

    public string $taxRate = '11';

    public string $notes = '';

    public function rules(): array
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
            'productId' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('company_id', (int) $this->companyId),
            ],
            'description' => ['required', 'string', 'max:180'],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:999999999'],
            'unitPrice' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'taxRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function resetForm(): void
    {
        $this->reset(['projectId', 'productId', 'description', 'quantity', 'unitPrice', 'taxRate', 'notes']);
        $this->quantity = '1';
        $this->unitPrice = '0';
        $this->taxRate = '11';
    }
}
