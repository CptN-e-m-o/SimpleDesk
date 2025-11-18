<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function index(): View
    {
        return view('auth.registration');
    }

    public function store(Request $request): View
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'login' => ['nullable', 'string', 'max:255', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => $request->name,
            'login' => $request->login,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'role_id' => UserRole::Client->value,
            'is_active' => true,
            'timezone' => 'UTC',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return view('index-page.index');
    }
}
