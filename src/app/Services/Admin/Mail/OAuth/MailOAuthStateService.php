<?php

namespace App\Services\Admin\Mail\OAuth;

use App\Exceptions\Admin\Mail\OAuth\MailOAuthAuthorizationException;
use App\Models\Admin\Mail\MailProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MailOAuthStateService
{
    private const SESSION_KEY = 'mail_oauth_flows';

    private const FLOW_TTL_MINUTES = 10;

    private const MAX_ACTIVE_FLOWS = 10;

    public function issue(
        Request $request,
        MailProviderConnection $connection
    ): array {
        $now = now()->getTimestamp();

        $flows = $this->activeFlows(
            $request
                ->session()
                ->get(
                    self::SESSION_KEY,
                    []
                ),
            $now
        );

        $state = Str::random(64);
        $verifier = Str::random(96);
        $nonce = Str::random(64);

        $flows[$state] = [
            'connection_id' => $connection->id,

            'user_id' => $request->user()?->id,

            'verifier' => $verifier,

            'nonce' => $nonce,

            'expires_at' => now()
                ->addMinutes(
                    self::FLOW_TTL_MINUTES
                )
                ->getTimestamp(),
        ];

        if (
            count($flows)
            > self::MAX_ACTIVE_FLOWS
        ) {
            $flows = array_slice(
                $flows,
                -self::MAX_ACTIVE_FLOWS,
                null,
                true
            );
        }

        $request
            ->session()
            ->put(
                self::SESSION_KEY,
                $flows
            );

        return [
            'state' => $state,
            'verifier' => $verifier,
            'nonce' => $nonce,
            'challenge' => $this->codeChallenge(
                $verifier
            ),
        ];
    }

    public function consume(
        Request $request,
        string $state
    ): array {
        $storedFlows = $request
            ->session()
            ->get(
                self::SESSION_KEY,
                []
            );

        $flows = is_array($storedFlows)
            ? $storedFlows
            : [];

        $flow = $state !== ''
            ? ($flows[$state] ?? null)
            : null;

        /*
         * Remove the requested state before validation.
         * Therefore even an invalid, expired or denied callback
         * cannot reuse the same OAuth flow.
         */
        if ($state !== '') {
            unset($flows[$state]);
        }

        $flows = $this->activeFlows(
            $flows,
            now()->getTimestamp()
        );

        if ($flows === []) {
            $request
                ->session()
                ->forget(
                    self::SESSION_KEY
                );
        } else {
            $request
                ->session()
                ->put(
                    self::SESSION_KEY,
                    $flows
                );
        }

        if (
            ! $this->isValidFlow(
                $flow,
                $request
            )
        ) {
            throw new MailOAuthAuthorizationException(
                'The OAuth authorization request is invalid or has expired.'
            );
        }

        return [
            'connection_id' => $flow['connection_id'],

            'verifier' => $flow['verifier'],

            'nonce' => $flow['nonce'],
        ];
    }

    private function activeFlows(
        mixed $storedFlows,
        int $now
    ): array {
        if (! is_array($storedFlows)) {
            return [];
        }

        return array_filter(
            $storedFlows,
            static function (
                mixed $flow
            ) use (
                $now
            ): bool {
                return
                    is_array($flow)
                    && isset(
                        $flow['connection_id'],
                        $flow['expires_at'],
                        $flow['nonce'],
                        $flow['verifier']
                    )
                    && is_int(
                        $flow['connection_id']
                    )
                    && is_int(
                        $flow['expires_at']
                    )
                    && is_string(
                        $flow['verifier']
                    )
                    && $flow['verifier'] !== ''
                    && is_string(
                        $flow['nonce']
                    )
                    && $flow['nonce'] !== ''
                    && $flow['expires_at'] >= $now;
            }
        );
    }

    private function isValidFlow(
        mixed $flow,
        Request $request
    ): bool {
        if (! is_array($flow)) {
            return false;
        }

        return
            isset(
                $flow['connection_id'],
                $flow['expires_at'],
                $flow['nonce'],
                $flow['verifier']
            )
            && is_int(
                $flow['connection_id']
            )
            && array_key_exists(
                'user_id',
                $flow
            )
            && $flow['user_id']
            === $request->user()?->id
            && is_int(
                $flow['expires_at']
            )
            && $flow['expires_at']
            >= now()->getTimestamp()
            && is_string(
                $flow['verifier']
            )
            && $flow['verifier'] !== ''
            && is_string(
                $flow['nonce']
            )
            && $flow['nonce'] !== '';
    }

    private function codeChallenge(
        string $verifier
    ): string {
        return rtrim(
            strtr(
                base64_encode(
                    hash(
                        'sha256',
                        $verifier,
                        true
                    )
                ),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
