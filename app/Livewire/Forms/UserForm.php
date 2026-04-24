<?php

namespace App\Livewire\Forms;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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

    public $photo = null;

    public $basic_salary = 0;

    public $hourly_rate = 0;

    public array $role_ids = [];

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
            'address' => ['required', 'string', 'max:255'],
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
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'role_ids' => ['array'],
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
        $this->basic_salary = $user->basic_salary;
        $this->hourly_rate = $user->hourly_rate;
        $this->role_ids = $user->roles()->pluck('roles.id')->all();
        $this->original_role_ids = $this->role_ids;

        return $this;
    }

    public function store()
    {
        $this->authorizeMutation();
        $this->original_role_ids = [];
        $this->validate();
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

        if ($this->user !== null && auth()->id() === $this->user->id) {
            $requestedRoleIds = array_values(array_unique($this->role_ids));
            $originalRoleIds = array_values(array_unique($this->original_role_ids));
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
        $this->sanitize();

        $this->user->update([
            ...$this->payload(),
            'password' => $this->password ? Hash::make($this->password) : $this->user?->password,
        ]);
        $this->syncRoles($this->user);
        if (isset($this->photo)) {
            $this->user->updateProfilePhoto($this->photo);
        }
        $this->reset();
    }

    protected function sanitize()
    {
        $this->division_id = $this->division_id ?: null;
        $this->job_title_id = $this->job_title_id ?: null;
        $this->education_id = $this->education_id ?: null;
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

    private function payload(): array
    {
        $payload = $this->all();

        if (! $this->supportsCityColumn()) {
            unset($payload['city']);
        }

        unset($payload['role_ids'], $payload['original_role_ids']);

        return $payload;
    }

    private function syncRoles(User $subject): void
    {
        $requestedRoleIds = array_values(array_unique($this->role_ids));
        $originalRoleIds = array_values(array_unique($this->original_role_ids));

        if ($requestedRoleIds === $originalRoleIds) {
            return;
        }

        $actor = auth()->user();

        if (! $actor?->can('assignRoles')) {
            throw new AuthorizationException(__('You do not have permission to assign roles.'));
        }

        if ($actor->is($subject)) {
            throw new AuthorizationException(__('You cannot change your own role assignment.'));
        }

        $roles = Role::query()
            ->whereIn('id', $requestedRoleIds)
            ->get(['id', 'is_super_admin']);

        if ($roles->count() !== count($requestedRoleIds)) {
            throw new AuthorizationException(__('One or more selected roles are invalid.'));
        }

        if (! $actor->isSuperadmin && $roles->contains('is_super_admin', true)) {
            throw new AuthorizationException(__('Only super admins can assign the Super Admin role.'));
        }

        if (! $actor->isSuperadmin && $subject->isSuperadmin) {
            throw new AuthorizationException(__('Only super admins can manage Super Admin accounts.'));
        }

        $subject->roles()->sync($roles->pluck('id')->all());
        $this->original_role_ids = $requestedRoleIds;
    }
}
