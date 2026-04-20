<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

    public function rules()
    {
        $requiredOrNullable = $this->group === 'user' ? 'required' : 'nullable';
        return [
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
                Rule::unique('users')->ignore($this->user)
            ],
            'phone' => ['required',  'string', 'min:5', 'max:255'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'gender' => [$requiredOrNullable, 'in:male,female'],
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
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ];
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
        return $this;
    }

    public function store()
    {
        $this->authorizeMutation();
        $this->validate();
        $this->sanitize();

        /** @var User $user */
        $user = User::create([
            ...$this->all(),
            'password' => Hash::make($this->password ?? 'password'),
        ]);
        if (isset($this->photo)) $user->updateProfilePhoto($this->photo);
        $this->reset();
    }

    public function update()
    {
        $this->authorizeMutation();

        // Demo User Protection: Cannot update password of Demo User
        if ($this->user->is_demo && $this->password) {
            $this->addError('password', 'Demo user password cannot be changed.');
            return;
        }
        $this->validate();
        $this->sanitize();

        $this->user->update([
            ...$this->all(),
            'password' => $this->password ? Hash::make($this->password) : $this->user?->password,
        ]);
        if (isset($this->photo)) $this->user->updateProfilePhoto($this->photo);
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
}
