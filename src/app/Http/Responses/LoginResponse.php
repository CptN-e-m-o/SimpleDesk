<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $redirectTo = '/';

        if ($user && $user->hasAnyRole(['admin', 'agent'])) {
            $redirectTo = '/dashboard';
        }

        return $request->wantsJson()
            ? new JsonResponse([
                'two_factor' => false,
                'redirect' => $redirectTo,
            ])
            : redirect()->intended($redirectTo);
    }
}
