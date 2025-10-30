<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request)
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, Google2FA $google2fa)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $userId = $request->session()->get('2fa_user_id');
        $user = User::findOrFail($userId);

        if ($google2fa->verifyKey($user->google2fa_secret, $request->input('one_time_password'))) {
            $request->session()->forget('2fa_user_id');
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at' => now(),
        ]);

        return back()->withErrors(['one_time_password' => __('lang.profile_try_again')]);
    }
}
