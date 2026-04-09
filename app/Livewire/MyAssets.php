<?php

namespace App\Livewire;

use App\Models\CompanyAsset;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyAssets extends Component
{
    public function render()
    {
        $assets = CompanyAsset::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('livewire.my-assets', compact('assets'));
    }
}
