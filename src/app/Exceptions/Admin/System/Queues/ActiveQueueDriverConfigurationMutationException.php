<?php

namespace App\Exceptions\Admin\System\Queues;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ActiveQueueDriverConfigurationMutationException extends RuntimeException
{
    public function render(
        Request $request,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => $this->getMessage(),
                    'errors' => [
                        'configuration' => [
                            $this->getMessage(),
                        ],
                    ],
                ],
                422,
            );
        }

        return back()
            ->withErrors([
                'configuration' => $this->getMessage(),
            ])
            ->withInput();
    }
}
