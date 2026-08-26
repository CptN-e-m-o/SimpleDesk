<?php

namespace App\Http\Controllers\Admin\System\Broadcasting;

use App\Events\Admin\System\Broadcasting\BrowserProbeSent;
use App\Http\Controllers\Controller;
use App\Services\Admin\System\Broadcasting\BroadcastClientConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BroadcastBrowserProbeController extends Controller
{
    public function __construct(
        private readonly BroadcastClientConfigurationService $clientConfiguration,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'probe_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();

        abort_unless($user, 401);

        $client = $this->clientConfiguration->effective();

        abort_unless(
            ($client['available'] ?? false) === true
            && ($client['ownership'] ?? null) === 'managed',
            409,
            'Managed browser broadcasting is not available.',
        );

        $probeId = (string) $validated['probe_id'];
        $sentAt = now()->toIso8601String();

        BrowserProbeSent::dispatch(
            $user->id,
            $probeId,
            $sentAt,
        );

        return response()->json([
            'probe_id' => $probeId,
            'sent_at' => $sentAt,
        ]);
    }
}
