<?php

namespace App\Livewire\Admin\MasterData;

use App\Models\LeaveType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LeaveTypeManager extends Component
{
    use InteractsWithBanner, WithPagination;

    public ?int $selectedId = null;

    public ?string $deleteName = null;

    public string $name = '';

    public string $description = '';

    public string $category = LeaveType::CATEGORY_OTHER;

    public bool $counts_against_quota = false;

    public bool $requires_attachment = false;

    public bool $is_active = true;

    public int $sort_order = 50;

    public bool $creating = false;

    public bool $editing = false;

    public bool $confirmingDeletion = false;

    public string $search = '';

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function boot(): void
    {
        Gate::authorize('manageLeaveTypes');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('leave_types', 'name')->ignore($this->selectedId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', Rule::in(array_keys(LeaveType::categories()))],
            'counts_against_quota' => ['boolean'],
            'requires_attachment' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->creating = true;
    }

    public function create(): void
    {
        $validated = $this->validatedPayload();

        LeaveType::create([
            ...$validated,
            'code' => $this->uniqueCode($validated['name']),
            'is_system' => false,
        ]);

        $this->creating = false;
        $this->resetForm();
        $this->banner(__('Created successfully.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $leaveType = LeaveType::query()->findOrFail($id);

        $this->selectedId = $leaveType->id;
        $this->name = $leaveType->name;
        $this->description = (string) $leaveType->description;
        $this->category = $leaveType->category;
        $this->counts_against_quota = $leaveType->counts_against_quota;
        $this->requires_attachment = $leaveType->requires_attachment;
        $this->is_active = $leaveType->is_active;
        $this->sort_order = $leaveType->sort_order;
        $this->creating = false;
        $this->editing = true;
    }

    public function update(): void
    {
        $leaveType = LeaveType::query()->findOrFail($this->selectedId);
        $leaveType->update($this->validatedPayload());

        $this->editing = false;
        $this->resetForm();
        $this->banner(__('Updated successfully.'));
    }

    public function confirmDeletion(int $id): void
    {
        $leaveType = LeaveType::query()->findOrFail($id);
        $this->selectedId = $leaveType->id;
        $this->deleteName = $leaveType->name;
        $this->confirmingDeletion = true;
    }

    public function delete(): void
    {
        $leaveType = LeaveType::query()->findOrFail($this->selectedId);

        if ($leaveType->is_system) {
            $this->addError('delete', __('System leave types cannot be deleted.'));

            return;
        }

        $leaveType->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
        $this->banner(__('Deleted successfully.'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(string $category): void
    {
        if ($category === LeaveType::CATEGORY_SICK) {
            $this->counts_against_quota = false;
            $this->requires_attachment = true;
        }

        if ($category === LeaveType::CATEGORY_ANNUAL) {
            $this->counts_against_quota = true;
        }
    }

    public function render()
    {
        $leaveTypes = LeaveType::query()
            ->when(filled($this->search), function ($query) {
                $search = trim($this->search);

                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->ordered()
            ->paginate($this->perPage);

        return view('livewire.admin.master-data.leave-types', [
            'leaveTypes' => $leaveTypes,
            'categories' => LeaveType::categories(),
        ]);
    }

    private function validatedPayload(): array
    {
        $payload = $this->validate();
        $payload['description'] = trim((string) $payload['description']) ?: null;

        if ($payload['category'] === LeaveType::CATEGORY_SICK) {
            $payload['counts_against_quota'] = false;
        }

        return $payload;
    }

    private function resetForm(): void
    {
        $this->selectedId = null;
        $this->name = '';
        $this->description = '';
        $this->category = LeaveType::CATEGORY_OTHER;
        $this->counts_against_quota = false;
        $this->requires_attachment = false;
        $this->is_active = true;
        $this->sort_order = 50;
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'leave_type';
        $code = $base;
        $suffix = 2;

        while (LeaveType::query()->where('code', $code)->exists()) {
            $code = "{$base}_{$suffix}";
            $suffix++;
        }

        return $code;
    }
}
