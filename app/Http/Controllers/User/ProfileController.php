<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    public function index(TimezoneService $timezoneService)
    {
        return view('users.profile.index', [
            'user' => Auth::user(),
            'timezones' => $timezoneService->getUniqueFormattedList(),
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
        ]);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->patronymic = $validated['patronymic'];
        $user->timezone = $validated['timezone'];

        $user->save();

        return back()->with('success', __('lang.profile_updated'));
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => [
                'required',
                File::image()
                    ->max(2 * 1024),
            ],
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
            'message' => 'Аватар успешно обновлен!',
            'new_avatar_url' => $user->fresh()->avatar_url,
        ]);
    }
}
