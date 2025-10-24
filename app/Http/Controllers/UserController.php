<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    const ELEMENTS_PER_PAGE = 20;

    public function index(): View
    {
        $users = User::paginate(self::ELEMENTS_PER_PAGE);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Пользователь успешно создан!');
    }

    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validatedData = $request->validated();

        if (! empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        return redirect()->route('users.index')
            ->with('success', 'Данные пользователя успешно обновлены!');
    }

    public function destroy(User $user)
    {
        if (auth()->user()->role_id !== UserRole::Admin) {
            abort(403, 'У вас нет прав для выполнения этого действия.');
        }

        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')
                ->with('error', 'Вы не можете удалить свою собственную учетную запись!');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Пользователь успешно удален!');
    }
}
