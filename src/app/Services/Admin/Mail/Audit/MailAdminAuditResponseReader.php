<?php

namespace App\Services\Admin\Mail\Audit;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MailAdminAuditResponseReader
{
    public function read(?Response $response): array
    {
        if ($response === null) {
            return [];
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            return is_array($data) ? $data : [];
        }

        $content = $response->getContent();

        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }
}
