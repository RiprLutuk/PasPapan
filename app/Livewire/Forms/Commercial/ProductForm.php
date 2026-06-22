<?php

namespace App\Livewire\Forms\Commercial;

use App\Livewire\Concerns\ValidatesCompanyId;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProductForm extends Form
{
    use ValidatesCompanyId;

    public ?int $productId = null;

    public string $status = 'active';

    public string $companyId = '';

    public string $name = '';

    public string $sku = '';

    public string $unit = 'pcs';

    public string $sellingPrice = '0';

    public string $costPrice = '0';

    public string $reorderPoint = '0';

    public function rules(): array
    {
        return [
            'companyId' => $this->companyIdRules(),
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:80'],
            'unit' => ['required', 'string', 'max:32'],
            'sellingPrice' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'costPrice' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'reorderPoint' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    public function resetForm(): void
    {
        $this->reset(['productId', 'name', 'sku', 'sellingPrice', 'costPrice', 'reorderPoint']);
        $this->status = 'active';
        $this->sellingPrice = '0';
        $this->costPrice = '0';
        $this->reorderPoint = '0';
    }
}
