<?php

namespace App\Http\Controllers\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailAdminAuditStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\Audit\MailAdminAuditLogger;
use App\Services\Admin\Mail\OAuth\MailOAuthProviderRegistry;
use App\Services\Admin\Mail\OAuth\MailOAuthStateService;
use App\Services\Admin\Mail\OAuth\MailOAuthTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class MailOAuthCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        MailOAuthStateService $states,
        MailOAuthProviderRegistry $providers,
        MailOAuthTokenService $tokens,
        MailAdminAuditLogger $audit
    ): RedirectResponse {
        $startedAt = hrtime(true);
        $connection = null;

        try {
            $state = $request
                ->string('state')
                ->toString();

            $flow = $states->consume(
                $request,
                $state
            );

            $connection = MailProviderConnection::query()
                ->findOrFail(
                    $flow['connection_id']
                );

            $request
                ->route()
                ?->setParameter(
                    'connection',
                    $connection
                );

            if ($request->filled('error')) {
                throw new RuntimeException(
                    'The OAuth provider denied the authorization request.'
                );
            }

            $code = $request
                ->string('code')
                ->toString();

            if ($code === '') {
                throw new RuntimeException(
                    'The OAuth callback did not include an authorization code.'
                );
            }

            $data = $providers
                ->resolve(
                    $connection->provider
                )
                ->exchangeAuthorizationCode(
                    $connection,
                    $code,
                    $flow['verifier'],
                    $flow['nonce'],
                    route(
                        'admin.email.oauth-integrations.callback'
                    )
                );

            $tokens->storeTokens(
                $connection,
                $data
            );

            $response = redirect()
                ->route(
                    'admin.email.oauth-integrations.edit',
                    $connection
                )
                ->with(
                    'success',
                    'OAuth account connected.'
                );

            $this->recordSafely(
                $audit,
                MailAdminAuditEvent::OAuthAccountConnected,
                MailAdminAuditStatus::Succeeded,
                $request,
                $response,
                $startedAt
            );

            return $response;
        } catch (Throwable $exception) {
            $response = $connection
            instanceof MailProviderConnection
                ? redirect()->route(
                    'admin.email.oauth-integrations.edit',
                    $connection
                )
                : redirect()->route(
                    'admin.email.oauth-integrations.index'
                );

            $response->with(
                'error',
                $request->filled('error')
                    ? 'OAuth authorization was cancelled or denied.'
                    : 'OAuth authorization could not be completed. Please try again or review the integration configuration.'
            );

            $this->recordSafely(
                $audit,
                MailAdminAuditEvent::OAuthAuthorizationFailed,
                MailAdminAuditStatus::Failed,
                $request,
                $response,
                $startedAt,
                $exception
            );

            return $response;
        }
    }

    private function recordSafely(
        MailAdminAuditLogger $audit,
        MailAdminAuditEvent $event,
        MailAdminAuditStatus $status,
        Request $request,
        RedirectResponse $response,
        int $startedAt,
        ?Throwable $exception = null
    ): void {
        try {
            $audit->record(
                $event,
                $status,
                $request,
                $response,
                (int) round(
                    (
                        hrtime(true)
                        - $startedAt
                    ) / 1_000_000
                ),
                $exception
            );
        } catch (Throwable $auditException) {
            report($auditException);
        }
    }
}
