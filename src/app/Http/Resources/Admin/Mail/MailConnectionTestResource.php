<?php

namespace App\Http\Resources\Admin\Mail;

use App\Data\Admin\Mail\MailConnectionTestResultData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MailConnectionTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MailConnectionTestResultData $result */
        $result = $this->resource;

        return [
            'successful' => $result->successful,
            'message' => $result->message,
            'latency_ms' => $result->latencyMilliseconds,
            'details' => $result->details,
            'tested_at' => now()->toIso8601String(),
        ];
    }
}
