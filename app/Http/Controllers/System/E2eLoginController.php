<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class E2eLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $expectedToken = (string) config('services.e2e.login_token', '');
        $providedToken = (string) $request->input('token', '');

        if ($expectedToken === '') {
            abort(404);
        }

        if (! hash_equals($expectedToken, $providedToken)) {
            abort(403);
        }

        $user = User::query()->where('email', (string) $request->input('email', ''))->firstOrFail();

        if (! $user->canAuthenticate()) {
            abort(403);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $destination = (string) $request->input('to', '/home');

        if (! Str::startsWith($destination, '/') || Str::startsWith($destination, '//')) {
            $destination = '/home';
        }

        return redirect()->to($destination);
    }
}
