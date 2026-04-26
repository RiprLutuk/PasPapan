<?php

namespace App\Livewire\Forms;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public $name = '';

    public $nip = '';

    public $email = '';

    public $phone = '';

    public $password = null;

    public $gender = null;

    public $address = '';

    public $city = '';

    public $provinsi_kode = null;

    public $kabupaten_kode = null;

    public $kecamatan_kode = null;

    public $kelurahan_kode = null;

    public $group = 'user';

    public $birth_date = null;

    public $birth_place = '';

    public $division_id = null;

    public $education_id = null;

    public $job_title_id = null;

    public $manager_id = null;

    public $photo = null;

    public $basic_salary = 0;

    public $hourly_rate = 0;

    public $employment_status = User::EMPLOYMENT_STATUS_ACTIVE;

    public array $role_ids = [];

    public ?string $role_id = null;

    public ?string $original_role_id = null;

    protected array $original_role_ids = [];

    public function rules()
    {
        $requiredOrNullable = $this->group === 'user' ? 'required' : 'nullable';
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'nip' => [$requiredOrNullable, 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user),
            ],
            'phone' => ['required',  'string', 'min:5', 'max:255'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'address' => [$requiredOrNullable, 'string', 'max:255'],
            'provinsi_kode' => [$requiredOrNullable, 'string', 'max:13'],
            'kabupaten_kode' => [$requiredOrNullable, 'string', 'max:13'],
            'kecamatan_kode' => [$requiredOrNullable, 'string', 'max:13'],
            'kelurahan_kode' => [$requiredOrNullable, 'string', 'max:13'],
            'group' => ['nullable', 'string', 'max:255', Rule::in(User::$groups)],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'education_id' => ['nullable', 'exists:educations,id'],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
            'manager_id' => [
                'nullable',
                'string',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('group', 'user')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->user !== null && $value === $this->user->id) {
                        $fail(__('An employee cannot be their own direct manager.'));
                    }
                },
            ],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'employment_status' => ['required', 'string', Rule::in(array_keys(User::employmentStatuses()))],
            'role_id' => ['nullable', 'string', 'exists:roles,id'],
            'role_ids' => ['array', 'max:1'],
            'role_ids.*' => ['string', 'exists:roles,id'],
        ];

        if ($this->supportsCityColumn()) {
            $rules['city'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->nip = $user->nip;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->password = null;
        $this->gender = $user->gender;
        $this->address = $user->address;
        $this->city = $this->supportsCityColumn()
            ? (string) $user->getAttribute('city')
            : '';
        $this->provinsi_kode = $user->provinsi_kode;
        $this->kabupaten_kode = $user->kabupaten_kode;
        $this->kecamatan_kode = $user->kecamatan_kode;
        $this->kelurahan_kode = $user->kelurahan_kode;
        $this->group = $user->group;
        $this->birth_date = $user->birth_date
            ? \Illuminate\Support\Carbon::parse($user->birth_date)->format('Y-m-d')
            : null;
        $this->birth_place = $user->birth_place;
        $this->division_id = $user->division_id;
        $this->education_id = $user->education_id;
        $this->job_title_id = $user->job_title_id;
        $this->manager_id = $user->manager_id;
        $this->basic_salary = $user->basic_salary;
        $this->hourly_rate = $user->hourly_rate;
        $this->employment_status = $user->employment_status ?: User::EMPLOYMENT_STATUS_ACTIVE;
        $this->role_ids = $user->roles()
            ->orderByDesc('roles.is_super_admin')
            ->orderBy('roles.name')
            ->pluck('roles.id')
            ->take(1)
            ->all();
        $this->role_id = $this->role_ids[0] ?? null;
        $this->original_role_id = $this->role_id;
        $this->original_role_ids = $this->role_ids;

        return $this;
    }

    public function store()
    {
        $this->authorizeMutation();
        $this->authorizeEmploymentStatusChange(null);
        $this->original_role_ids = [];
        $this->normalizeSingleRoleSelection();
        $this->validate();
        $this->ensureManagerDoesNotCreateCycle();
        $this->sanitize();

        /** @var User $user */
        $user = User::create([
            ...$this->payload(),
            'password' => Hash::make($this->password ?? 'password'),
        ]);
        $this->syncRoles($user);
        if (isset($this->photo)) {
            $user->updateProfilePhoto($this->photo);
        }
        $this->reset();
    }

    public function update()
    {
        $this->authorizeMutation();
        $this->authorizeEmploymentStatusChange($this->user);
        $this->normalizeSingleRoleSelection();

        if ($this->user !== null && auth()->id() === $this->user->id) {
            if ($this->group !== $this->user->group) {
                throw new AuthorizationException(__('You cannot change your own account group.'));
            }

            $requestedRoleIds = array_values(array_unique($this->role_ids));
            $originalRoleIds = $this->user
                ->roles()
                ->pluck('roles.id')
                ->all();
            sort($requestedRoleIds);
            sort($originalRoleIds);

            if ($requestedRoleIds !== $originalRoleIds) {
                throw new AuthorizationException(__('You cannot change your own role assignment.'));
            }
        }

        // Demo User Protection: Cannot update password of Demo User
        if ($this->user->is_demo && $this->password) {
            $this->addError('password', 'Demo user password cannot be changed.');

            return;
        }
        $this->validate();
        $this->ensureManagerDoesNotCreateCycle();
        $newPassword = filled($this->password) ? (string) $this->password : null;
        $this->sanitize();

        $payload = $this->payload();
        unset($payload['password']);

        $this->user->update($payload);

        $this->syncRoles($this->user);

        if ($newPassword !== null) {
            $this->user->forceFill([
                'password' => Hash::make($newPassword),
            ])->save();
        }

        if (isset($this->photo)) {
            $this->user->updateProfilePhoto($this->photo);
        }
        $this->reset();
    }

    protected function sanitize()
    {
        $this->division_id = $this->division_id ?: null;
        $this->job_title_id = $this->job_title_id ?: null;
        $this->manager_id = $this->manager_id ?: null;
        $this->education_id = $this->education_id ?: null;
        $this->employment_status = $this->employment_status ?: User::EMPLOYMENT_STATUS_ACTIVE;
        $this->provinsi_kode = $this->provinsi_kode ?: null;
        $this->kabupaten_kode = $this->kabupaten_kode ?: null;
        $this->kecamatan_kode = $this->kecamatan_kode ?: null;
        $this->kelurahan_kode = $this->kelurahan_kode ?: null;
        $this->birth_date = $this->birth_date ?: null;
        if ($this->supportsCityColumn()) {
            $this->city = trim((string) $this->city);
        }
        $this->address = trim((string) $this->address);
        $this->birth_place = trim((string) $this->birth_place);
    }

    public function supportsCityColumn(): bool
    {
        static $supportsCity;

        return $supportsCity ??= Schema::hasColumn('users', 'city');
    }

    public function deleteProfilePhoto()
    {
        $this->authorizeMutation();

        return $this->user->deleteProfilePhoto();
    }

    public function delete()
    {
        $this->authorizeMutation();
        $this->user->delete();
        $this->deleteProfilePhoto();
        $this->reset();
    }

    private function authorizeMutation(): void
    {
        Gate::authorize('manageUserRecord', [$this->user, $this->group]);
    }

    private function authorizeEmploymentStatusChange(?User $subject): void
    {
        $currentStatus = $subject?->employment_status ?: User::EMPLOYMENT_STATUS_ACTIVE;
        $requestedStatus = $this->employment_status ?: User::EMPLOYMENT_STATUS_ACTIVE;

        if ($subject !== null && in_array($currentStatus, [
            User::EMPLOYMENT_STATUS_DELETION_REQUESTED,
            User::EMPLOYMENT_STATUS_DELETED,
        ], true) && $requestedStatus !== $currentStatus) {
            throw ValidationException::withMessages([
                'form.employment_status' => __('Use the account deletion review action to resolve deletion requests.'),
            ]);
        }

        if ($requestedStatus !== $currentStatus && ! $subject?->canTransitionEmploymentStatusTo($requestedStatus) && ! in_array($requestedStatus, User::manuallyManagedEmploymentStatuses(), true)) {
            throw ValidationException::withMessages([
                'form.employment_status' => __('This employee status must be managed through the account lifecycle flow.'),
            ]);
        }

        if ($requestedStatus !== $currentStatus && ! Gate::allows('manageEmployeeStatuses')) {
            throw new AuthorizationException(__('You do not have permission to manage employee status.'));
        }
    }

    private function payload(): array
    {
        $payload = $this->all();

        if (! $this->supportsCityColumn()) {
            unset($payload['city']);
        }

        unset($payload['role_id'], $payload['original_role_id'], $payload['role_ids'], $payload['original_role_ids']);

        return $payload;
    }

    private function ensureManagerDoesNotCreateCycle(): void
    {
        if ($this->user === null || blank($this->manager_id)) {
            return;
        }

        $visited = [];
        $manager = User::query()
            ->select(['id', 'manager_id'])
            ->find($this->manager_id);

        while ($manager !== null) {
            if ($manager->id === $this->user->id) {
                throw ValidationException::withMessages([
                    'form.manager_id' => __('This direct manager would create a circular reporting line.'),
                ]);
            }

            if (blank($manager->manager_id) || in_array($manager->id, $visited, true)) {
                return;
            }

            $visited[] = $manager->id;
            $manager = User::query()
                ->select(['id', 'manager_id'])
                ->find($manager->manager_id);
        }
    }

    private function normalizeSingleRoleSelection(): void
    {
        $selectedRoleIds = array_values(array_unique(array_filter($this->role_ids)));
        $firstRoleId = $selectedRoleIds[0] ?? null;
        $roleIdChanged = $this->role_id !== $this->original_role_id;
        $selectedRoleId = $roleIdChanged
            ? $this->role_id
            : ($firstRoleId ?: $this->role_id);

        $this->role_id = filled($selectedRoleId) ? (string) $selectedRoleId : null;
        $this->role_ids = $this->role_id ? [$this->role_id] : [];
    }

    private function syncRoles(User $subject): void
    {
        $requestedRoleIds = $this->normalizeRequestedRoleIds($subject, array_values(array_unique($this->role_ids)));
        $actor = auth()->user();
        $originalRoleIds = $actor?->is($subject)
            ? $subject->roles()->pluck('roles.id')->all()
            : array_values(array_unique($this->original_role_ids));
        $usingImplicitDefaultRole = $this->role_ids === [] && $originalRoleIds === [];

        if ($requestedRoleIds === $originalRoleIds) {
            return;
        }

        if (! $usingImplicitDefaultRole && ! $actor?->can('assignRoles')) {
            throw new AuthorizationException(__('You do not have permission to assign roles.'));
        }

        if (! $usingImplicitDefaultRole && $actor->is($subject)) {
            throw new AuthorizationException(__('You cannot change your own role assignment.'));
        }

        $roles = Role::query()
            ->whereIn('id', $requestedRoleIds)
            ->get();

        if ($roles->count() !== count($requestedRoleIds)) {
            throw new AuthorizationException(__('One or more selected roles are invalid.'));
        }

        $grantsFullAdminAccess = $roles->contains(fn (Role $role) => $role->grantsFullAdminAccess());

        if ($grantsFullAdminAccess && ! $actor->canManageSuperadminAccounts()) {
            throw new AuthorizationException(__('You do not have permission to assign the Super Admin role.'));
        }

        if ($subject->isSuperadmin && ! $actor->canManageSuperadminAccounts()) {
            throw new AuthorizationException(__('You do not have permission to manage Super Admin accounts.'));
        }

        $subject->roles()->sync($roles->pluck('id')->all());
        $this->synchronizeSubjectGroup($subject, $grantsFullAdminAccess);
        $this->role_ids = $roles->pluck('id')->all();
        $this->original_role_ids = $requestedRoleIds;
    }

    private function normalizeRequestedRoleIds(User $subject, array $requestedRoleIds): array
    {
        if ($requestedRoleIds !== [] || ! in_array($subject->group, ['admin', 'superadmin'], true)) {
            return $requestedRoleIds;
        }

        $defaultRoleSlug = $subject->group === 'superadmin' ? 'super_admin' : 'admin';
        $defaultRoleId = Role::query()->where('slug', $defaultRoleSlug)->value('id');

        if (! is_string($defaultRoleId) || $defaultRoleId === '') {
            throw new AuthorizationException(__('The default :group role is missing.', ['group' => $subject->group]));
        }

        return [$defaultRoleId];
    }

    private function synchronizeSubjectGroup(User $subject, bool $grantsFullAdminAccess): void
    {
        if ($subject->group === 'user') {
            return;
        }

        $resolvedGroup = $grantsFullAdminAccess ? 'superadmin' : 'admin';

        if ($subject->group === $resolvedGroup) {
            return;
        }

        $subject->forceFill(['group' => $resolvedGroup])->save();
        $this->group = $resolvedGroup;
    }
}
