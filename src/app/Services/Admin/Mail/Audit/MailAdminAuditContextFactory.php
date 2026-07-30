<?php

namespace App\Services\Admin\Mail\Audit;

use App\Data\Admin\Mail\MailAdminAuditTargetData;
use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailAdminAuditStatus;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\MailSensitiveDataRedactor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MailAdminAuditContextFactory
{
    public function __construct(
        private readonly MailAdminAuditResponseReader $responses,
        private readonly MailSensitiveDataRedactor $redactor,
    ) {
    }

    public function make(
        MailAdminAuditEvent $event,
        MailAdminAuditStatus $status,
        MailAdminAuditTargetData $target,
        Request $request,
        ?Response $response,
        int $durationMilliseconds,
        ?Throwable $exception = null,
    ): array {
        $context = [
            'route_name' => $request->route()?->getName(),
            'http_method' => $request->method(),
            'duration_ms' => $durationMilliseconds,
            'request' => $this->safeRequestContext($event, $request),
            'result' => $this->safeResultContext($event, $response),
        ];

        if ($target->subjectId !== null) {
            $context['subject_id'] = $target->subjectId;
        }

        if ($exception !== null) {
            $context['exception'] = [
                'class' => $exception::class,
                'message' => $this->redactor->redactString(
                    $exception->getMessage()
                ),
            ];
        }

        if ($status !== MailAdminAuditStatus::Succeeded) {
            $payload = $this->responses->read($response);

            $errorCode = $payload['error_code'] ?? null;
            $errors = $payload['errors'] ?? null;

            if (is_string($errorCode) && $errorCode !== '') {
                $context['error_code'] = $errorCode;
            }

            if (is_array($errors)) {
                $context['validation_fields'] = array_values(
                    array_map('strval', array_keys($errors))
                );
            }
        }

        return $this->redactor->sanitizeArray(
            array_filter(
                $context,
                static fn (mixed $value): bool =>
                    $value !== null
                    && $value !== []
                    && $value !== ''
            )
        );
    }

    private function safeRequestContext(
        MailAdminAuditEvent $event,
        Request $request
    ): array {
        return match ($event) {
            MailAdminAuditEvent::MailboxCreated,
            MailAdminAuditEvent::MailboxUpdated
            => $request->only([
                'name',
                'email_address',
                'display_name',
                'department_id',
                'is_active',
                'is_default_outgoing',
            ]),

            MailAdminAuditEvent::ChannelCreated,
            MailAdminAuditEvent::ChannelUpdated
            => $this->channelRequestContext($request),

            MailAdminAuditEvent::ProviderConnectionCreated,
            MailAdminAuditEvent::ProviderConnectionUpdated
            => $this->providerConnectionRequestContext($request),

            MailAdminAuditEvent::QuarantineIgnored => [
                'reason_provided' =>
                    trim((string) $request->input('reason')) !== '',
            ],

            default => [],
        };
    }

    private function channelRequestContext(
        Request $request
    ): array {
        $context = $request->only([
            'provider_connection_id',
            'name',
            'direction',
            'driver',
            'auth_type',
            'is_enabled',
            'is_primary',
            'failover_order',
        ]);

        $mailbox = $request->route('mailbox');

        if ($mailbox instanceof Mailbox) {
            $context['mailbox_id'] = $mailbox->id;
        } elseif ($request->filled('mailbox_id')) {
            $context['mailbox_id'] = (int) $request->input('mailbox_id');
        }

        $configuration = $request->input('configuration', []);
        $secrets = $request->input('secret_configuration', []);
        $clearSecretKeys = $request->input('clear_secret_keys', []);

        if (is_array($configuration)) {
            $context['configuration_keys'] = array_values(
                array_map('strval', array_keys($configuration))
            );
        }

        if (is_array($secrets)) {
            $context['secret_configuration_keys'] = array_values(
                array_map('strval', array_keys($secrets))
            );
        }

        if (is_array($clearSecretKeys)) {
            $context['cleared_secret_keys'] = array_values(
                array_filter(
                    array_map('strval', $clearSecretKeys)
                )
            );
        }

        return $context;
    }

    private function providerConnectionRequestContext(
        Request $request
    ): array {
        $context = $request->only([
            'name',
            'provider',
            'auth_type',
            'account_identifier',
            'tenant_identifier',
            'token_expires_at',
            'is_active',
        ]);

        $configuration = $request->input('configuration', []);
        $secrets = $request->input('secret_configuration', []);
        $clearSecretKeys = $request->input('clear_secret_keys', []);
        $scopes = $request->input('scopes', []);

        if (is_array($configuration)) {
            $context['configuration_keys'] = array_values(
                array_map('strval', array_keys($configuration))
            );
        }

        if (is_array($secrets)) {
            $context['secret_configuration_keys'] = array_values(
                array_map('strval', array_keys($secrets))
            );
        }

        if (is_array($clearSecretKeys)) {
            $context['cleared_secret_keys'] = array_values(
                array_filter(
                    array_map('strval', $clearSecretKeys)
                )
            );
        }

        if (is_array($scopes)) {
            $context['scopes_count'] = count($scopes);
        }

        return $context;
    }

    private function safeResultContext(
        MailAdminAuditEvent $event,
        ?Response $response
    ): array {
        $payload = $this->responses->read($response);
        $data = $payload['data'] ?? [];

        if (!is_array($data)) {
            return [];
        }

        return match ($event) {
            MailAdminAuditEvent::ChannelConnectionTested,
            MailAdminAuditEvent::ProviderConnectionTested,
            MailAdminAuditEvent::AntivirusConnectionTested
            => $this->connectionResultContext($data),

            MailAdminAuditEvent::MailboxSyncRequested,
            MailAdminAuditEvent::OutgoingMessageRetryRequested,
            MailAdminAuditEvent::AttachmentRescanRequested,
            MailAdminAuditEvent::QuarantineRetryRequested,
            MailAdminAuditEvent::QuarantineIgnored
            => $this->actionResultContext($data),

            default => [],
        };
    }

    private function connectionResultContext(
        array $data
    ): array {
        $context = [
            'successful' => $data['successful'] ?? null,
            'message' => isset($data['message'])
                ? $this->redactor->redactString((string) $data['message'])
                : null,
            'latency_ms' => $data['latency_ms'] ?? null,
        ];

        $details = $data['details'] ?? [];

        if (is_array($details)) {
            $context['details'] = array_intersect_key(
                $details,
                array_flip([
                    'channel_id',
                    'provider_connection_id',
                    'direction',
                    'driver',
                    'error_code',
                    'retryable',
                    'failover_allowed',
                    'health_status',
                    'total_channels',
                    'successful_channels',
                    'failed_channels',
                    'host',
                    'port',
                ])
            );
        }

        return array_filter(
            $context,
            static fn (mixed $value): bool =>
                $value !== null
                && $value !== []
                && $value !== ''
        );
    }

    private function actionResultContext(
        array $data
    ): array {
        $context = [
            'accepted' => $data['accepted'] ?? null,
            'dispatched' => $data['dispatched'] ?? null,
            'message' => isset($data['message'])
                ? $this->redactor->redactString((string) $data['message'])
                : null,
        ];

        $details = $data['details'] ?? [];

        if (is_array($details)) {
            $context['details'] = array_intersect_key(
                $details,
                array_flip([
                    'mailbox_id',
                    'incoming_channels',
                    'email_message_id',
                    'attachment_id',
                    'quarantine_id',
                    'scan_status',
                    'resolution',
                ])
            );
        }

        return array_filter(
            $context,
            static fn (mixed $value): bool =>
                $value !== null
                && $value !== []
                && $value !== ''
        );
    }
}
