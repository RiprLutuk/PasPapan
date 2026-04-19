<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VerifyEmailCodeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => __('The verification code must be 6 digits.'),
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended('/');
        }

        if (! $user->hasValidEmailVerificationCode($validated['code'])) {
            return back()
                ->withErrors(['code' => __('The verification code is invalid or has expired.')])
                ->onlyInput('code');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $user->clearEmailVerificationCode();

        return redirect()->intended('/')->with('verified', true);
    }
}
