<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Support\RbacRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;

class RolePermissionManager extends Component
{
    use InteractsWithBanner;

    public ?Role $editingRole = null;

    public ?string $deleteRoleId = null;

    public string $search = '';

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public array $permissions = [];

    public bool $showEditor = false;

    public bool $confirmingDeletion = false;

    public function boot(): void
    {
        Gate::authorize('manageRbac');
    }

    public function updatedName(string $value): void
    {
        if ($this->editingRole !== null) {
            return;
        }

        $this->slug = Str::slug($value, '_');
    }

    public function showCreate(): void
    {
        $this->resetEditor();
        $this->showEditor = true;
    }

    public function edit(string $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);

        $this->editingRole = $role;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->description = (string) $role->description;
        $this->permissions = $role->grantsFullAdminAccess()
            ? RbacRegistry::permissionKeys()
            : array_values(array_intersect(RbacRegistry::permissionKeys(), $role->permissions ?? []));
        $this->showEditor = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'slug')->ignore($this->editingRole),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(RbacRegistry::permissionKeys())],
        ]);

        $role = $this->editingRole ?? new Role;

        if ($role->grantsFullAdminAccess()) {
            $validated['permissions'] = RbacRegistry::permissionKeys();
        }

        $role->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?: null,
            'permissions' => array_values(array_unique($validated['permissions'] ?? [])),
        ])->save();

        $this->banner($this->editingRole ? __('Role updated successfully.') : __('Role created successfully.'));

        $this->resetEditor();
    }

    public function confirmDeletion(string $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);

        if ($role->is_system) {
            $this->dangerBanner(__('System roles cannot be deleted.'));

            return;
        }

        $this->deleteRoleId = $roleId;
        $this->confirmingDeletion = true;
    }

    public function deleteRole(): void
    {
        $role = Role::query()->findOrFail($this->deleteRoleId);

        if ($role->is_system) {
            $this->dangerBanner(__('System roles cannot be deleted.'));
            $this->confirmingDeletion = false;
            $this->deleteRoleId = null;

            return;
        }

        $role->users()->detach();
        $role->delete();

        $this->confirmingDeletion = false;
        $this->deleteRoleId = null;

        $this->banner(__('Role deleted successfully.'));
    }

    public function cancelEditor(): void
    {
        $this->resetEditor();
    }

    public function render()
    {
        $roles = Role::query()
            ->withCount('users')
            ->when($this->search !== '', function ($query) {
                $term = '%'.trim($this->search).'%';

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->get()
            ->sort(fn (Role $left, Role $right) => [
                $left->grantsFullAdminAccess() ? 0 : 1,
                $left->is_system ? 0 : 1,
                $left->name,
            ] <=> [
                $right->grantsFullAdminAccess() ? 0 : 1,
                $right->is_system ? 0 : 1,
                $right->name,
            ])
            ->values();

        return view('livewire.admin.role-permission-manager', [
            'roles' => $roles,
            'groupedModules' => RbacRegistry::groupedModules(),
            'allPermissions' => RbacRegistry::permissionKeys(),
        ])->layout('layouts.app');
    }

    private function resetEditor(): void
    {
        $this->resetErrorBag();
        $this->editingRole = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->permissions = [];
        $this->showEditor = false;
    }
}
