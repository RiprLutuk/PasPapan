<?php

namespace App\Livewire\Admin;

use App\Models\Barcode;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;

class BarcodeComponent extends Component
{
    use InteractsWithBanner;

    public string $search = '';
    public string $modeFilter = 'all';

    public $deleteName = null;
    public $confirmingDeletion = false;
    public $selectedId = null;

    public function updatingSearch(): void
    {
        //
    }

    public function confirmDeletion($id, $name)
    {
        $this->deleteName = $name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete()
    {
        Gate::authorize('manageBarcodes');
        $barcode = Barcode::find($this->selectedId);
        $barcode->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->banner(__('Deleted successfully.'));
    }

    public function render()
    {
        $barcodes = Barcode::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('latitude', 'like', '%' . $this->search . '%')
                        ->orWhere('longitude', 'like', '%' . $this->search . '%')
                        ->orWhere('value', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->modeFilter !== 'all', function ($query) {
                $query->where('dynamic_enabled', $this->modeFilter === 'dynamic');
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.barcode', [
            'barcodes' => $barcodes
        ]);
    }
}
