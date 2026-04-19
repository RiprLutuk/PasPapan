<?php

namespace App\Livewire\Admin;

use App\Models\PayrollComponent;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollSettings extends Component
{
    use WithPagination;

    public string $search = '';
    public string $typeFilter = 'all';
    public string $activeFilter = 'all';
    public int $perPage = 10;
    public bool $showModal = false;
    public bool $confirmingDeletion = false;
    public $selectedId;

    // Form Fields
    public $name;
    public $type = 'allowance';
    public $calculation_type = 'fixed';
    public $amount;
    public $percentage;
    public $is_taxable = false;
    public $is_active = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'type' => 'required|in:allowance,deduction',
        'calculation_type' => 'required|in:fixed,percentage_basic,daily_presence',
        'amount' => 'nullable|numeric|min:0',
        'percentage' => 'nullable|numeric|min:0|max:100',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
        'activeFilter' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $components = PayrollComponent::query()
            ->when(filled($this->search), function ($query) {
                $term = '%' . trim($this->search) . '%';

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('name', 'like', $term)
                        ->orWhere('type', 'like', $term)
                        ->orWhere('calculation_type', 'like', $term);
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->activeFilter !== 'all', function ($query) {
                $query->where('is_active', $this->activeFilter === 'active');
            })
            ->orderBy('type')
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.payroll-settings', [
            'components' => $components
        ])->layout('layouts.app');
    }

    public function create()
    {
        $this->reset(['name', 'type', 'calculation_type', 'amount', 'percentage', 'is_taxable', 'is_active', 'selectedId']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $component = PayrollComponent::findOrFail($id);
        $this->selectedId = $id;
        $this->name = $component->name;
        $this->type = $component->type;
        $this->calculation_type = $component->calculation_type;
        $this->amount = $component->amount;
        $this->percentage = $component->percentage;
        $this->is_taxable = $component->is_taxable;
        $this->is_active = $component->is_active;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        // Business Logic Validation
        if ($this->calculation_type === 'percentage_basic' && empty($this->percentage)) {
             $this->addError('percentage', 'Percentage is required for percentage calculation.');
             return;
        }
        if (in_array($this->calculation_type, ['fixed', 'daily_presence']) && empty($this->amount)) {
             $this->addError('amount', 'Amount is required for this calculation type.');
             return;
        }

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'calculation_type' => $this->calculation_type,
            'amount' => in_array($this->calculation_type, ['fixed', 'daily_presence']) ? $this->amount : null,
            'percentage' => $this->calculation_type === 'percentage_basic' ? $this->percentage : null,
            'is_taxable' => $this->is_taxable,
            'is_active' => $this->is_active,
        ];

        if ($this->selectedId) {
            PayrollComponent::find($this->selectedId)->update($data);
            session()->flash('success', 'Component updated successfully.');
        } else {
            PayrollComponent::create($data);
            session()->flash('success', 'Component created successfully.');
        }

        $this->showModal = false;
        $this->reset(['name', 'type', 'calculation_type', 'amount', 'percentage', 'is_taxable', 'is_active', 'selectedId']);
    }

    public function confirmDelete($id)
    {
        $this->selectedId = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        PayrollComponent::find($this->selectedId)->delete();
        $this->confirmingDeletion = false;
        $this->reset(['selectedId']);
        session()->flash('success', 'Component deleted successfully.');
    }

    public function toggleActive($id)
    {
        $comp = PayrollComponent::findOrFail($id);
        $comp->update(['is_active' => !$comp->is_active]);
    }
}
