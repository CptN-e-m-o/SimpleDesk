<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    public function index(TimezoneService $timezoneService, Google2FA $google2fa)
    {
        $user = Auth::user();
        $qrCodeUrl = null;
        $secretKey = null;

        if (! $user->google2fa_enabled) {
            $secretKey = $google2fa->generateSecretKey();
            $qrCodeUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $secretKey);
            session(['2fa_secret' => Crypt::encrypt($secretKey)]);
        }

        $logins = $user->loginHistories()->latest('login_at')->take(10)->get();

        return view('users.profile.index', [
            'user' => $user,
            'timezones' => $timezoneService->getUniqueFormattedList(),
            'qrCodeUrl' => $qrCodeUrl,
            'secretKey' => $secretKey,
            'logins' => $logins,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'patronymic' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string'],
            'current_password' => ['nullable', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->patronymic = $validated['patronymic'];
        $user->timezone = $validated['timezone'];

        if ($request->filled('password') && $request->filled('current_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => __('lang.profile_current_password_is_incorrect')]);
            }
            $user->password = Hash::make($validated['password']);
        } elseif ($request->filled('password') && ! $request->filled('current_password')) {
            return back()->withErrors(['current_password' => __('lang.profile_current_password_required')]);
        }

        $user->save();

        return back()->with('success', __('lang.profile_updated'));
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', File::image()->max(2 * 1024)],
        ]);

        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => __('lang.profile_avatar_updated'),
            'new_avatar_url' => $user->fresh()->avatar_url,
        ]);
    }

    public function enableTwoFactor(Request $request, Google2FA $google2fa)
    {
        $user = Auth::user();
        $request->validate(['one_time_password' => 'required|digits:6']);

        $secret = Crypt::decrypt(session('2fa_secret'));

        if ($google2fa->verifyKey($secret, $request->input('one_time_password'))) {
            $user->google2fa_secret = $secret;
            $user->google2fa_enabled = true;
            $user->save();

            session()->forget('2fa_secret');

            return back()->with('success', __('lang.profile_2fa_enabled_successfully'));
        }

        return back()->withErrors(['one_time_password' => __('lang.profile_2fa_incorrect_code')]);
    }

    public function disableTwoFactor(Request $request)
    {
        $user = Auth::user();

        $request->validate(['current_password' => 'required|current_password']);

        $user->google2fa_enabled = false;
        $user->google2fa_secret = null;
        $user->save();

        return back()->with('success', __('lang.profile_2fa_disabled_successfully'));
    }
}
