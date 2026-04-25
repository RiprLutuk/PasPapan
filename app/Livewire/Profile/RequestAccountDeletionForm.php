<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;

class RequestAccountDeletionForm extends Component
{
    use InteractsWithBanner;

    public bool $confirmingUserDeletion = false;

    public string $password = '';

    public string $reason = '';

    public function confirmUserDeletion(): void
    {
        $this->resetErrorBag();
        $this->password = '';
        $this->confirmingUserDeletion = true;
    }

    public function deleteUser(): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        if ($user->hasPendingAccountDeletionRequest()) {
            $this->confirmingUserDeletion = false;
            $this->banner(__('Your account deletion request is already pending admin review.'));

            return;
        }

        $this->validate([
            'password' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if (! Hash::check($this->password, $user->password)) {
            $this->addError('password', __('The provided password does not match your current password.'));

            return;
        }

        $user->requestAccountDeletion($this->reason);

        $this->reset(['password', 'reason']);
        $this->confirmingUserDeletion = false;
        $this->banner(__('Your account deletion request has been sent to admin for review.'));
    }

    public function render()
    {
        return view('profile.delete-user-form', [
            'user' => Auth::user()?->fresh(),
        ]);
    }
}
