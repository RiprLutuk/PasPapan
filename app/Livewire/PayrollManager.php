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

    // Bulk Actions
    public $selectedPayrolls = [];
    public $selectAll = false;

    public function mount()
    {
        if (\App\Helpers\Editions::payrollLocked()) {
            session()->flash('show-feature-lock', ['title' => 'Payroll Locked', 'message' => 'Payroll Management is an Enterprise Feature 🔒. Please Upgrade.']);
            return redirect()->route('admin.dashboard');
        }

        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedPayrolls = Payroll::where('month', $this->month)
                ->where('year', $this->year)
                ->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedPayrolls = [];
        }
    }

    // Determine if the current user is Superadmin or Finance Rank 1
    public function getCanManageProperty()
    {
        $user = Auth::user();
        if ($user->isSuperadmin) {
            return true;
        }

        // Demo admin gets full payroll access for demonstration purposes
        if ($user->is_demo && $user->isAdmin) {
            return true;
        }

        // Check if Finance and Rank 1 (e.g., Head of Finance)
        if ($user->division && strtolower($user->division->name) === 'finance') {
            if ($user->jobTitle && $user->jobTitle->jobLevel && $user->jobTitle->jobLevel->rank === 1) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        $payrolls = Payroll::with('user')
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->paginate(10);

        return view('livewire.payroll-manager', [
            'payrolls' => $payrolls,
        ])->layout('layouts.app'); // Ensure layout is set
    }

    public function openGenerateModal()
    {
        $this->showGenerateModal = true;
    }

    public function generate(PayrollServiceInterface $service)
    {

        $this->isGenerating = true;

        $users = User::whereNotIn('group', ['admin', 'superadmin'])->get(); // Exclude admins and superadmins

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
            $this->selectedPayrolls = [];
            $this->selectAll = false;

            $this->dispatch('feature-lock', title: 'Payroll Locked', message: 'Payroll Generation is an Enterprise Feature 🔒. Please Upgrade.');
            return;
        }

        $this->isGenerating = false;
        $this->showGenerateModal = false;
        $this->selectedPayrolls = [];
        $this->selectAll = false;

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
        if (!$this->canManage) return;
        Payroll::find($id)->update(['status' => 'published']);
        session()->flash('success', 'Payroll published.');
    }

    public function pay($id)
    {
        if (!$this->canManage) return;
        $payroll = Payroll::find($id);

        if ($payroll) {
            $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Sync: Mark related Kasbons as Paid
            \App\Models\CashAdvance::where('user_id', $payroll->user_id)
                ->where('status', 'approved')
                ->where('payment_month', $payroll->month)
                ->where('payment_year', $payroll->year)
                ->update(['status' => 'paid']);

            session()->flash('success', 'Payroll marked as paid.');
        }
    }

    public function bulkPublish()
    {
        if (!$this->canManage || empty($this->selectedPayrolls)) return;

        Payroll::whereIn('id', $this->selectedPayrolls)->where('status', 'draft')->update(['status' => 'published']);
        $this->selectedPayrolls = [];
        $this->selectAll = false;
        $this->dispatch('banner-message', ['style' => 'success', 'message' => 'Selected payrolls published.']);
    }

    public function bulkPay()
    {
        if (!$this->canManage || empty($this->selectedPayrolls)) return;

        $payrolls = Payroll::whereIn('id', $this->selectedPayrolls)->where('status', 'published')->get();

        foreach ($payrolls as $payroll) {
            $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Sync: Mark related Kasbons as Paid
            \App\Models\CashAdvance::where('user_id', $payroll->user_id)
                ->where('status', 'approved')
                ->where('payment_month', $payroll->month)
                ->where('payment_year', $payroll->year)
                ->update(['status' => 'paid']);
        }

        $this->selectedPayrolls = [];
        $this->selectAll = false;
        $this->dispatch('banner-message', ['style' => 'success', 'message' => 'Selected payrolls marked as paid.']);
    }
}
