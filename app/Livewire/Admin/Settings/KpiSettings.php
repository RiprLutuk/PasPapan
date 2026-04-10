<?php

namespace App\Livewire\Admin\Settings;

use App\Models\KpiTemplate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class KpiSettings extends Component
{
    public $kpis = [];
    public $name = '';
    public $weight = 0;
    public $is_active = true;
    public $editId = null;

    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'weight' => 'required|integer|min:1|max:100',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->loadKpis();
    }

    public function loadKpis()
    {
        $this->kpis = KpiTemplate::orderBy('id')->get();
    }

    public function create()
    {
        $this->reset(['name', 'weight', 'is_active', 'editId']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $kpi = KpiTemplate::findOrFail($id);
        $this->editId = $kpi->id;
        $this->name = $kpi->name;
        $this->weight = $kpi->weight;
        $this->is_active = $kpi->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editId) {
            KpiTemplate::findOrFail($this->editId)->update([
                'name' => $this->name,
                'weight' => $this->weight,
                'is_active' => $this->is_active,
            ]);
            session()->flash('success', __('KPI updated successfully.'));
        } else {
            KpiTemplate::create([
                'name' => $this->name,
                'weight' => $this->weight,
                'is_active' => $this->is_active,
            ]);
            session()->flash('success', __('KPI added successfully.'));
        }

        $this->showModal = false;
        $this->loadKpis();
    }

    public function delete($id)
    {
        KpiTemplate::destroy($id);
        $this->loadKpis();
        session()->flash('success', __('KPI deleted successfully.'));
    }

    public function toggleActive($id)
    {
        $kpi = KpiTemplate::findOrFail($id);
        $kpi->update(['is_active' => !$kpi->is_active]);
        $this->loadKpis();
    }

    public function render()
    {
        $totalWeight = $this->kpis->where('is_active', true)->sum('weight');
        return view('livewire.admin.settings.kpi-settings', compact('totalWeight'));
    }
}
