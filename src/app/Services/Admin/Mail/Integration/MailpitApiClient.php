<?php

namespace App\Services\Admin\Mail\Integration;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MailpitApiClient
{
    public function info(): array
    {
        return $this->json(
            $this->jsonRequest()
                ->get('/api/v1/info')
                ->throw()
        );
    }

    public function waitForSubject(
        string $subject,
        int $timeoutSeconds,
    ): array {
        $deadline = microtime(true) + max(1, $timeoutSeconds);
        $query = 'subject:"'.$this->escapeSearchPhrase($subject).'"';

        do {
            foreach ($this->search($query) as $message) {
                if (($message['Subject'] ?? null) !== $subject) {
                    continue;
                }

                $id = $message['ID'] ?? null;

                if (is_string($id) && $id !== '') {
                    return $this->message($id);
                }
            }

            usleep($this->pollIntervalMilliseconds() * 1000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException(
            "Mailpit did not receive message with subject [{$subject}] "
            ."within {$timeoutSeconds} seconds."
        );
    }

    public function search(string $query, int $limit = 20): array
    {
        $payload = $this->json(
            $this->jsonRequest()
                ->get('/api/v1/search', [
                    'query' => $query,
                    'start' => 0,
                    'limit' => max(1, min(100, $limit)),
                ])
                ->throw()
        );

        $messages = $payload['messages'] ?? [];

        return is_array($messages) ? $messages : [];
    }

    public function message(string $id): array
    {
        return $this->json(
            $this->jsonRequest()
                ->get('/api/v1/message/'.rawurlencode($id))
                ->throw()
        );
    }

    public function attachmentContent(
        string $messageId,
        string $partId,
    ): string {
        return $this->request()
            ->accept('*/*')
            ->get(
                '/api/v1/message/'
                .rawurlencode($messageId)
                .'/part/'
                .rawurlencode($partId)
            )
            ->throw()
            ->body();
    }

    public function deleteMessage(string $id): void
    {
        $this->jsonRequest()
            ->delete('/api/v1/messages', [
                'IDs' => [$id],
            ])
            ->throw();
    }

    private function jsonRequest(): PendingRequest
    {
        return $this->request()->acceptJson();
    }

    private function request(): PendingRequest
    {
        $baseUrl = rtrim(
            (string) config(
                'simpledesk-mail-integration.mailpit.base_url',
                'http://mailpit:8025'
            ),
            '/'
        );

        if ($baseUrl === '') {
            throw new RuntimeException(
                'Mailpit API base URL is not configured.'
            );
        }

        $request = Http::baseUrl($baseUrl)->timeout(
            max(
                1,
                (int) config(
                    'simpledesk-mail-integration.mailpit.http_timeout_seconds',
                    10
                )
            )
        );

        $username = trim(
            (string) config(
                'simpledesk-mail-integration.mailpit.username',
                ''
            )
        );

        if ($username !== '') {
            $request = $request->withBasicAuth(
                $username,
                (string) config(
                    'simpledesk-mail-integration.mailpit.password',
                    ''
                )
            );
        }

        return $request;
    }

    private function json(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException(
                'Mailpit returned an invalid JSON response.'
            );
        }

        return $payload;
    }

    private function pollIntervalMilliseconds(): int
    {
        return max(
            100,
            (int) config(
                'simpledesk-mail-integration.mailpit.poll_interval_milliseconds',
                250
            )
        );
    }

    private function escapeSearchPhrase(string $value): string
    {
        return str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value
        );
    }
}
