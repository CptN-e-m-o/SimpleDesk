<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function index(): View
    {
        return view('auth.authorization');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $user = Auth::user();

        if ($user->google2fa_enabled) {
            $request->session()->put('2fa_user_id', $user->id);
            Auth::logout();

            return redirect()->route('2fa.challenge');
        }

        $request->session()->regenerate();

        return view('index-page.index');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): View
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return view('index-page.index');
    }
}
