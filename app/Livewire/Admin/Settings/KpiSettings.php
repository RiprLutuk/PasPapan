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

    // Period Lock fields
    public $periodOpen = false;
    public $periodLabel = '';
    public $periodDeadline = '';

    // Advanced Evaluation Settings
    public $attendanceWeight = 30;

    protected $rules = [
        'name' => 'required|string|max:255',
        'weight' => 'required|integer|min:1|max:100',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->loadKpis();
        $this->periodOpen = (bool) \App\Models\Setting::getValue('appraisal.period_open', false);
        $this->periodLabel = \App\Models\Setting::getValue('appraisal.period_label', '');
        $this->periodDeadline = \App\Models\Setting::getValue('appraisal.period_deadline', '');
        $this->attendanceWeight = (int) \App\Models\Setting::getValue('appraisal.attendance_weight', 30);
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

    public function savePeriodLock()
    {
        $this->validate([
            'periodLabel' => 'required|string|max:255',
            'periodDeadline' => 'required|date',
        ]);

        $this->updateSetting('appraisal.period_open', $this->periodOpen ? '1' : '0');
        $this->updateSetting('appraisal.period_label', $this->periodLabel);
        $this->updateSetting('appraisal.period_deadline', $this->periodDeadline);

        session()->flash('success', __('Appraisal period settings updated.'));
    }

    public function saveEvaluationSettings()
    {
        $this->validate([
            'attendanceWeight' => 'required|integer|min:0|max:100',
        ]);

        $this->updateSetting('appraisal.attendance_weight', $this->attendanceWeight);
        session()->flash('success', __('System attendance evaluation weight updated.'));
    }

    public function togglePeriodLock()
    {
        $this->periodOpen = !$this->periodOpen;
        $this->updateSetting('appraisal.period_open', $this->periodOpen ? '1' : '0');
        session()->flash('success', $this->periodOpen ? __('Appraisal window is now OPEN.') : __('Appraisal window is now CLOSED.'));
    }

    private function updateSetting($key, $value)
    {
        \App\Models\Setting::where('key', $key)->update(['value' => $value]);
        \Illuminate\Support\Facades\Cache::forget("setting.{$key}");
    }

    public function render()
    {
        $totalWeight = $this->kpis->where('is_active', true)->sum('weight');
        return view('livewire.admin.settings.kpi-settings', compact('totalWeight'));
    }
}
