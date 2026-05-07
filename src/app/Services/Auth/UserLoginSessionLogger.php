<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Models\User\UserLoginSession;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class UserLoginSessionLogger
{
    public function log(Request $request, User $user, string $guard): UserLoginSession
    {
        $agent = new Agent();

        $userAgent = $request->userAgent();

        if ($userAgent) {
            $agent->setUserAgent($userAgent);
        }

        $platform = $agent->platform();
        $browser = $agent->browser();

        $deviceType = match (true) {
            $agent->isTablet() => 'tablet',
            $agent->isPhone() => 'mobile',
            $agent->isDesktop() => 'desktop',
            default => 'unknown',
        };

        $ip = $request->ip();

        /*
        |--------------------------------------------------------------------------
        | Local development fallback
        |--------------------------------------------------------------------------
        |
        | Local/private IPs cannot be geolocated, so for local development
        | we use a public test IP.
        |
        */

        $geoLookupIp = $ip;

        if (
            app()->isLocal() ||
            $ip === '127.0.0.1' ||
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.')
        ) {
            $geoLookupIp = '8.8.8.8';
        }

        $geo = geoip($geoLookupIp);

        $loginSession = UserLoginSession::create([
            'user_id' => $user->id,
            'guard' => $guard,

            'session_id' => $request->session()->getId(),

            'device_type' => $deviceType,
            'device_name' => $this->deviceName($agent, $platform),

            'platform' => $platform,
            'platform_version' => $platform
                ? $agent->version($platform)
                : null,

            'browser' => $browser,
            'browser_version' => $browser
                ? $agent->version($browser)
                : null,

            'ip_address' => $ip,
            'user_agent' => $userAgent,

            'country' => $geo->country,
            'region' => $geo->state_name,
            'city' => $geo->city,

            'latitude' => $geo->lat,
            'longitude' => $geo->lon,

            'logged_in_at' => now(),
            'last_activity_at' => now(),

            'is_current' => true,
            'is_successful' => true,
        ]);

        $request->session()->put(
            'user_login_session_id',
            $loginSession->id,
        );

        return $loginSession;
    }

    protected function deviceName(Agent $agent, ?string $platform): string
    {
        if ($agent->isDesktop()) {
            return match (strtolower($platform ?? '')) {
                'windows' => 'Windows PC',
                'mac os',
                'macos' => 'Mac',
                'linux' => 'Linux PC',
                default => 'Desktop',
            };
        }

        if ($agent->isTablet()) {
            if ($agent->isiPad()) {
                return 'iPad';
            }

            return 'Tablet';
        }

        if ($agent->isPhone()) {
            if ($agent->isAndroidOS()) {
                return 'Android Phone';
            }

            if ($agent->isiPhone()) {
                return 'iPhone';
            }

            return 'Phone';
        }

        return 'Unknown Device';
    }
}
