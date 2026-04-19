<?php

namespace App\Livewire\User;

use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;
use Livewire\WithPagination;

class MyPayslips extends Component
{
    use WithPagination;

    // State
    public $needsSetup = false;

    // Inputs
    public $new_password;
    public $new_password_confirmation;

    public function mount()
    {
        if (\App\Helpers\Editions::payrollLocked()) {
             session()->flash('show-feature-lock', ['title' => 'Payroll Locked', 'message' => 'Payroll Access is an Enterprise Feature 🔒. Please Upgrade.']);
             return redirect()->route('home');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Check basic validity (time)
        if (!$user->hasValidPayslipPassword()) {
            $this->needsSetup = true;
            return;
        }

        // 2. Check encryption format validity (prevent infinite loop on download)
        try {
            Crypt::decryptString($user->payslip_password);
        } catch (\Exception $e) {
            $this->needsSetup = true;
        }
    }

    public function render()
    {
        if ($this->needsSetup) {
             return view('livewire.user.my-payslips', [
                'payrolls' => collect(), 
            ])->layout('layouts.app');
        }

        // List is always visible if setup is done
        $payrolls = Payroll::where('user_id', Auth::id())
            ->where('status', 'paid')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(10);

        return view('livewire.user.my-payslips', [
            'payrolls' => $payrolls
        ])->layout('layouts.app');
    }

    public function triggerReset()
    {
        $this->needsSetup = true;
    }

    public function cancelReset()
    {
        // Only allow cancel if checking validity passes
        $user = Auth::user();
        if ($user->hasValidPayslipPassword()) {
            try {
                Crypt::decryptString($user->payslip_password);
                $this->needsSetup = false;
            } catch (\Exception $e) {
                // Cannot cancel if password is invalid
            }
        }
    }

    public function setupPassword()
    {
        $this->validate([
            'new_password' => 'required|min:4|confirmed',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Store Reversibly Encrypted Password for PDF Encryption
        $user->update([
            'payslip_password' => Crypt::encryptString($this->new_password),
            'payslip_password_set_at' => now(),
        ]);

        $this->needsSetup = false;
        session()->flash('message', 'Payslip password set successfully. This password will be used to open your PDF files.');
    }

    public function download($id)
    {
        if ($this->needsSetup) {
             abort(403, 'Please set a payslip password first.');
        }

        $payroll = Payroll::where('user_id', Auth::id())
            ->where('status', 'paid')
            ->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $password = Crypt::decryptString($user->payslip_password);
        } catch (\Exception $e) {
            // Fallback or error if decryption fails (e.g. old hashed password)
            $this->needsSetup = true;
            session()->flash('error', 'Your password format is outdated. Please reset it.');
            return redirect()->route('my-payslips');
        }

        $pdf = Pdf::loadView('pdf.payslip', ['payroll' => $payroll]);
        
        // Encrypt the PDF
        // User password to open, Random owner password to prevent editing permissions
        $ownerPassword = \Illuminate\Support\Str::random(16);
        $pdf->setEncryption($password, $ownerPassword, ['print']);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'payslip-' . $payroll->month . '-' . $payroll->year . '.pdf');
    }
}
