<?php

namespace App\Http\Resources\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserLoginSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeSession = $this->session_id
            ? DB::table('sessions')
                ->where('id', $this->session_id)
                ->first()
            : null;

        return [
            'id' => $this->id,
            'guard' => $this->guard,

            'device_type' => $this->device_type,
            'device_name' => $this->device_name,

            'platform' => $this->platform,
            'platform_version' => $this->platform_version,

            'browser' => $this->browser,
            'browser_version' => $this->browser_version,

            'ip_address' => $this->ip_address,

            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,

            'user_agent' => $this->user_agent,

            'logged_in_at' => $this->logged_in_at?->toISOString(),

            'last_activity_at' => $activeSession
                ? Carbon::createFromTimestamp($activeSession->last_activity)->toISOString()
                : $this->last_activity_at?->toISOString(),

            'logged_out_at' => $this->logged_out_at?->toISOString(),

            'is_current' => $activeSession !== null,
            'is_successful' => (bool) $this->is_successful,
        ];
    }
}
