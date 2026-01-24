<?php

namespace App\Livewire;

use App\Models\Payroll;
use App\Models\User;
use App\Contracts\PayrollServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollManager extends Component
{
    use WithPagination;

    public $month;
    public $year;
    public $showGenerateModal = false;
    public $isGenerating = false;

    public function mount()
    {
        if (\App\Helpers\Editions::payrollLocked()) {
             session()->flash('show-feature-lock', ['title' => 'Payroll Locked', 'message' => 'Payroll Management is an Enterprise Feature 🔒. Please Upgrade.']);
             return redirect()->route('admin.dashboard');
        }

        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
    }

    public function render()
    {
        $payrolls = Payroll::with('user')
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->paginate(10);

        return view('livewire.payroll-manager', [
            'payrolls' => $payrolls
        ])->layout('layouts.app'); // Ensure layout is set
    }

    public function openGenerateModal()
    {
        $this->showGenerateModal = true;
    }

    public function generate(PayrollServiceInterface $service)
    {
        if (Auth::user()->is_demo) {
            $this->dispatch('banner-message', [
                'style' => 'danger',
                'message' => 'Demo User cannot generate payroll.'
            ]);
            $this->showGenerateModal = false;
            return;
        }

        $this->isGenerating = true;

        $users = User::where('group', '!=', 'admin')->get(); // Assume simple user filter
        // Better filter: User::all() or filtering by active status if available

        $count = 0;
        $locked = false;

        foreach ($users as $user) {
            // Skip if payroll already exists and is locked? 
            // For now, we update draft or create new
            
            $data = $service->calculate($user, $this->month, $this->year);

            // Open Core: Check if feature is locked
            if (isset($data['details']['status']) && $data['details']['status'] === 'locked_community_edition') {
                $locked = true;
                break; 
            }

            Payroll::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month' => $this->month,
                    'year' => $this->year,
                ],
                array_merge($data, [
                    'status' => 'draft',
                    'generated_by' => Auth::id(),
                ])
            );
            $count++;
        }

        if ($locked) {
            $this->dispatch('close-modal', 'generate-payroll-modal'); // Close modal
            $this->showGenerateModal = false;
            $this->isGenerating = false;
            
            $this->dispatch('feature-lock', title: 'Payroll Locked', message: 'Payroll Generation is an Enterprise Feature 🔒. Please Upgrade.');
            return;
        }

        $this->isGenerating = false;
        $this->showGenerateModal = false;
        
        $this->dispatch('banner-message', [
            'style' => 'success',
            'message' => "Payroll generated for $count employees."
        ]);
        
        session()->flash('flash.banner', "Payroll generated for $count employees.");
        session()->flash('flash.bannerStyle', 'success');
        
        return redirect()->route('admin.payrolls');
    }

    public function publish($id)
    {
        Payroll::find($id)->update(['status' => 'published']);
        session()->flash('success', 'Payroll published.');
    }

    public function pay($id)
    {
        Payroll::find($id)->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        session()->flash('success', 'Payroll marked as paid.');
    }
}
