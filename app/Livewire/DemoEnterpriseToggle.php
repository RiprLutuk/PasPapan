<?php

namespace App\Livewire;

use Livewire\Component;

class DemoEnterpriseToggle extends Component
{
    public bool $isEnterprise = false;

    public function mount()
    {
        $this->isEnterprise = session('demo_enterprise_mode', false);
    }

    public function toggle()
    {
        $this->isEnterprise = ! $this->isEnterprise;
        session(['demo_enterprise_mode' => $this->isEnterprise]);

        return redirect(request()->header('Referer', '/'));
    }

    public function render()
    {
        return view('livewire.demo-enterprise-toggle');
    }
}
