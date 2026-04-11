<?php

namespace App\Livewire\Admin\Settings;

use App\Models\KpiGroup;
use App\Models\KpiTemplate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class KpiSettings extends Component
{
    // Group properties
    public $groups = [];
    public $groupName = '';
    public $groupWeight = 0;
    public $groupIsActive = true;
    public $editGroupId = null;
    public $showGroupModal = false;

    // Template (child) properties
    public $name = '';
    public $indicator_description = '';
    public $weight = 0;
    public $is_active = true;
    public $kpi_group_id = null; // parent group for the template
    public $editId = null;
    public $showModal = false;

    // Period Lock fields
    public $periodOpen = false;
    public $periodLabel = '';
    public $periodDeadline = '';

    // Advanced Evaluation Settings
    public $attendanceWeight = 30;

    public function mount()
    {
        $this->loadGroups();
        $this->periodOpen = (bool) \App\Models\Setting::getValue('appraisal.period_open', false);
        $this->periodLabel = \App\Models\Setting::getValue('appraisal.period_label', '');
        $this->periodDeadline = \App\Models\Setting::getValue('appraisal.period_deadline', '');
        $this->attendanceWeight = (int) \App\Models\Setting::getValue('appraisal.attendance_weight', 30);
    }

    public function loadGroups()
    {
        $this->groups = KpiGroup::with('kpiTemplates')->orderBy('sort_order')->get();
    }

    // ─── GROUP CRUD ───

    public function createGroup()
    {
        $this->reset(['groupName', 'groupWeight', 'groupIsActive', 'editGroupId']);
        $this->groupIsActive = true;
        $this->showGroupModal = true;
    }

    public function editGroup($id)
    {
        $group = KpiGroup::findOrFail($id);
        $this->editGroupId = $group->id;
        $this->groupName = $group->name;
        $this->groupWeight = $group->weight;
        $this->groupIsActive = $group->is_active;
        $this->showGroupModal = true;
    }

    public function saveGroup()
    {
        $this->validate([
            'groupName' => 'required|string|max:255',
            'groupWeight' => 'required|integer|min:0|max:100',
            'groupIsActive' => 'boolean',
        ]);

        if ($this->editGroupId) {
            KpiGroup::findOrFail($this->editGroupId)->update([
                'name' => $this->groupName,
                'weight' => $this->groupWeight,
                'is_active' => $this->groupIsActive,
            ]);
            $this->dispatch('success', __('Kategori KPI berhasil diperbarui.'));
        } else {
            $maxSort = KpiGroup::max('sort_order') ?? 0;
            KpiGroup::create([
                'name' => $this->groupName,
                'weight' => $this->groupWeight,
                'is_active' => $this->groupIsActive,
                'sort_order' => $maxSort + 1,
            ]);
            $this->dispatch('success', __('Kategori KPI baru berhasil ditambahkan.'));
        }

        $this->showGroupModal = false;
        $this->loadGroups();
    }

    public function deleteGroup($id)
    {
        $group = KpiGroup::findOrFail($id);
        if ($group->kpiTemplates()->count() > 0) {
            $this->dispatch('error', __('Hapus semua komponen KPI di dalam kategori ini terlebih dahulu.'));
            return;
        }
        $group->delete();
        $this->loadGroups();
        $this->dispatch('success', __('Kategori KPI berhasil dihapus.'));
    }

    // ─── TEMPLATE (CHILD) CRUD ───

    public function createTemplate($groupId)
    {
        $this->reset(['name', 'indicator_description', 'weight', 'is_active', 'editId']);
        $this->is_active = true;
        $this->kpi_group_id = $groupId;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $kpi = KpiTemplate::findOrFail($id);
        $this->editId = $kpi->id;
        $this->kpi_group_id = $kpi->kpi_group_id;
        $this->name = $kpi->name;
        $this->indicator_description = $kpi->indicator_description;
        $this->weight = $kpi->weight;
        $this->is_active = $kpi->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'indicator_description' => 'nullable|string',
            'weight' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        if ($this->editId) {
            KpiTemplate::findOrFail($this->editId)->update([
                'kpi_group_id' => $this->kpi_group_id,
                'name' => $this->name,
                'indicator_description' => $this->indicator_description,
                'weight' => $this->weight,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('success', __('Komponen KPI berhasil diperbarui.'));
        } else {
            KpiTemplate::create([
                'kpi_group_id' => $this->kpi_group_id,
                'name' => $this->name,
                'indicator_description' => $this->indicator_description,
                'weight' => $this->weight,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('success', __('Komponen KPI baru berhasil ditambahkan.'));
        }

        $this->showModal = false;
        $this->loadGroups();
    }

    public function delete($id)
    {
        KpiTemplate::destroy($id);
        $this->loadGroups();
        $this->dispatch('success', __('Komponen KPI berhasil dihapus.'));
    }

    public function toggleActive($id)
    {
        $kpi = KpiTemplate::findOrFail($id);
        $kpi->update(['is_active' => !$kpi->is_active]);
        $this->loadGroups();
    }

    // ─── PERIOD LOCK ───

    public function savePeriodLock()
    {
        $this->validate([
            'periodLabel' => 'required|string|max:255',
            'periodDeadline' => 'required|date',
        ]);

        $this->updateSetting('appraisal.period_open', $this->periodOpen ? '1' : '0');
        $this->updateSetting('appraisal.period_label', $this->periodLabel);
        $this->updateSetting('appraisal.period_deadline', $this->periodDeadline);

        $this->dispatch('success', __('Pengaturan periode penilaian berhasil disimpan.'));
    }

    public function updatedAttendanceWeight()
    {
        $this->saveEvaluationSettings();
    }

    public function saveEvaluationSettings()
    {
        $this->validate([
            'attendanceWeight' => 'required|integer|min:0|max:100',
        ]);

        $this->updateSetting('appraisal.attendance_weight', $this->attendanceWeight);
        $this->dispatch('success', __('Bobot kehadiran berhasil diperbarui.'));
    }

    public function togglePeriodLock()
    {
        $this->periodOpen = !$this->periodOpen;
        $this->updateSetting('appraisal.period_open', $this->periodOpen ? '1' : '0');
        $this->dispatch('success', $this->periodOpen ? __('Jendela penilaian TERBUKA.') : __('Jendela penilaian DITUTUP.'));
    }

    private function updateSetting($key, $value)
    {
        \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        \Illuminate\Support\Facades\Cache::forget("setting.{$key}");
    }

    public function render()
    {
        $totalGroupWeight = $this->groups->where('is_active', true)->sum('weight');
        return view('livewire.admin.settings.kpi-settings', compact('totalGroupWeight'));
    }
}
