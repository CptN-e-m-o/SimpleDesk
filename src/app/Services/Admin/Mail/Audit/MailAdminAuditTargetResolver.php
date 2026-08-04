<?php

namespace App\Services\Admin\Mail\Audit;

use App\Data\Admin\Mail\MailAdminAuditTargetData;
use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailAdminAuditSubjectType;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Models\Admin\Mail\ReplyParsingRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MailAdminAuditTargetResolver
{
    public function __construct(
        private readonly MailAdminAuditResponseReader $responses,
    ) {}

    public function resolve(
        MailAdminAuditEvent $event,
        Request $request,
        ?Response $response,
    ): MailAdminAuditTargetData {
        $subjectType = $this->subjectType($event);
        $routeTarget = $this->routeTarget($event, $request);

        $subjectId = $routeTarget?->getKey();

        if ($subjectId === null) {
            $subjectId = $this->responseSubjectId($response);
        }

        return new MailAdminAuditTargetData(
            subjectType: $subjectType,
            subjectId: $subjectId === null ? null : (int) $subjectId,
            mailboxId: $this->mailboxId(
                routeTarget: $routeTarget,
                request: $request,
                response: $response,
                event: $event,
            ),
        );
    }

    private function subjectType(
        MailAdminAuditEvent $event
    ): ?MailAdminAuditSubjectType {
        return match ($event) {
            MailAdminAuditEvent::MailboxCreated,
            MailAdminAuditEvent::MailboxUpdated,
            MailAdminAuditEvent::MailboxDeleted,
            MailAdminAuditEvent::MailboxSyncRequested => MailAdminAuditSubjectType::Mailbox,

            MailAdminAuditEvent::ReplyParsingRuleCreated,
            MailAdminAuditEvent::ReplyParsingRuleUpdated,
            MailAdminAuditEvent::ReplyParsingRuleDeleted,
            MailAdminAuditEvent::ReplyParsingRuleRestored,
            MailAdminAuditEvent::ReplyParsingRuleForceDeleted => MailAdminAuditSubjectType::ReplyParsingRule,

            MailAdminAuditEvent::ChannelCreated,
            MailAdminAuditEvent::ChannelUpdated,
            MailAdminAuditEvent::ChannelDeleted,
            MailAdminAuditEvent::ChannelConnectionTested => MailAdminAuditSubjectType::MailboxChannel,

            MailAdminAuditEvent::ProviderConnectionCreated,
            MailAdminAuditEvent::ProviderConnectionUpdated,
            MailAdminAuditEvent::ProviderConnectionDeleted,
            MailAdminAuditEvent::ProviderConnectionTested => MailAdminAuditSubjectType::ProviderConnection,

            MailAdminAuditEvent::OutgoingMessageRetryRequested => MailAdminAuditSubjectType::EmailMessage,

            MailAdminAuditEvent::AttachmentRescanRequested => MailAdminAuditSubjectType::EmailAttachment,

            MailAdminAuditEvent::QuarantineRetryRequested,
            MailAdminAuditEvent::QuarantineIgnored => MailAdminAuditSubjectType::EmailQuarantine,

            MailAdminAuditEvent::AntivirusConnectionTested => MailAdminAuditSubjectType::Antivirus,
        };
    }

    private function routeTarget(
        MailAdminAuditEvent $event,
        Request $request
    ): ?Model {
        $parameter = match ($event) {
            MailAdminAuditEvent::MailboxUpdated,
            MailAdminAuditEvent::MailboxDeleted,
            MailAdminAuditEvent::MailboxSyncRequested => 'mailbox',

            MailAdminAuditEvent::ChannelUpdated,
            MailAdminAuditEvent::ChannelDeleted,
            MailAdminAuditEvent::ChannelConnectionTested => 'channel',

            MailAdminAuditEvent::ProviderConnectionUpdated,
            MailAdminAuditEvent::ProviderConnectionDeleted,
            MailAdminAuditEvent::ProviderConnectionTested => 'providerConnection',

            MailAdminAuditEvent::ReplyParsingRuleUpdated,
            MailAdminAuditEvent::ReplyParsingRuleDeleted,
            MailAdminAuditEvent::ReplyParsingRuleRestored,
            MailAdminAuditEvent::ReplyParsingRuleForceDeleted => 'rule',

            MailAdminAuditEvent::OutgoingMessageRetryRequested => 'message',

            MailAdminAuditEvent::AttachmentRescanRequested => 'attachment',

            MailAdminAuditEvent::QuarantineRetryRequested,
            MailAdminAuditEvent::QuarantineIgnored => 'quarantine',

            default => null,
        };

        if ($parameter === null) {
            return null;
        }

        $target = $request->route($parameter);

        if ($target instanceof Model) {
            return $target;
        }

        if (is_numeric($target)
            && in_array($event, [
                MailAdminAuditEvent::ReplyParsingRuleUpdated,
                MailAdminAuditEvent::ReplyParsingRuleDeleted,
                MailAdminAuditEvent::ReplyParsingRuleRestored,
                MailAdminAuditEvent::ReplyParsingRuleForceDeleted,
            ], true)) {
            return (new ReplyParsingRule)->forceFill([
                'id' => (int) $target,
            ]);
        }

        return null;
    }

    private function responseSubjectId(
        ?Response $response
    ): ?int {
        $payload = $this->responses->read($response);

        $id = data_get($payload, 'data.id');

        return is_numeric($id) ? (int) $id : null;
    }

    private function mailboxId(
        ?Model $routeTarget,
        Request $request,
        ?Response $response,
        MailAdminAuditEvent $event,
    ): ?int {
        $routeMailbox = $request->route('mailbox');

        if ($routeMailbox instanceof Mailbox) {
            return $routeMailbox->id;
        }

        if ($routeTarget instanceof Mailbox) {
            return $routeTarget->id;
        }

        if ($routeTarget instanceof MailboxChannel) {
            return $routeTarget->mailbox_id;
        }

        if ($routeTarget instanceof EmailMessage) {
            return $routeTarget->mailbox_id;
        }

        if ($routeTarget instanceof EmailMessageQuarantine) {
            return $routeTarget->mailbox_id;
        }

        if ($routeTarget instanceof EmailAttachment) {
            $mailboxId = $routeTarget
                ->emailMessage()
                ->value('mailbox_id');

            return $mailboxId === null
                ? null
                : (int) $mailboxId;
        }

        if ($routeTarget instanceof MailProviderConnection) {
            return null;
        }

        if ($event === MailAdminAuditEvent::MailboxCreated) {
            return $this->responseSubjectId($response);
        }

        $requestMailboxId = $request->input('mailbox_id');

        if (is_numeric($requestMailboxId)) {
            return (int) $requestMailboxId;
        }

        $payload = $this->responses->read($response);

        $responseMailboxId =
            data_get($payload, 'data.mailbox_id')
            ?? data_get($payload, 'data.mailbox.id');

        return is_numeric($responseMailboxId)
            ? (int) $responseMailboxId
            : null;
    }
}
