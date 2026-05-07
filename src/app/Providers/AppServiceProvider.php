<?php

namespace App\Providers;

use App\Models\User\UserLoginSession;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use App\Services\Auth\UserLoginSessionLogger;
use App\Models\User\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event) {
            if (! $event->user instanceof User) {
                return;
            }

            app(UserLoginSessionLogger::class)->log(
                request(),
                $event->user,
                $event->guard,
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (! $event->user instanceof User) {
                return;
            }

            $loginSessionId = request()->session()->get('user_login_session_id');

            if (! $loginSessionId) {
                return;
            }

            UserLoginSession::query()
                ->whereKey($loginSessionId)
                ->where('user_id', $event->user->id)
                ->whereNull('logged_out_at')
                ->update([
                    'is_current' => false,
                    'logged_out_at' => now(),
                    'last_activity_at' => now(),
                ]);
        });
    }
}
