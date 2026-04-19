<?php

namespace App\Livewire\Forms;

use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ShiftForm extends Form
{
    public ?Shift $shift = null;

    public string $name = '';
    public ?string $start_time = null;
    public ?string $end_time = null;

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shifts')->ignore($this->shift),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function setShift(Shift $shift)
    {
        $this->shift = $shift;
        $this->name = $shift->name;
        $this->start_time = $this->normalizeTime($shift->start_time);
        $this->end_time = $this->normalizeTime($shift->end_time);
        return $this;
    }

    public function store()
    {
        if (Auth::user()->isNotAdmin) {
            return abort(403);
        }
        $this->validate();
        Shift::create($this->payload());
        $this->reset();
    }

    public function update()
    {
        if (Auth::user()->isNotAdmin) {
            return abort(403);
        }
        $this->validate();
        $this->shift->update($this->payload());
        $this->reset();
    }

    public function delete()
    {
        if (Auth::user()->isNotAdmin) {
            return abort(403);
        }
        $this->shift->delete();
        $this->reset();
    }

    protected function payload(): array
    {
        return [
            'name' => trim($this->name),
            'start_time' => $this->normalizeTime($this->start_time),
            'end_time' => $this->normalizeTime($this->end_time),
        ];
    }

    protected function normalizeTime(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return substr($time, 0, 5);
    }
}
