<?php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Auth;

class MyCashAdvances extends Component
{
    use WithPagination;

    public $amount;
    public $purpose;
    public $payment_month;
    public $payment_year;

    public $showCreateModal = false;

    public function mount()
    {
        if (\App\Helpers\Editions::payrollLocked()) {
            session()->flash('show-feature-lock', ['title' => 'Kasbon Locked', 'message' => 'Kasbon is an Enterprise Feature 🔒. Please Upgrade.']);
            return redirect()->route('home');
        }

        $this->payment_month = now()->addMonth()->month;
        $this->payment_year = now()->addMonth()->year;
    }

    public function openCreateModal()
    {
        $this->reset(['amount', 'purpose']);
        $this->showCreateModal = true;
    }

    public function submit()
    {
        if ($this->amount) {
            $this->amount = str_replace(['.', ','], '', (string) $this->amount);
        }

        $basicSalary = Auth::user()->basic_salary ?? 0;

        $this->validate([
            'amount' => ['required', 'numeric', 'min:1000', 'max:' . $basicSalary],
            'purpose' => ['required', 'string', 'min:5', 'max:255'],
            'payment_month' => ['required', 'integer', 'min:1', 'max:12'],
            'payment_year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ], [
            'amount.max' => 'Pengajuan Kasbon tidak boleh melebihi Gaji Pokok (Rp ' . number_format($basicSalary, 0, ',', '.') . ').',
        ]);

        $advance = CashAdvance::create([
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            'purpose' => $this->purpose,
            'payment_month' => $this->payment_month,
            'payment_year' => $this->payment_year,
            'status' => 'pending',
        ]);

        // Notify Supervisor AND Admins
        $supervisor = Auth::user()->supervisor;
        $admins = \App\Models\User::whereIn('group', ['admin', 'superadmin'])->get();

        $notifiable = $admins;
        if ($supervisor) {
            $notifiable = $notifiable->push($supervisor)->unique('id');
        }

        if ($notifiable->count() > 0) {
            \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\CashAdvanceRequested($advance));
            \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\CashAdvanceRequestedEmail($advance));
            $this->dispatch('refresh-notifications');
        }

        // Global Admin Email
        $adminEmail = \App\Models\Setting::getValue('notif.admin_email');
        if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
                    ->notify(new \App\Notifications\CashAdvanceRequestedEmail($advance));
            } catch (\Throwable $e) {
            }
        }

        $this->showCreateModal = false;

        session()->flash('success', 'Pengajuan Kasbon berhasil dikirim, menunggu persetujuan.');
        $this->dispatch('banner-message', [
            'style' => 'success',
            'message' => 'Pengajuan Kasbon berhasil dikirim.'
        ]);
    }

    public function delete($id)
    {
        $advance = CashAdvance::where('id', $id)->where('user_id', Auth::id())->where('status', 'pending')->first();
        if ($advance) {
            $advance->delete();
            $this->dispatch('banner-message', [
                'style' => 'success',
                'message' => 'Pengajuan dibatalkan.'
            ]);
        }
    }

    public function render()
    {
        $userId = Auth::id();
        $user = Auth::user();

        $advances = CashAdvance::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Summary stats
        $totalUnpaid = CashAdvance::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        $totalPaid = CashAdvance::where('user_id', $userId)
            ->where('status', 'paid')
            ->sum('amount');

        $basicSalary = $user->basic_salary ?? 0;

        return view('livewire.finance.my-cash-advances', [
            'advances' => $advances,
            'totalUnpaid' => $totalUnpaid,
            'totalPaid' => $totalPaid,
            'basicSalary' => $basicSalary,
        ])->layout('layouts.app');
    }
}
