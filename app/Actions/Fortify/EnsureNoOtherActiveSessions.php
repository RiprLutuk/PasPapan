<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\ActiveSessionGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EnsureNoOtherActiveSessions
{
    public function __construct(
        protected ActiveSessionGuard $activeSessionGuard
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $login = (string) $request->input('email');

        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $login)->first()
            : User::where('phone', $login)->first();

        if ($user && Hash::check((string) $request->input('password'), $user->password)) {
            if ($this->activeSessionGuard->hasActiveSession($user)) {
                throw ValidationException::withMessages([
                    'email' => __('This account is still active on another device. Please log out from that device first.'),
                ])->redirectTo('/login');
            }
        }

        return $next($request);
    }
}
