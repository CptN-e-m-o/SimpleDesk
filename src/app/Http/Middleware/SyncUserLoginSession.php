<?php

namespace App\Http\Middleware;

use App\Models\User\User;
use App\Models\User\UserLoginSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncUserLoginSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user instanceof User) {
            return $response;
        }

        $loginSessionId = $request->session()->get('user_login_session_id');

        if (! $loginSessionId) {
            return $response;
        }

        UserLoginSession::query()
            ->whereKey($loginSessionId)
            ->where('user_id', $user->id)
            ->update([
                'session_id' => $request->session()->getId(),
                'last_activity_at' => now(),
                'logged_out_at' => null,
                'is_current' => true,
            ]);

        return $response;
    }
}
