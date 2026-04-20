<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EmployeeComponent extends Component
{
    use WithPagination, InteractsWithBanner, WithFileUploads;

    public UserForm $form;
    public $deleteName = null;
    public $creating = false;
    public $editing = false;
    public $confirmingDeletion = false;
    public $selectedId = null;
    public $showDetail = null;

    # filter
    public ?string $division = null;
    public ?string $jobTitle = null;
    public ?string $education = null;
    public ?string $search = null;

    public function show($id)
    {
        $this->form->setUser(User::find($id));
        $this->showDetail = true;
    }

    public function showCreating()
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->creating = true;
        $this->form->password = 'password';
    }

    public function create()
    {
        $this->form->store();
        $this->creating = false;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->editing = true;
        /** @var User $user */
        $user = User::find($id);
        $this->form->setUser($user);
    }

    public function update()
    {
        $this->form->update();
        $this->editing = false;
        $this->banner(__('Updated successfully.'));
    }

    public function deleteProfilePhoto()
    {
        $this->form->deleteProfilePhoto();
    }

    public function confirmDeletion($id)
    {
        $user = User::findOrFail($id);
        $this->deleteName = $user->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $user->id;
    }

    public function delete()
    {
        $user = User::find($this->selectedId);
        $this->form->setUser($user)->delete();
        $this->confirmingDeletion = false;
        $this->banner(__('Deleted successfully.'));
    }

    public function updated($property, $value)
    {
        if ($property === 'form.job_title_id' && $value) {
            $jobTitle = \App\Models\JobTitle::find($value);
            if ($jobTitle && $jobTitle->division_id) {
                $this->form->division_id = $jobTitle->division_id;
            }
        }

        if ($property === 'form.division_id') {
            $this->form->job_title_id = null;
        }

        if ($property === 'form.provinsi_kode') {
            $this->form->kabupaten_kode = null;
            $this->form->kecamatan_kode = null;
            $this->form->kelurahan_kode = null;
        }
        if ($property === 'form.kabupaten_kode') {
            $this->form->kecamatan_kode = null;
            $this->form->kelurahan_kode = null;
        }
        if ($property === 'form.kecamatan_kode') {
            $this->form->kelurahan_kode = null;
        }
    }

    public function render()
    {
        $users = User::where('group', 'user')
            ->managedBy(auth()->user())
            ->when($this->search, function (Builder $q) {
                $q->where(function ($subQ) {
                    $subQ->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('nip', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->division, fn(Builder $q) => $q->where('division_id', $this->division))
            ->when($this->jobTitle, fn(Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->when($this->education, fn(Builder $q) => $q->where('education_id', $this->education))
            ->with(['division', 'jobTitle', 'education'])
            ->orderBy('name')
            ->paginate(20);

        $availableJobTitles = \App\Models\JobTitle::query()
            ->when($this->form->division_id, function ($q) {
                $q->where('division_id', $this->form->division_id)
                    ->orWhereNull('division_id'); // Include global titles if any
            })
            ->get();

        $provinces = \App\Models\Wilayah::whereRaw('LENGTH(kode) = 2')->orderBy('nama')->get();
        $regencies = $this->form->provinsi_kode ? \App\Models\Wilayah::where('kode', 'like', $this->form->provinsi_kode . '.%')->whereRaw('LENGTH(kode) = 5')->orderBy('nama')->get() : collect();
        $districts = $this->form->kabupaten_kode ? \App\Models\Wilayah::where('kode', 'like', $this->form->kabupaten_kode . '.%')->whereRaw('LENGTH(kode) = 8')->orderBy('nama')->get() : collect();
        $villages = $this->form->kecamatan_kode ? \App\Models\Wilayah::where('kode', 'like', $this->form->kecamatan_kode . '.%')->whereRaw('LENGTH(kode) = 13')->orderBy('nama')->get() : collect();

        return view('livewire.admin.employees', [
            'users' => $users,
            'availableJobTitles' => $availableJobTitles,
            'provinces' => $provinces,
            'regencies' => $regencies,
            'districts' => $districts,
            'villages' => $villages,
        ]);
    }
}
