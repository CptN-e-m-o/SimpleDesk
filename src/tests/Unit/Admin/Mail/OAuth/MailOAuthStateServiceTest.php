<?php

namespace Tests\Unit\Admin\Mail\OAuth;

use App\Exceptions\Admin\Mail\OAuth\MailOAuthAuthorizationException;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Models\User\User;
use App\Services\Admin\Mail\OAuth\MailOAuthStateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class MailOAuthStateServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_issue_creates_state_and_valid_pkce_s256_challenge(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-04 12:00:00'
            )
        );

        $request = $this->requestForUser(
            10
        );

        $connection = $this->connection(
            25
        );

        $flow = app(
            MailOAuthStateService::class
        )->issue(
            $request,
            $connection
        );

        $this->assertIsString(
            $flow['state']
        );

        $this->assertSame(
            64,
            strlen(
                $flow['state']
            )
        );

        $this->assertIsString(
            $flow['verifier']
        );

        $this->assertSame(
            96,
            strlen(
                $flow['verifier']
            )
        );

        $this->assertIsString(
            $flow['nonce']
        );

        $this->assertSame(
            64,
            strlen(
                $flow['nonce']
            )
        );

        $expectedChallenge = rtrim(
            strtr(
                base64_encode(
                    hash(
                        'sha256',
                        $flow['verifier'],
                        true
                    )
                ),
                '+/',
                '-_'
            ),
            '='
        );

        $this->assertSame(
            $expectedChallenge,
            $flow['challenge']
        );

        $storedFlows = $request
            ->session()
            ->get(
                'mail_oauth_flows'
            );

        $this->assertIsArray(
            $storedFlows
        );

        $this->assertArrayHasKey(
            $flow['state'],
            $storedFlows
        );

        $storedFlow = $storedFlows[
        $flow['state']
        ];

        $this->assertSame(
            25,
            $storedFlow['connection_id']
        );

        $this->assertSame(
            10,
            $storedFlow['user_id']
        );

        $this->assertSame(
            $flow['verifier'],
            $storedFlow['verifier']
        );

        $this->assertSame(
            $flow['nonce'],
            $storedFlow['nonce']
        );

        $this->assertSame(
            now()
                ->addMinutes(10)
                ->getTimestamp(),
            $storedFlow['expires_at']
        );
    }

    public function test_consume_removes_state_and_prevents_reuse(): void
    {
        $request = $this->requestForUser(
            10
        );

        $connection = $this->connection(
            25
        );

        $service = app(
            MailOAuthStateService::class
        );

        $issued = $service->issue(
            $request,
            $connection
        );

        $consumed = $service->consume(
            $request,
            $issued['state']
        );

        $this->assertSame(
            25,
            $consumed['connection_id']
        );

        $this->assertSame(
            $issued['verifier'],
            $consumed['verifier']
        );

        $this->assertSame(
            $issued['nonce'],
            $consumed['nonce']
        );

        $this->assertFalse(
            $request
                ->session()
                ->has(
                    'mail_oauth_flows'
                )
        );

        $this->expectException(
            MailOAuthAuthorizationException::class
        );

        $service->consume(
            $request,
            $issued['state']
        );
    }

    public function test_consume_rejects_state_issued_for_another_user(): void
    {
        $session = $this->makeSessionStore();

        $firstRequest = $this->requestForUser(
            10,
            $session
        );

        $connection = $this->connection(
            25
        );

        $service = app(
            MailOAuthStateService::class
        );

        $issued = $service->issue(
            $firstRequest,
            $connection
        );

        $secondRequest = $this->requestForUser(
            20,
            $session
        );

        try {
            $service->consume(
                $secondRequest,
                $issued['state']
            );

            $this->fail(
                'Expected OAuth state validation failure.'
            );
        } catch (
            MailOAuthAuthorizationException $exception
        ) {
            $this->assertSame(
                'The OAuth authorization request is invalid or has expired.',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $session->has(
                'mail_oauth_flows'
            )
        );
    }

    public function test_issue_removes_expired_and_malformed_flows(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-04 12:00:00'
            )
        );

        $request = $this->requestForUser(
            10
        );

        $request
            ->session()
            ->put(
                'mail_oauth_flows',
                [
                    'expired-state' => [
                        'connection_id' => 1,
                        'user_id' => 10,
                        'verifier' => 'expired-verifier',

                        'nonce' => 'expired-nonce',

                        'expires_at' => now()
                            ->subSecond()
                            ->getTimestamp(),
                    ],

                    'valid-state' => [
                        'connection_id' => 2,
                        'user_id' => 10,
                        'verifier' => 'valid-verifier',

                        'nonce' => 'valid-nonce',

                        'expires_at' => now()
                            ->addMinutes(5)
                            ->getTimestamp(),
                    ],

                    'malformed-state' => [
                        'connection_id' => 'not-an-integer',

                        'verifier' => null,

                        'nonce' => null,
                    ],
                ]
            );

        $issued = app(
            MailOAuthStateService::class
        )->issue(
            $request,
            $this->connection(25)
        );

        $storedFlows = $request
            ->session()
            ->get(
                'mail_oauth_flows'
            );

        $this->assertIsArray(
            $storedFlows
        );

        $this->assertArrayNotHasKey(
            'expired-state',
            $storedFlows
        );

        $this->assertArrayNotHasKey(
            'malformed-state',
            $storedFlows
        );

        $this->assertArrayHasKey(
            'valid-state',
            $storedFlows
        );

        $this->assertArrayHasKey(
            $issued['state'],
            $storedFlows
        );

        $this->assertSame(
            'valid-nonce',
            $storedFlows[
            'valid-state'
            ]['nonce']
        );

        $this->assertCount(
            2,
            $storedFlows
        );
    }

    public function test_issue_keeps_only_ten_most_recent_active_flows(): void
    {
        $request = $this->requestForUser(
            10
        );

        $connection = $this->connection(
            25
        );

        $service = app(
            MailOAuthStateService::class
        );

        $issuedStates = [];

        for (
            $index = 0;
            $index < 12;
            $index++
        ) {
            $flow = $service->issue(
                $request,
                $connection
            );

            $issuedStates[] =
                $flow['state'];
        }

        $storedFlows = $request
            ->session()
            ->get(
                'mail_oauth_flows'
            );

        $this->assertIsArray(
            $storedFlows
        );

        $this->assertCount(
            10,
            $storedFlows
        );

        $this->assertArrayNotHasKey(
            $issuedStates[0],
            $storedFlows
        );

        $this->assertArrayNotHasKey(
            $issuedStates[1],
            $storedFlows
        );

        foreach (
            array_slice(
                $issuedStates,
                2
            ) as $state
        ) {
            $this->assertArrayHasKey(
                $state,
                $storedFlows
            );
        }
    }

    public function test_expired_state_is_rejected_and_removed(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-04 12:00:00'
            )
        );

        $request = $this->requestForUser(
            10
        );

        $service = app(
            MailOAuthStateService::class
        );

        $issued = $service->issue(
            $request,
            $this->connection(25)
        );

        CarbonImmutable::setTestNow(
            now()->addMinutes(11)
        );

        try {
            $service->consume(
                $request,
                $issued['state']
            );

            $this->fail(
                'Expected expired OAuth state to be rejected.'
            );
        } catch (
            MailOAuthAuthorizationException $exception
        ) {
            $this->assertSame(
                'The OAuth authorization request is invalid or has expired.',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $request
                ->session()
                ->has(
                    'mail_oauth_flows'
                )
        );
    }

    private function requestForUser(
        int $userId,
        ?Store $session = null
    ): Request {
        $user = new User;

        $user->forceFill([
            'id' => $userId,
        ]);

        $request = Request::create(
            '/admin/email/oauth-integrations',
            'GET'
        );

        $request->setLaravelSession(
            $session ?? $this->makeSessionStore()
        );

        $request->setUserResolver(
            static fn (): User => $user
        );

        return $request;
    }

    private function makeSessionStore(): Store
    {
        $session = new Store(
            'mail-oauth-state-test',
            new ArraySessionHandler(
                120
            )
        );

        $session->start();

        return $session;
    }

    private function connection(
        int $id
    ): MailProviderConnection {
        $connection =
            new MailProviderConnection;

        $connection->forceFill([
            'id' => $id,
        ]);

        return $connection;
    }
}
