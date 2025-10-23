<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public function store(LoginRequest $request): View
    {
        $request->authenticate();

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
