<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class FaceEnrollment extends Component
{
    public bool $isEnrolled = false;
    public bool $isCapturing = false;

    public function mount()
    {
        $this->isEnrolled = Auth::user()->hasFaceRegistered();
    }

    /**
     * Save the face descriptor from the frontend.
     */
    public function saveFaceDescriptor($descriptor)
    {
        if (!$descriptor) return;
        
        $user = Auth::user();

        // Support legacy 128-dim descriptors and lightweight geometry descriptors.
        if (!in_array(count($descriptor), [128, 129], true)) {
            $this->dispatch('toast', type: 'error', message: __('Invalid face data. Please try again.'));
            return;
        }

        try {
            app(\App\Contracts\AttendanceServiceInterface::class)->registerFace($user, $descriptor);
            
            $this->isEnrolled = true;
            $this->isCapturing = false;
    
            $this->dispatch('toast', type: 'success', message: __('Face ID registered successfully!'));
            $this->dispatch('face-enrolled');
        } catch (\Exception $e) {
            if ($e->getCode() == 403) {
                $this->dispatch('feature-lock', title: 'Face ID Locked', message: $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Remove the user's face registration.
     */
    public function removeFace()
    {
        try {
            app(\App\Contracts\AttendanceServiceInterface::class)->removeFace(Auth::user());
            
            $this->isEnrolled = false;
            $this->dispatch('toast', type: 'success', message: __('Face ID removed.'));
        } catch (\Exception $e) {
            if ($e->getCode() == 403) {
                $this->dispatch('feature-lock', title: 'Face ID Locked', message: $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Start the capture process.
     */
    public function startCapture()
    {
        $this->isCapturing = true;
    }

    /**
     * Cancel the capture process.
     */
    public function cancelCapture()
    {
        $this->isCapturing = false;
    }

    public function reportClientError(string $stage, string $message, array $context = []): void
    {
        $user = Auth::user();

        Log::warning('Face enrollment client error', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'stage' => $stage,
            'message' => $message,
            'context' => $context,
            'route' => request()->path(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function render()
    {
        return view('livewire.face-enrollment')->layout('layouts.app');
    }
}
