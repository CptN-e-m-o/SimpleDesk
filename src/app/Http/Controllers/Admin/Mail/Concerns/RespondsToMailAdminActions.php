<?php

namespace App\Http\Controllers\Admin\Mail\Concerns;

use App\Data\Admin\Mail\MailAdminActionResultData;
use App\Exceptions\Admin\Mail\MailAdminActionException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait RespondsToMailAdminActions
{
    private function accepted(
        MailAdminActionResultData $result
    ): JsonResponse {
        return response()->json(
            [
                'data' => $result->toArray(),
            ],
            Response::HTTP_ACCEPTED,
        );
    }

    private function rejected(
        MailAdminActionException $exception
    ): JsonResponse {
        return response()->json([
            'message' => 'The requested mail action could not be accepted.',
            'error_code' => $exception->errorCode(),
            'errors' => [
                $exception->field() => [
                    $exception->getMessage(),
                ],
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
