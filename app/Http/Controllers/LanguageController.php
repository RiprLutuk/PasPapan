<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    /**
     * Update the user's language preference.
     */
    public function update(Request $request)
    {
        $request->validate([
            'language' => 'required|in:id,en',
        ]);

        $user = Auth::user();
        $user->language = $request->language;
        $user->save();

        return  back();
    }
}
